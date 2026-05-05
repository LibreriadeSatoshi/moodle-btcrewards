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

global $CFG;
require_once($CFG->dirroot . '/local/btcrewards/lib.php');

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
     * Claim all unclaimed points as a queued payout.
     *
     * @throws \moodle_exception When misconfigured, below threshold, or missing address.
     */
    public function claim(int $userid, string $destination): int {
        $destination = $this->validate_destination($destination);
        $usdcents    = $this->compute_claim_value($userid);

        $pointids = $this->source->get_unclaimed_point_ids($userid);
        if (empty($pointids)) {
            throw new \moodle_exception('claim_below_threshold', 'local_btcrewards');
        }

        $client = new payment_client();
        [$ratecents, $sats] = $this->lock_rate_and_sats($usdcents, $client);
        $this->enforce_onchain_floor($destination, $sats, $ratecents, $client);

        return $this->persist_claim($userid, $usdcents, $ratecents, $sats, $destination, $pointids);
    }

    /**
     * Trim and require a non-empty destination string.
     *
     * @throws \moodle_exception
     */
    private function validate_destination(string $destination): string {
        $destination = trim($destination);
        if ($destination === '') {
            throw new \moodle_exception('claim_no_destination', 'local_btcrewards');
        }
        return $destination;
    }

    /**
     * Read pricing config, compute the user's claim value in USD cents, and
     * gate against the configured minimum payout.
     *
     * @return int USD cents the user is claiming.
     * @throws \moodle_exception
     */
    private function compute_claim_value(int $userid): int {
        $minpayoutcents = local_btcrewards_min_payout_cents();
        $centsperpoint  = local_btcrewards_cents_per_point();

        if ($minpayoutcents <= 0 || $centsperpoint <= 0) {
            throw new \moodle_exception('claim_misconfigured', 'local_btcrewards');
        }

        $usdcents = $this->source->get_unclaimed_points($userid) * $centsperpoint;
        if ($usdcents < $minpayoutcents) {
            throw new \moodle_exception('claim_below_threshold', 'local_btcrewards',
                '', number_format($minpayoutcents / 100, 2));
        }
        return $usdcents;
    }

    /**
     * Lock the BTC/USD rate and convert the claim value to sats. The rate
     * stays on the queue row for audit so the same number is used at submit
     * time even if the live rate has drifted.
     *
     * @return array{0: int, 1: int} [rate_cents_per_btc, sats]
     */
    private function lock_rate_and_sats(int $usdcents, payment_client $client): array {
        $ratecents = $client->fetch_rate();
        $sats      = (int) round($usdcents * 100000000 / $ratecents);
        return [$ratecents, $sats];
    }

    /**
     * Reject onchain destinations whose sats amount is below the swap
     * provider's live minimum. Lightning has no practical floor and is
     * accepted as-is.
     *
     * @throws \moodle_exception
     */
    private function enforce_onchain_floor(string $destination, int $sats, int $ratecents, payment_client $client): void {
        if (local_btcrewards_guess_dest_type($destination) !== 'onchain') {
            return;
        }
        try {
            $limits = $client->fetch_limits();
        } catch (\moodle_exception $e) {
            return; // Limits unavailable — fall back to whatever the service decides at /pay time.
        }
        if ($limits['onchain_min'] > 0 && $sats < $limits['onchain_min']) {
            $minusd = $limits['onchain_min'] * $ratecents / 100000000 / 100;
            throw new \moodle_exception('claim_onchain_below_min', 'local_btcrewards',
                '', number_format($minusd, 2));
        }
    }

    /**
     * Insert the queue row and link every consumed point.
     *
     * @return int payoutid of the new queue row.
     */
    private function persist_claim(
        int $userid, int $usdcents, int $ratecents, int $sats,
        string $destination, array $pointids
    ): int {
        global $DB;
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
