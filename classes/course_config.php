<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Per-course config: enable flag + course-completion points.
 *
 * Quiz and badge rewards live in separate per-item tables (see quiz_config,
 * badge_config); this class only handles the course-level concerns.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards;

defined('MOODLE_INTERNAL') || die();

class course_config {
    public static function get(int $courseid) {
        global $DB;
        return $DB->get_record('btcrewards_course_config', ['courseid' => $courseid]);
    }

    public static function is_course_enabled(int $courseid): bool {
        if ($courseid <= 0) {
            return false;
        }
        $row = self::get($courseid);
        return $row && !empty($row->enabled);
    }

    /**
     * Points for completing the course. 0 if not configured.
     */
    public static function course_completion_points(int $courseid): int {
        $row = self::get($courseid);
        if (!$row || empty($row->enabled)) {
            return 0;
        }
        return $row->points_course_completed !== null ? (int) $row->points_course_completed : 0;
    }

    public static function save(int $courseid, bool $enabled, ?int $pointscoursecompleted): void {
        global $DB;

        $now = time();
        $existing = self::get($courseid);
        $data = (object) [
            'courseid'                 => $courseid,
            'enabled'                  => $enabled ? 1 : 0,
            'points_course_completed'  => $pointscoursecompleted,
            'timemodified'             => $now,
        ];

        if ($existing) {
            $data->id = $existing->id;
            $DB->update_record('btcrewards_course_config', $data);
            return;
        }
        $DB->insert_record('btcrewards_course_config', $data);
    }
}
