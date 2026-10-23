<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Applies the payout service's terminal payment events to the payout queue.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards;

/**
 * Turns a verified webhook event (settled/failed) into a queue update.
 *
 * Events are idempotent by tx_id, with one exception: the payout service can
 * report a Lightning payment failed and later settled (its preimage arrived
 * late), and then the money did leave. A settled event therefore turns a
 * FAILED row into PAID, and flags a REQUEUED row, whose points were already
 * returned to the student, for an admin to check for a double payment.
 */
final class payout_event {
    /**
     * Apply one event.
     *
     * @param string $txid     Payment id from the service.
     * @param string $status   'settled' or 'failed'.
     * @param string $preimage Payment preimage, if the service sent one.
     * @return array{0: int, 1: array} HTTP status code and JSON body for the service.
     */
    public static function apply(string $txid, string $status, string $preimage = ''): array {
        global $DB;

        // A transition fails only if an admin action changed the row in between:
        // read it again and decide on its new status.
        for ($try = 0; $try < 3; $try++) {
            $row = $DB->get_record('btcrewards_payout_queue', ['txid' => $txid]);
            if (!$row) {
                // The event may arrive before our /pay response is persisted. Answer 404
                // so the service retries with backoff; on a later attempt the row exists.
                return [404, ['error' => 'tx_id not found', 'tx_id' => $txid]];
            }
            $result = self::apply_to($row, $status === 'settled', $preimage);
            if ($result !== null) {
                return $result;
            }
        }
        return [503, ['error' => 'payout changed concurrently, retry']];
    }

    /**
     * Apply the event to one row as read.
     *
     * @param \stdClass $row
     * @param bool      $settled
     * @param string    $preimage
     * @return array|null HTTP status and body, or null if the row changed meanwhile.
     */
    private static function apply_to(\stdClass $row, bool $settled, string $preimage): ?array {
        $fields = $preimage !== '' ? ['preimage' => $preimage] : [];

        if ($settled && $row->status === payout_status::FAILED) {
            $fields['last_error'] = 'Reported failed, then settled: the payment went out.';
            return payout_queue::transition($row->id, payout_status::FAILED, payout_status::PAID, $fields)
                ? [200, ['received' => true, 'settled_after_failure' => true]]
                : null;
        }
        if ($settled && $row->status === payout_status::REQUEUED) {
            $note = "Settled after its points were returned to the student: check payout {$row->id} "
                . "(user {$row->userid}) for a double payment.";
            $fields['last_error'] = $note;
            if (!payout_queue::transition($row->id, payout_status::REQUEUED, payout_status::REQUEUED, $fields)) {
                return null;
            }
            error_log("local_btcrewards: payment {$row->txid} {$note}");
            return [200, ['received' => true, 'warning' => 'settled after requeue']];
        }

        // Otherwise a terminal row only acknowledges repeats.
        if (in_array($row->status, [payout_status::PAID, payout_status::FAILED, payout_status::REQUEUED], true)) {
            return [200, ['received' => true, 'duplicate' => true]];
        }

        $to = $settled ? payout_status::PAID : payout_status::FAILED;
        return payout_queue::transition($row->id, $row->status, $to, $fields)
            ? [200, ['received' => true]]
            : null;
    }
}
