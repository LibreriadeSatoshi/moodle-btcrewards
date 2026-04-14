<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Payout engine: records points, checks thresholds, drains the queue.
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
 * Coordinates award, threshold check, queue insertion, and queue draining.
 */
class payout_engine {
    /** @var points_source */
    private $source;

    /**
     * Build the engine using the configured points source.
     */
    public function __construct() {
        $this->source = points_source_factory::get();
    }

    /**
     * Record a points award and evaluate whether a payout should be queued.
     *
     * @param int    $userid
     * @param int    $points
     * @param string $eventtype
     * @return void
     */
    public function award_points(int $userid, int $points, string $eventtype): void {
        global $DB;

        if ($points <= 0) {
            return;
        }

        $DB->insert_record('btcrewards_points', (object) [
            'userid'      => $userid,
            'points'      => $points,
            'event_type'  => $eventtype,
            'timecreated' => time(),
        ]);

        $this->check_threshold($userid);
    }

    /**
     * If the user has crossed the payout threshold, queue a payout row.
     *
     * @param int $userid
     * @return void
     */
    public function check_threshold(int $userid): void {
        global $DB;

        $threshold    = (int) get_config('local_btcrewards', 'payout_threshold');
        $satsperpoint = (int) get_config('local_btcrewards', 'sats_per_point');

        if ($threshold <= 0 || $satsperpoint <= 0) {
            return;
        }

        $total     = $this->source->get_points($userid);
        $watermark = $this->source->get_last_payout_watermark($userid);
        $sincelast = $total - $watermark;

        if ($sincelast < $threshold) {
            return;
        }

        $destination = $this->get_user_destination($userid);
        if ($destination === null) {
            return;
        }

        $now = time();
        $DB->insert_record('btcrewards_payout_queue', (object) [
            'userid'       => $userid,
            'sats'         => $sincelast * $satsperpoint,
            'destination'  => $destination['address'],
            'dest_type'    => $destination['type'],
            'status'       => 'pending',
            'txid'         => null,
            'attempts'     => 0,
            'timecreated'  => $now,
            'timemodified' => $now,
        ]);

        $this->source->set_last_payout_watermark($userid, $total);
    }

    /**
     * Drain the pending queue. Called by the scheduled task.
     *
     * @return void
     */
    public function process_queue(): void {
        global $DB;

        $maxattempts = (int) get_config('local_btcrewards', 'max_attempts');
        $client = new payment_client();

        $rows = $DB->get_records('btcrewards_payout_queue', ['status' => 'pending']);
        foreach ($rows as $row) {
            if ((int) $row->attempts >= $maxattempts) {
                continue;
            }

            try {
                $result = $client->pay(
                    (int) $row->userid,
                    (int) $row->sats,
                    (string) $row->destination,
                    (string) $row->dest_type
                );
            } catch (payment_exception $e) {
                $result = ['success' => false, 'txid' => '', 'error' => $e->getMessage()];
            }

            $row->attempts     = (int) $row->attempts + 1;
            $row->timemodified = time();
            if (!empty($result['success'])) {
                $row->status = 'paid';
                $row->txid   = $result['txid'];
            } else if ($row->attempts >= $maxattempts) {
                $row->status = 'failed';
            }
            $DB->update_record('btcrewards_payout_queue', $row);
        }
    }

    /**
     * Resolve a user's preferred payout destination. Hook for profile/custom fields.
     *
     * @param int $userid
     * @return array{address: string, type: string}|null
     */
    private function get_user_destination(int $userid) {
        // TODO: resolve from user profile field. Scaffolded to return null by default.
        unset($userid);
        return null;
    }
}
