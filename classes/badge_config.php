<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Per-badge reward config (course-context badges only).
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards;

defined('MOODLE_INTERNAL') || die();

class badge_config extends item_config {
    protected static function table(): string {
        return 'btcrewards_badge_config';
    }

    protected static function key_column(): string {
        return 'badgeid';
    }

    /**
     * List the course's badges (course-context only, badge.type=2) with their
     * configured points (if any). Returns rows: id, name, points (null if none).
     */
    public static function list_for_course(int $courseid): array {
        global $DB;
        return $DB->get_records_sql(
            "SELECT b.id, b.name, bc.points
               FROM {badge} b
          LEFT JOIN {btcrewards_badge_config} bc ON bc.badgeid = b.id
              WHERE b.courseid = ? AND b.type = 2
           ORDER BY b.name",
            [$courseid]
        );
    }
}
