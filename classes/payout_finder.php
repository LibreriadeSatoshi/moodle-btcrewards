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
     * Paginated/filtered listing of payouts for the admin browse section.
     *
     * @param array $filters {status: string, q_user: string, q_dest: string, from: int, to: int}
     */
    public static function search_payouts(array $filters, int $page = 0, int $pagesize = 25): array {
        global $DB;
        [$where, $params] = self::build_search_where($filters);
        $sql = "SELECT q.id, q.userid, q.status, q.usd_cents, q.sats, q.destination,
                       q.txid, q.attempts, q.last_error, q.timecreated, q.timemodified,
                       u.firstname, u.lastname, u.email
                  FROM {btcrewards_payout_queue} q
                  JOIN {user} u ON u.id = q.userid
                 WHERE $where
              ORDER BY q.id DESC";
        return $DB->get_records_sql($sql, $params, $page * $pagesize, $pagesize);
    }

    /**
     * Count rows matching the same filters as search_payouts().
     */
    public static function count_payouts(array $filters): int {
        global $DB;
        [$where, $params] = self::build_search_where($filters);
        return (int) $DB->count_records_sql(
            "SELECT COUNT(*) FROM {btcrewards_payout_queue} q JOIN {user} u ON u.id = q.userid WHERE $where",
            $params
        );
    }

    private static function build_search_where(array $filters): array {
        global $DB;
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'q.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['q_user'])) {
            $needle = '%' . $DB->sql_like_escape($filters['q_user']) . '%';
            $where[] = '('
                . $DB->sql_like('u.firstname', '?', false, false) . ' OR '
                . $DB->sql_like('u.lastname',  '?', false, false) . ' OR '
                . $DB->sql_like('u.email',     '?', false, false) . ')';
            $params[] = $needle;
            $params[] = $needle;
            $params[] = $needle;
        }
        if (!empty($filters['q_dest'])) {
            $needle = '%' . $DB->sql_like_escape($filters['q_dest']) . '%';
            $where[] = '('
                . $DB->sql_like('q.destination', '?', false, false) . ' OR '
                . $DB->sql_like('q.txid',        '?', false, false) . ')';
            $params[] = $needle;
            $params[] = $needle;
        }
        if (!empty($filters['from'])) {
            $where[] = 'q.timecreated >= ?';
            $params[] = (int) $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 'q.timecreated <= ?';
            $params[] = (int) $filters['to'];
        }
        return [implode(' AND ', $where), $params];
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
