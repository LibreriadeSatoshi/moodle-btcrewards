<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Read-only queries used by the admin payout page.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards;

defined('MOODLE_INTERNAL') || die();

class payout_finder {
    /**
     * Queue rows awaiting admin approval, joined with the submitting user.
     */
    public static function pending_approvals(): array {
        global $DB;
        $sql = "SELECT q.id, q.userid, q.usd_cents, q.sats, q.destination, q.timecreated,
                       u.firstname, u.lastname, u.email
                  FROM {btcrewards_payout_queue} q
                  JOIN {user} u ON u.id = q.userid
                 WHERE q.status = ?
              ORDER BY q.timecreated";
        return $DB->get_records_sql($sql, [payout_status::PENDING_APPROVAL]);
    }

    /**
     * Failed queue rows, joined with the originating user.
     */
    public static function failed_payouts(): array {
        global $DB;
        $sql = "SELECT q.id, q.userid, q.usd_cents, q.sats, q.destination, q.last_error, q.timemodified,
                       u.firstname, u.lastname, u.email
                  FROM {btcrewards_payout_queue} q
                  JOIN {user} u ON u.id = q.userid
                 WHERE q.status = ?
              ORDER BY q.timemodified DESC";
        return $DB->get_records_sql($sql, [payout_status::FAILED]);
    }

    /**
     * Users whose unclaimed-points value reaches the configured payout
     * threshold. Powers the admin "trigger payout" section.
     */
    public static function users_above_threshold(int $centsperpoint, int $minpayoutcents): array {
        global $DB;
        $sql = "SELECT p.userid, SUM(p.points) AS pts, u.firstname, u.lastname, u.email
                  FROM {btcrewards_points} p
             LEFT JOIN {btcrewards_payout_items} pi ON pi.pointsid = p.id
                  JOIN {user} u ON u.id = p.userid
                 WHERE pi.id IS NULL
              GROUP BY p.userid, u.firstname, u.lastname, u.email
                HAVING SUM(p.points) * ? >= ?";
        return $DB->get_records_sql($sql, [$centsperpoint, $minpayoutcents]);
    }
}
