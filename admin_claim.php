<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin payout management: approve/reject pending student claims and
 * initiate payouts on behalf of any user.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_login();
if (!is_siteadmin()) {
    throw new moodle_exception('accessdenied', 'admin');
}

$pageurl = new moodle_url('/local/btcrewards/admin_claim.php');
$PAGE->set_url($pageurl);
$PAGE->set_context(\context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('admin_claim_title', 'local_btcrewards'));
$PAGE->set_heading(get_string('admin_claim_title', 'local_btcrewards'));

/**
 * Render a single-button POST form for an approve/reject action.
 */
$button_form = function (moodle_url $url, string $action, int $payoutid,
                        string $label, string $btnclass, string $extraformclass = ''): string {
    $form  = html_writer::start_tag('form', ['method' => 'post', 'action' => $url,
        'class' => 'd-inline ' . $extraformclass]);
    $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey',   'value' => sesskey()]);
    $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action',    'value' => $action]);
    $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'payoutid',  'value' => $payoutid]);
    $form .= html_writer::tag('button', $label, ['type' => 'submit', 'class' => 'btn btn-sm ' . $btnclass]);
    $form .= html_writer::end_tag('form');
    return $form;
};

/**
 * Render the destination input + Pay button form for an admin-initiated claim.
 */
$pay_form = function (moodle_url $url, int $userid): string {
    $form  = html_writer::start_tag('form', ['method' => 'post', 'action' => $url, 'class' => 'd-flex gap-2']);
    $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action',  'value' => 'claim']);
    $form .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'userid',  'value' => $userid]);
    $form .= html_writer::empty_tag('input', ['type' => 'text',   'name' => 'destination',
        'class'       => 'form-control form-control-sm',
        'placeholder' => get_string('my_address_placeholder', 'local_btcrewards'),
        'required'    => 'required']);
    $form .= html_writer::tag('button', get_string('admin_claim_pay', 'local_btcrewards'),
        ['type' => 'submit', 'class' => 'btn btn-sm btn-primary']);
    $form .= html_writer::end_tag('form');
    return $form;
};

$user_cell = function (\stdClass $row): string {
    return fullname($row) . ' <small class="text-muted">' . s($row->email) . '</small>';
};

// --- Handle POST actions before rendering. ---
$action = optional_param('action', '', PARAM_ALPHA);
if ($action !== '' && confirm_sesskey()) {
    $engine = new \local_btcrewards\payout_engine();
    if ($action === 'approve') {
        $payoutid = required_param('payoutid', PARAM_INT);
        $engine->approve_pending($payoutid);
        redirect($pageurl, get_string('admin_claim_approved', 'local_btcrewards'),
            null, \core\output\notification::NOTIFY_SUCCESS);
    }
    if ($action === 'reject') {
        $payoutid = required_param('payoutid', PARAM_INT);
        $engine->reject_pending($payoutid);
        redirect($pageurl, get_string('admin_claim_rejected', 'local_btcrewards'),
            null, \core\output\notification::NOTIFY_INFO);
    }
    if ($action === 'release') {
        $payoutid = required_param('payoutid', PARAM_INT);
        try {
            $engine->requeue_failed($payoutid);
            redirect($pageurl, get_string('admin_failed_released', 'local_btcrewards'),
                null, \core\output\notification::NOTIFY_SUCCESS);
        } catch (\moodle_exception $e) {
            redirect($pageurl, $e->getMessage(),
                null, \core\output\notification::NOTIFY_ERROR);
        }
    }
    if ($action === 'claim') {
        $targetuserid = required_param('userid', PARAM_INT);
        $destination  = required_param('destination', PARAM_RAW_TRIMMED);
        try {
            $engine->claim($targetuserid, $destination, true);
            redirect($pageurl, get_string('admin_claim_submitted', 'local_btcrewards'),
                null, \core\output\notification::NOTIFY_SUCCESS);
        } catch (\moodle_exception $e) {
            redirect($pageurl, $e->getMessage(),
                null, \core\output\notification::NOTIFY_ERROR);
        }
    }
}

echo $OUTPUT->header();

// --- Section 1: pending approvals. ---
echo $OUTPUT->heading(get_string('admin_claim_pending_heading', 'local_btcrewards'), 3);

$pending = \local_btcrewards\payout_finder::pending_approvals();
if (empty($pending)) {
    echo html_writer::tag('p', get_string('admin_claim_pending_empty', 'local_btcrewards'),
        ['class' => 'text-muted']);
} else {
    $table = new html_table();
    $table->head = [
        get_string('admin_claim_col_user', 'local_btcrewards'),
        get_string('admin_claim_col_amount', 'local_btcrewards'),
        get_string('admin_claim_col_destination', 'local_btcrewards'),
        get_string('admin_claim_col_when', 'local_btcrewards'),
        get_string('admin_claim_col_actions', 'local_btcrewards'),
    ];
    foreach ($pending as $row) {
        $usd  = '$' . number_format(((int) $row->usd_cents) / 100, 2);
        $sats = number_format((int) $row->sats);
        $approve = $button_form($pageurl, 'approve', (int) $row->id,
            get_string('admin_claim_approve', 'local_btcrewards'), 'btn-success', 'mr-1');
        $reject  = $button_form($pageurl, 'reject', (int) $row->id,
            get_string('admin_claim_reject', 'local_btcrewards'), 'btn-outline-danger');
        $table->data[] = [
            $user_cell($row),
            $usd . ' <small class="text-muted">(' . $sats . ' sats)</small>',
            html_writer::tag('code', shorten_text($row->destination, 40)),
            userdate($row->timecreated),
            $approve . ' ' . $reject,
        ];
    }
    echo html_writer::table($table);
}

// --- Section 2: admin-initiated claim. ---
echo $OUTPUT->heading(get_string('admin_claim_initiate_heading', 'local_btcrewards'), 3, 'mt-5');

$centsperpoint  = local_btcrewards_cents_per_point();
$minpayoutcents = local_btcrewards_min_payout_cents();
$claimable = \local_btcrewards\payout_finder::users_above_threshold($centsperpoint, $minpayoutcents);

if (empty($claimable)) {
    echo html_writer::tag('p', get_string('admin_claim_initiate_empty', 'local_btcrewards'),
        ['class' => 'text-muted']);
} else {
    $table = new html_table();
    $table->head = [
        get_string('admin_claim_col_user', 'local_btcrewards'),
        get_string('admin_claim_col_unclaimed', 'local_btcrewards'),
        get_string('admin_claim_col_destination', 'local_btcrewards'),
    ];
    foreach ($claimable as $row) {
        $usd = '$' . number_format(((int) $row->pts) * $centsperpoint / 100, 2);
        $table->data[] = [
            $user_cell($row),
            $usd . ' <small class="text-muted">(' . number_format((int) $row->pts) . ' pts)</small>',
            $pay_form($pageurl, (int) $row->userid),
        ];
    }
    echo html_writer::table($table);
}

// --- Section 3: failed payouts (admin can release points back to the student). ---
echo $OUTPUT->heading(get_string('admin_failed_heading', 'local_btcrewards'), 3, 'mt-5');

$failed = \local_btcrewards\payout_finder::failed_payouts();
if (empty($failed)) {
    echo html_writer::tag('p', get_string('admin_failed_empty', 'local_btcrewards'),
        ['class' => 'text-muted']);
} else {
    $table = new html_table();
    $table->head = [
        get_string('admin_claim_col_user', 'local_btcrewards'),
        get_string('admin_claim_col_amount', 'local_btcrewards'),
        get_string('admin_claim_col_destination', 'local_btcrewards'),
        get_string('admin_failed_col_error', 'local_btcrewards'),
        get_string('admin_claim_col_when', 'local_btcrewards'),
        get_string('admin_claim_col_actions', 'local_btcrewards'),
    ];
    foreach ($failed as $row) {
        $usd  = '$' . number_format(((int) $row->usd_cents) / 100, 2);
        $sats = number_format((int) $row->sats);
        $release = $button_form($pageurl, 'release', (int) $row->id,
            get_string('admin_failed_release', 'local_btcrewards'), 'btn-outline-primary');
        $table->data[] = [
            $user_cell($row),
            $usd . ' <small class="text-muted">(' . $sats . ' sats)</small>',
            html_writer::tag('code', shorten_text($row->destination, 40)),
            html_writer::tag('small', s((string) ($row->last_error ?? '')), ['class' => 'text-muted']),
            userdate($row->timemodified),
            $release,
        ];
    }
    echo html_writer::table($table);
}

// --- Section 4: read-only history of recent payouts. ---
echo $OUTPUT->heading(get_string('admin_recent_heading', 'local_btcrewards'), 3, 'mt-5');

$recent = \local_btcrewards\payout_finder::recent_payouts(50);
if (empty($recent)) {
    echo html_writer::tag('p', get_string('admin_recent_empty', 'local_btcrewards'),
        ['class' => 'text-muted']);
} else {
    $table = new html_table();
    $table->head = [
        '#',
        get_string('admin_claim_col_user', 'local_btcrewards'),
        get_string('my_col_status', 'local_btcrewards'),
        get_string('admin_claim_col_amount', 'local_btcrewards'),
        get_string('admin_claim_col_destination', 'local_btcrewards'),
        get_string('admin_recent_col_attempts', 'local_btcrewards'),
        get_string('admin_claim_col_when', 'local_btcrewards'),
    ];
    foreach ($recent as $row) {
        $usd  = '$' . number_format(((int) $row->usd_cents) / 100, 2);
        $sats = number_format((int) $row->sats);
        $statuslabel = get_string('payout_status_' . $row->status, 'local_btcrewards');
        $table->data[] = [
            (int) $row->id,
            $user_cell($row),
            $statuslabel,
            $usd . ' <small class="text-muted">(' . $sats . ' sats)</small>',
            html_writer::tag('code', shorten_text($row->destination, 35)),
            (int) $row->attempts,
            userdate($row->timecreated),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
