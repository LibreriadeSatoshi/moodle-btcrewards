<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Status changes on the payout queue.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards;

/**
 * Compare-and-set status transitions for btcrewards_payout_queue rows.
 *
 * Admin actions (release, approve, reject) and payout service webhooks can act
 * on the same row at the same moment. Writing back a whole row read earlier
 * lets one silently undo the other, e.g. a release returning the points of a
 * payout a webhook just marked PAID. A transition only applies if the row is
 * still in the status the caller read.
 */
final class payout_queue {
    /**
     * Move a payout from one status to another, if it is still in $from.
     *
     * @param int    $payoutid
     * @param string $from   Status the caller expects the row to be in.
     * @param string $to
     * @param array  $fields Other columns to set in the same update (column => value).
     * @return bool False when the row was no longer in $from: nothing changed.
     */
    public static function transition(int $payoutid, string $from, string $to, array $fields = []): bool {
        global $DB;

        $fields = ['status' => $to, 'timemodified' => time()] + $fields;
        $sets = [];
        $params = [];
        foreach ($fields as $column => $value) {
            $sets[] = "$column = :set_$column";
            $params["set_$column"] = $value;
        }
        $params['id'] = $payoutid;
        $params['from'] = $from;

        $transaction = $DB->start_delegated_transaction();
        $DB->execute('UPDATE {btcrewards_payout_queue} SET ' . implode(', ', $sets) .
            ' WHERE id = :id AND status = :from', $params);
        $applied = $DB->get_field('btcrewards_payout_queue', 'status', ['id' => $payoutid]) === $to;
        $transaction->allow_commit();
        return $applied;
    }
}
