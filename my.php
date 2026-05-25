<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Student-facing Bitcoin rewards dashboard.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();
if (isguestuser()) {
    redirect(new moodle_url('/'));
}

$pageurl = new moodle_url('/local/btcrewards/my.php');
$PAGE->set_url($pageurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('my_title', 'local_btcrewards'));
$PAGE->set_heading(get_string('my_title', 'local_btcrewards'));

$source        = \local_btcrewards\points_source\factory::get();
$userid        = (int) $USER->id;
$total         = $source->get_points($userid);
$unclaimed     = $source->get_unclaimed_points($userid);
require_once(__DIR__ . '/lib.php');
$minpayoutcents = local_btcrewards_min_payout_cents();
$centsperpoint  = local_btcrewards_cents_per_point();

// Handle claim submission.
if (data_submitted() && confirm_sesskey()) {
    try {
        $destination = trim(required_param('destination', PARAM_RAW_TRIMMED));
        (new \local_btcrewards\payout_engine())->claim($userid, $destination);
        redirect($pageurl, get_string('my_claimed_ok', 'local_btcrewards'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    } catch (\moodle_exception $e) {
        redirect($pageurl, $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
}

// Fetch all point rows and mark which ones have been claimed.
$allpoints = $DB->get_records('btcrewards_points', ['userid' => $userid], 'timecreated DESC');
$claimedids = [];
if ($allpoints) {
    $pids = array_keys($allpoints);
    [$insql, $params] = $DB->get_in_or_equal($pids);
    $claimed = $DB->get_fieldset_select('btcrewards_payout_items', 'pointsid', "pointsid $insql", $params);
    $claimedids = array_flip($claimed);
}
$coursemap = [];
foreach ($allpoints as $row) {
    $row->_claimed = isset($claimedids[$row->id]);
    $coursemap[(int) $row->courseid][] = $row;
}

// Bulk-fetch course names.
$courseids = array_filter(array_keys($coursemap), function ($id) { return $id > 0; });
$coursenames = [];
if ($courseids) {
    [$insql, $params] = $DB->get_in_or_equal($courseids);
    $records = $DB->get_records_select('course', "id $insql", $params, '', 'id, fullname');
    foreach ($records as $c) {
        $coursenames[(int) $c->id] = format_string($c->fullname);
    }
}

// Fetch payout queue and their consumed point items.
$queue = $DB->get_records('btcrewards_payout_queue', ['userid' => $userid], 'timecreated DESC', '*', 0, 50);
$payoutdetails = [];
if ($queue) {
    $payoutids = array_keys($queue);
    [$insql, $params] = $DB->get_in_or_equal($payoutids);
    $sql = "SELECT pi.id, pi.payoutid, p.points, p.component, p.courseid, p.timecreated
              FROM {btcrewards_payout_items} pi
              JOIN {btcrewards_points} p ON p.id = pi.pointsid
             WHERE pi.payoutid $insql
          ORDER BY p.timecreated ASC";
    $items = $DB->get_records_sql($sql, $params);
    foreach ($items as $item) {
        $payoutdetails[(int) $item->payoutid][] = $item;
    }
}

// Fetch BTC/USD rate + per-rail limits. Failure is non-fatal — the overview
// still renders; the claim form is disabled if the rate is missing, and the
// onchain availability hint just disappears if limits are missing.
$ratecents = 0;
$rateerror = '';
$onchainminsat = 0;
$client = new \local_btcrewards\payment_client();
try {
    $ratecents = $client->fetch_rate();
} catch (\moodle_exception $e) {
    $rateerror = $e->getMessage();
}
try {
    $limits = $client->fetch_limits();
    $onchainminsat = (int) $limits['onchain_min'];
} catch (\moodle_exception $e) {
    // ignore — UI degrades gracefully
}

echo $OUTPUT->header();

// ── Overview card ──────────────────────────────────────────────────────────
$projectedusdcents = $unclaimed * $centsperpoint;
$projectedusd = $projectedusdcents / 100;
$projectedsats = $ratecents > 0
    ? (int) round($projectedusdcents * 100000000 / $ratecents)
    : 0;
// Progress is in USD now: how close are you to the configured min payout?
$minpayoutusd = $minpayoutcents / 100;
$progress = $minpayoutcents > 0
    ? min(100, (int) round($projectedusdcents / $minpayoutcents * 100))
    : 0;
$onchainminusd = ($onchainminsat > 0 && $ratecents > 0)
    ? $onchainminsat * $ratecents / 100000000 / 100
    : 0;

echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');

echo html_writer::start_div('row');

echo html_writer::start_div('col-sm-6');
echo html_writer::tag('h5', get_string('my_total_points', 'local_btcrewards'), ['class' => 'text-muted mb-0']);
echo html_writer::tag('p', $total, ['class' => 'h2 font-weight-bold mb-3']);
echo html_writer::end_div();

echo html_writer::start_div('col-sm-6');
echo html_writer::tag('h5', get_string('my_unclaimed_points', 'local_btcrewards'), ['class' => 'text-muted mb-0']);
echo html_writer::tag('p', $unclaimed, ['class' => 'h2 font-weight-bold mb-3']);
echo html_writer::end_div();

echo html_writer::end_div(); // row

echo html_writer::tag('label',
    get_string('my_progress_label', 'local_btcrewards', [
        'current'   => '$' . number_format($projectedusd, 2),
        'threshold' => '$' . number_format($minpayoutusd, 2),
    ]),
    ['class' => 'small text-muted mb-1', 'style' => 'display:block']);
echo html_writer::start_div('progress mb-2', ['style' => 'height:20px']);
echo html_writer::tag('div', $progress . '%', [
    'class' => 'progress-bar' . ($progress >= 100 ? ' bg-success' : ''),
    'role'  => 'progressbar',
    'style' => 'width:' . $progress . '%',
    'aria-valuenow' => $progress,
    'aria-valuemin' => '0',
    'aria-valuemax' => '100',
]);
echo html_writer::end_div(); // progress

$ratedisplay = $ratecents > 0
    ? '$' . number_format(intdiv($ratecents, 100)) . '/BTC'
    : '—';
$usdperpoint = '$' . number_format($centsperpoint / 100, 2);
echo html_writer::start_div('row mt-2 text-muted small');
echo html_writer::tag('div',
    get_string('my_usd_per_point', 'local_btcrewards') . ': ' . $usdperpoint,
    ['class' => 'col-sm-4']);
echo html_writer::tag('div',
    get_string('my_current_rate', 'local_btcrewards') . ': ' . $ratedisplay,
    ['class' => 'col-sm-4']);
echo html_writer::tag('div',
    get_string('my_claim_value', 'local_btcrewards') . ': ' .
    '$' . number_format($projectedusd, 2),
    ['class' => 'col-sm-4']);
echo html_writer::end_div();

echo html_writer::end_div(); // card-body
echo html_writer::end_div(); // card

// ── Claim form ─────────────────────────────────────────────────────────────
if ($projectedusdcents < $minpayoutcents) {
    echo $OUTPUT->notification(
        get_string('my_below_threshold', 'local_btcrewards',
            number_format($minpayoutusd, 2)),
        \core\output\notification::NOTIFY_INFO);
} else if ($ratecents <= 0) {
    echo $OUTPUT->notification(
        get_string('my_rate_unavailable', 'local_btcrewards', $rateerror),
        \core\output\notification::NOTIFY_ERROR);
} else {
    $usdformatted  = '$' . number_format($projectedusd, 2);
    $satsformatted = number_format($projectedsats);
    $raterender    = '$' . number_format(intdiv($ratecents, 100)) . '/BTC';

    echo html_writer::start_div('card mb-4 border-success');
    echo html_writer::start_div('card-body');

    // Prominent payout amount — USD first (what the student thinks about),
    // sats second (what the wallet actually sends).
    $amountblock  = html_writer::tag('div',
        get_string('my_payout_amount_label', 'local_btcrewards'),
        ['class' => 'text-muted small text-uppercase mb-1']);
    $amountblock .= html_writer::tag('div',
        $usdformatted,
        ['class' => 'h2 mb-0 text-success font-weight-bold']);
    $amountblock .= html_writer::tag('div',
        '≈ ' . get_string('my_claim_value_sats', 'local_btcrewards', $satsformatted) .
        ' @ ' . $raterender,
        ['class' => 'text-muted small mt-1']);
    echo html_writer::tag('div', $amountblock, ['class' => 'mb-3']);

    $form  = html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $pageurl->out(false),
    ]);
    $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $form .= html_writer::start_div('form-group mb-2');
    $form .= html_writer::tag('label',
        get_string('my_address_label', 'local_btcrewards'),
        ['for' => 'local_btcrewards_destination', 'class' => 'font-weight-bold']);
    $form .= html_writer::empty_tag('input', [
        'type'         => 'text',
        'name'         => 'destination',
        'id'           => 'local_btcrewards_destination',
        'class'        => 'form-control',
        'required'     => 'required',
        'autocomplete' => 'off',
        'placeholder'  => get_string('my_address_placeholder', 'local_btcrewards'),
    ]);
    $form .= html_writer::tag('div',
        get_string('my_address_help', 'local_btcrewards', $satsformatted),
        ['class' => 'form-text text-muted small mt-2']);

    // Onchain availability hint — informational when above the floor, a
    // soft warning when the current claim is below it.
    if ($onchainminusd > 0) {
        $minusdfmt = '$' . number_format($onchainminusd, 2);
        if ($projectedusd < $onchainminusd) {
            $form .= html_writer::tag('div',
                '⚠ ' . get_string('my_onchain_unavailable', 'local_btcrewards', $minusdfmt),
                ['class' => 'small text-warning mt-2']);
        } else {
            $form .= html_writer::tag('div',
                get_string('my_onchain_available', 'local_btcrewards', $minusdfmt),
                ['class' => 'small text-muted mt-2']);
        }
    }

    $form .= html_writer::tag('div',
        get_string('my_address_warning', 'local_btcrewards'),
        ['class' => 'small text-danger mt-2']);
    $form .= html_writer::end_div();
    $form .= html_writer::tag('button',
        get_string('my_claim_button', 'local_btcrewards') . ' · ' . $usdformatted,
        ['type' => 'submit', 'class' => 'btn btn-success']);
    $form .= html_writer::end_tag('form');
    echo $form;
    echo html_writer::end_div(); // card-body
    echo html_writer::end_div(); // card
}

// ── Per-course breakdown ───────────────────────────────────────────────────
echo html_writer::tag('h3', get_string('my_courses_heading', 'local_btcrewards'), ['class' => 'mt-4 mb-3']);

if (empty($allpoints)) {
    echo html_writer::tag('p', get_string('my_empty_history', 'local_btcrewards'));
} else {
    foreach ($coursemap as $cid => $rows) {
        $cname = ($cid > 0 && isset($coursenames[$cid]))
            ? $coursenames[$cid]
            : get_string('my_course_unknown', 'local_btcrewards');

        $coursepoints = array_sum(array_column($rows, 'points'));

        echo html_writer::start_div('card mb-3 overflow-hidden');
        echo html_writer::start_div('card-header d-flex justify-content-between align-items-center');
        echo html_writer::tag('strong', $cname);
        echo html_writer::tag('span', $coursepoints . ' ' . get_string('my_col_points', 'local_btcrewards'),
            ['class' => 'badge badge-primary bg-primary text-white px-2 py-1']);
        echo html_writer::end_div();
        echo html_writer::start_div('card-body p-0');

        $table = new html_table();
        $table->attributes = ['class' => 'table table-sm mb-0'];
        $table->head = [
            get_string('my_col_when', 'local_btcrewards'),
            get_string('my_col_event', 'local_btcrewards'),
            get_string('my_col_points', 'local_btcrewards'),
            get_string('my_col_claimed', 'local_btcrewards'),
        ];
        foreach ($rows as $row) {
            $eventlabel = get_string('component_' . $row->component, 'local_btcrewards');
            $resource = local_btcrewards_resource_link(
                $row->component, (int) $row->itemid, (int) $row->courseid);
            if ($resource !== '') {
                $eventlabel .= ': ' . $resource;
            }
            $claimedbadge = $row->_claimed
                ? html_writer::tag('span', get_string('my_status_claimed', 'local_btcrewards'),
                    ['class' => 'badge bg-success text-white'])
                : html_writer::tag('span', get_string('my_status_unclaimed', 'local_btcrewards'),
                    ['class' => 'badge bg-secondary text-white']);
            $table->data[] = [
                userdate($row->timecreated),
                $eventlabel,
                (int) $row->points,
                $claimedbadge,
            ];
        }
        echo html_writer::table($table);
        echo html_writer::end_div(); // card-body
        echo html_writer::end_div(); // card
    }
}

// ── Payouts ────────────────────────────────────────────────────────────────
echo html_writer::tag('h3', get_string('my_queue_heading', 'local_btcrewards'), ['class' => 'mt-4 mb-3']);
if (empty($queue)) {
    echo html_writer::tag('p', get_string('my_empty_queue', 'local_btcrewards'));
} else {
    $statusclass = [
        \local_btcrewards\payout_status::PENDING_APPROVAL => 'badge-warning bg-warning text-dark',
        \local_btcrewards\payout_status::PENDING  => 'badge-warning bg-warning text-dark',
        \local_btcrewards\payout_status::ACCEPTED => 'badge-info bg-info text-white',
        'processing'                              => 'badge-primary bg-primary text-white',
        \local_btcrewards\payout_status::PAID     => 'badge-success bg-success text-white',
        \local_btcrewards\payout_status::FAILED   => 'badge-danger bg-danger text-white',
        \local_btcrewards\payout_status::REQUEUED => 'badge-secondary bg-secondary text-white',
    ];
    foreach ($queue as $row) {
        $statuslabel = get_string('payout_status_' . $row->status, 'local_btcrewards');
        $badge = html_writer::tag('span', $statuslabel, [
            'class' => 'badge ' . ($statusclass[$row->status] ?? 'badge-secondary bg-secondary'),
        ]);
        $desttypelabel = get_string('dest_type_' . $row->dest_type, 'local_btcrewards');

        $usd  = '$' . number_format(((int) $row->usd_cents) / 100, 2);
        $sats = number_format((int) $row->sats);
        $headline = userdate($row->timecreated) . ' — ' . $usd .
            ' ' . html_writer::tag('span', '· ' . $sats . ' sats',
                ['class' => 'text-muted small']);

        echo html_writer::start_div('card mb-3');
        echo html_writer::start_div('card-header d-flex justify-content-between align-items-center');
        echo html_writer::tag('span', $headline);
        echo $badge;
        echo html_writer::end_div();
        echo html_writer::start_div('card-body');

        $dl  = html_writer::start_tag('dl', ['class' => 'row mb-0']);
        $dl .= html_writer::tag('dt', get_string('my_col_destination', 'local_btcrewards'), ['class' => 'col-sm-3']);
        $dl .= html_writer::tag('dd', s($row->destination), ['class' => 'col-sm-9 text-break']);
        $dl .= html_writer::tag('dt', get_string('my_col_dest_type', 'local_btcrewards'), ['class' => 'col-sm-3']);
        $dl .= html_writer::tag('dd', $desttypelabel, ['class' => 'col-sm-9']);
        $dl .= html_writer::tag('dt', get_string('my_col_txid', 'local_btcrewards'), ['class' => 'col-sm-3']);
        $dl .= html_writer::tag('dd', $row->txid ? s($row->txid) : '—', ['class' => 'col-sm-9 text-break']);
        $dl .= html_writer::end_tag('dl');
        echo $dl;

        // Failed payouts are admin-only to resolve. Requeued means an admin
        // has released the points; the student can claim again with a new
        // destination.
        if ($row->status === \local_btcrewards\payout_status::FAILED) {
            echo html_writer::tag('div',
                get_string('my_failed_admin_contact', 'local_btcrewards'),
                ['class' => 'mt-3 small text-muted']);
        } else if ($row->status === \local_btcrewards\payout_status::REQUEUED) {
            echo html_writer::tag('div',
                '↩ ' . get_string('my_requeued_note', 'local_btcrewards'),
                ['class' => 'mt-3 small text-muted']);
        }

        // Show which rewards funded this payout.
        $details = $payoutdetails[(int) $row->id] ?? [];
        if ($details) {
            echo html_writer::tag('h6',
                get_string('my_payout_rewards', 'local_btcrewards'),
                ['class' => 'mt-2 mb-1']);
            $dtable = new html_table();
            $dtable->attributes = ['class' => 'table table-sm mb-0'];
            $dtable->head = [
                get_string('my_col_when', 'local_btcrewards'),
                get_string('my_col_event', 'local_btcrewards'),
                get_string('my_col_points', 'local_btcrewards'),
            ];
            foreach ($details as $item) {
                $cname = ((int) $item->courseid > 0 && isset($coursenames[(int) $item->courseid]))
                    ? $coursenames[(int) $item->courseid] . ' — '
                    : '';
                $eventlabel = get_string('component_' . $item->component, 'local_btcrewards');
                $dtable->data[] = [
                    userdate($item->timecreated),
                    $cname . $eventlabel,
                    (int) $item->points,
                ];
            }
            echo html_writer::table($dtable);
        }

        echo html_writer::end_div(); // card-body
        echo html_writer::end_div(); // card
    }
}

echo $OUTPUT->footer();
