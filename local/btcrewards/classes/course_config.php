<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Helper for resolving per-course reward configuration.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolves effective point values for a course, honoring the opt-in config table.
 */
class course_config {
    /**
     * Fetch the raw config row for a course, or false if none exists.
     *
     * @param int $courseid
     * @return \stdClass|false
     */
    public static function get(int $courseid) {
        global $DB;
        return $DB->get_record('btcrewards_course_config', ['courseid' => $courseid]);
    }

    /**
     * Resolve the point value to award for a given event in a given course.
     *
     * Rewards are strictly per-course opt-in — there is no site-wide default.
     *
     * Rules:
     *  - If courseid <= 0 (no course context, e.g. site-level badges), return 0.
     *  - If no btcrewards_course_config row exists, return 0.
     *  - If the row is disabled, return 0.
     *  - If the override column is NULL, return 0.
     *  - Otherwise return the per-course override.
     *
     * @param int    $courseid
     * @param string $key One of points_course_completed|points_quiz_passed|points_badge_awarded.
     * @return int
     */
    public static function resolve_points(int $courseid, string $key): int {
        if ($courseid <= 0) {
            return 0;
        }

        $row = self::get($courseid);
        if (!$row || empty($row->enabled)) {
            return 0;
        }

        return isset($row->{$key}) && $row->{$key} !== null ? (int) $row->{$key} : 0;
    }

    /**
     * Insert or update a per-course config row.
     *
     * @param int        $courseid
     * @param bool       $enabled
     * @param int|null   $pointscoursecompleted
     * @param int|null   $pointsquizpassed
     * @param int|null   $pointsbadgeawarded
     * @return void
     */
    public static function save(
        int $courseid,
        bool $enabled,
        ?int $pointscoursecompleted,
        ?int $pointsquizpassed,
        ?int $pointsbadgeawarded
    ): void {
        global $DB;

        $now = time();
        $existing = self::get($courseid);
        $data = (object) [
            'courseid'                 => $courseid,
            'enabled'                  => $enabled ? 1 : 0,
            'points_course_completed'  => $pointscoursecompleted,
            'points_quiz_passed'       => $pointsquizpassed,
            'points_badge_awarded'     => $pointsbadgeawarded,
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
