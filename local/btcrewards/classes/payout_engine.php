<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Payout engine: records points, handles claims, submits pending payouts.
 *
 * Status transitions to terminal states (paid / failed) happen in webhook.php,
 * not here — the payment microservice pushes those updates to us.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards;

use local_btcrewards\points_source\base as points_source;
use local_btcrewards\points_source\factory as points_source_factory;

defined('MOODLE_INTERNAL') || die();

/**
 * Coordinates point awards, student claims, and queue draining.
 */
class payout_engine {
    /** @var points_source */
    private $source;

    public function __construct() {
        $this->source = points_source_factory::get();
    }

    /**
     * Record a points award. Silently no-ops on duplicate (userid, component, itemid).
     *
     * @return bool True if a new row was inserted, false if duplicate.
     */
    public function award_points(int $userid, int $courseid, int $points, string $component, int $itemid): bool {
        global $DB;

        if ($points <= 0) {
            return false;
        }

        try {
            $DB->insert_record('btcrewards_points', (object) [
                'userid'      => $userid,
                'courseid'    => $courseid,
                'points'      => $points,
                'component'   => $component,
                'itemid'      => $itemid,
                'timecreated' => time(),
            ]);
            return true;
        } catch (\dml_write_exception $e) {
            return false;
        }
    }

    /**
     * Claim all unclaimed points as a queued onchain payout.
     *
     * @throws \moodle_exception When misconfigured, below threshold, or missing address.
     */
    public function claim(int $userid, string $destination): int {
        global $DB;

        $destination = trim($destination);
        if ($destination === '') {
            throw new \moodle_exception('claim_no_destination', 'local_btcrewards');
        }

        global $CFG;
        require_once($CFG->dirroot . '/local/btcrewards/lib.php');

        $minpayoutcents = local_btcrewards_min_payout_cents();
        $centsperpoint  = local_btcrewards_cents_per_point();

        if ($minpayoutcents <= 0 || $centsperpoint <= 0) {
            throw new \moodle_exception('claim_misconfigured', 'local_btcrewards');
        }

        $unclaimed = $this->source->get_unclaimed_points($userid);
        $usdcents  = $unclaimed * $centsperpoint;
        if ($usdcents < $minpayoutcents) {
            throw new \moodle_exception('claim_below_threshold', 'local_btcrewards',
                '', number_format($minpayoutcents / 100, 2));
        }

        $pointids = $this->source->get_unclaimed_point_ids($userid);
        if (empty($pointids)) {
            throw new \moodle_exception('claim_below_threshold', 'local_btcrewards');
        }

        // Fetch live rate + onchain limit. Rate locks into the queue row so
        // sats are deterministic between now and when the cron submits.
        $client    = new payment_client();
        $ratecents = $client->fetch_rate();
        $sats      = (int) round($usdcents * 100000000 / $ratecents);

        // Per-rail validation: reject onchain destinations below the live
        // Boltz floor before we even enqueue. Lightning has no practical
        // floor (~21 sats), so we only gate onchain.
        if (local_btcrewards_guess_dest_type($destination) === 'onchain') {
            try {
                $limits = $client->fetch_limits();
            } catch (\moodle_exception $e) {
                $limits = ['onchain_min' => 0];
            }
            if ($limits['onchain_min'] > 0 && $sats < $limits['onchain_min']) {
                $minusd = $limits['onchain_min'] * $ratecents / 100000000 / 100;
                throw new \moodle_exception('claim_onchain_below_min', 'local_btcrewards',
                    '', number_format($minusd, 2));
            }
        }

        $now = time();
        $payoutid = $DB->insert_record('btcrewards_payout_queue', (object) [
            'userid'       => $userid,
            'usd_cents'    => $usdcents,
            'btc_usd_rate' => $ratecents,
            'sats'         => $sats,
            'destination'  => $destination,
            'dest_type'    => 'auto',
            'status'       => 'pending',
            'txid'         => null,
            'preimage'     => null,
            'attempts'     => 0,
            'timecreated'  => $now,
            'timemodified' => $now,
        ]);

        foreach ($pointids as $pid) {
            $DB->insert_record('btcrewards_payout_items', (object) [
                'payoutid' => $payoutid,
                'pointsid' => $pid,
            ]);
        }

        return (int) $payoutid;
    }

    /**
     * Free the points consumed by a failed payout so they can be claimed again.
     *
     * The failed row stays in the queue as an audit record, but its
     * btcrewards_payout_items links are dropped — which makes those points
     * "unclaimed" again from the points_source's perspective.
     *
     * @throws \moodle_exception When the row doesn't belong to the user,
     *                           isn't in failed state, or doesn't exist.
     */
    public function requeue_failed(int $payoutid, int $userid): void {
        global $DB;

        $row = $DB->get_record('btcrewards_payout_queue', ['id' => $payoutid], '*', MUST_EXIST);
        if ((int) $row->userid !== $userid) {
            throw new \moodle_exception('requeue_forbidden', 'local_btcrewards');
        }
        if ($row->status !== 'failed') {
            throw new \moodle_exception('requeue_not_failed', 'local_btcrewards');
        }

        $DB->delete_records('btcrewards_payout_items', ['payoutid' => $payoutid]);
        // Mark the row so the UI can distinguish "points still locked" from
        // "points already refunded into the user's balance".
        $row->status = 'requeued';
        $row->timemodified = time();
        $DB->update_record('btcrewards_payout_queue', $row);
    }

    /**
     * Process the payout queue: submit pending rows to the payment service.
     *
     * Terminal states (paid/failed) arrive later via webhook.php.
     */
    public function process_queue(): void {
        global $DB;

        $maxattempts = (int) get_config('local_btcrewards', 'max_attempts');
        $client = new payment_client();

        $rows = $DB->get_records('btcrewards_payout_queue', ['status' => 'pending']);
        foreach ($rows as $row) {
            if ((int) $row->attempts >= $maxattempts) {
                $row->status = 'failed';
                $row->timemodified = time();
                $DB->update_record('btcrewards_payout_queue', $row);
                continue;
            }

            $result = $client->pay((int) $row->sats, (string) $row->destination);

            $row->attempts     = (int) $row->attempts + 1;
            $row->timemodified = time();
            if (!empty($result['tx_id'])) {
                $row->txid = $result['tx_id'];
            }
            if (!empty($result['dest_type'])) {
                $row->dest_type = $result['dest_type'];
            }

            $servicestatus = $result['status'] ?? '';
            $retryable     = (bool) ($result['retryable'] ?? true);

            if ($servicestatus === payment_client::STATUS_SETTLED) {
                // Rare: payment completed synchronously before webhook could race us.
                $row->status = 'paid';
            } else if ($servicestatus === payment_client::STATUS_FAILED) {
                // Permanent failures (amount mismatch, bad destination) are marked
                // terminal immediately — retrying won't change the outcome.
                if (!$retryable) {
                    $row->status = 'failed';
                } else {
                    $row->status = ($row->attempts >= $maxattempts) ? 'failed' : 'pending';
                }
            } else if ($servicestatus === payment_client::STATUS_PROCESSING ||
                       $servicestatus === payment_client::STATUS_ACCEPTED) {
                // Await webhook for terminal state.
                $row->status = 'accepted';
            } else {
                $row->status = ($row->attempts >= $maxattempts) ? 'failed' : 'pending';
            }

            $DB->update_record('btcrewards_payout_queue', $row);
        }
    }
}
