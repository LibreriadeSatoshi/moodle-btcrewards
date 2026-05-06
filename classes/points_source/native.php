<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Native points source backed by btcrewards_points.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards\points_source;

defined('MOODLE_INTERNAL') || die();

/**
 * Native built-in implementation of the points source interface.
 */
class native implements base {
    /**
     * {@inheritdoc}
     */
    public function get_points(int $userid): int {
        global $DB;
        $sql = "SELECT COALESCE(SUM(points), 0) FROM {btcrewards_points} WHERE userid = :userid";
        return (int) $DB->get_field_sql($sql, ['userid' => $userid]);
    }

    /**
     * {@inheritdoc}
     */
    public function get_unclaimed_points(int $userid): int {
        global $DB;
        $sql = "SELECT COALESCE(SUM(p.points), 0)
                  FROM {btcrewards_points} p
             LEFT JOIN {btcrewards_payout_items} pi ON pi.pointsid = p.id
                 WHERE p.userid = :userid AND pi.id IS NULL";
        return (int) $DB->get_field_sql($sql, ['userid' => $userid]);
    }

    /**
     * {@inheritdoc}
     */
    public function get_unclaimed_point_ids(int $userid): array {
        global $DB;
        $sql = "SELECT p.id
                  FROM {btcrewards_points} p
             LEFT JOIN {btcrewards_payout_items} pi ON pi.pointsid = p.id
                 WHERE p.userid = :userid AND pi.id IS NULL";
        return array_map('intval', array_keys($DB->get_records_sql($sql, ['userid' => $userid])));
    }

    /**
     * {@inheritdoc}
     */
    public function is_available(): bool {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get_name(): string {
        return 'Native (built-in)';
    }
}
