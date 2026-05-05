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
     * Rules:
     *  - If courseid is 0 (no course context, e.g. site badges), use the plugin default.
     *  - If no row exists for the course, return 0 (rewards are opt-in).
     *  - If the row is disabled, return 0.
     *  - Otherwise return the per-course override, falling back to the plugin default
     *    when the column is null.
     *
     * @param int    $courseid
     * @param string $key One of points_course_completed|points_quiz_passed|points_badge_awarded.
     * @return int
     */
    public static function resolve_points(int $courseid, string $key): int {
        $default = (int) get_config('local_btcrewards', $key);

        if ($courseid <= 0) {
            return $default;
        }

        $row = self::get($courseid);
        if (!$row || empty($row->enabled)) {
            return 0;
        }

        return isset($row->{$key}) && $row->{$key} !== null ? (int) $row->{$key} : $default;
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
