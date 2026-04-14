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
    public function get_points_since(int $userid, int $since): int {
        global $DB;
        $sql = "SELECT COALESCE(SUM(points), 0)
                  FROM {btcrewards_points}
                 WHERE userid = :userid AND timecreated > :since";
        return (int) $DB->get_field_sql($sql, ['userid' => $userid, 'since' => $since]);
    }

    /**
     * {@inheritdoc}
     */
    public function get_last_payout_watermark(int $userid): int {
        global $DB;
        $record = $DB->get_record('btcrewards_payout_watermark', ['userid' => $userid]);
        return $record ? (int) $record->points : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function set_last_payout_watermark(int $userid, int $points): void {
        global $DB;
        $now = time();
        $existing = $DB->get_record('btcrewards_payout_watermark', ['userid' => $userid]);
        if ($existing) {
            $existing->points = $points;
            $existing->timemodified = $now;
            $DB->update_record('btcrewards_payout_watermark', $existing);
            return;
        }
        $DB->insert_record('btcrewards_payout_watermark', (object) [
            'userid' => $userid,
            'points' => $points,
            'timemodified' => $now,
        ]);
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
