<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Core event observers for local_btcrewards.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards;

defined('MOODLE_INTERNAL') || die();

/**
 * Translates Moodle core events into points awards.
 */
class observer {
    /**
     * Award points for course completion.
     *
     * @param \core\event\course_completed $event
     * @return void
     */
    public static function course_completed(\core\event\course_completed $event): void {
        $userid   = (int) $event->relateduserid;
        $courseid = (int) $event->courseid;
        $points   = course_config::resolve_points($courseid, 'points_course_completed');
        if ($points <= 0) {
            return;
        }
        (new payout_engine())->award_points($userid, $courseid, $points, 'course', $courseid);
    }

    /**
     * Award points for a passing grade.
     *
     * @param \core\event\user_graded $event
     * @return void
     */
    public static function user_graded(\core\event\user_graded $event): void {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $userid   = (int) $event->relateduserid;
        $courseid = (int) $event->courseid;
        $points = course_config::resolve_points($courseid, 'points_quiz_passed');
        if ($points <= 0) {
            return;
        }

        $grade = $event->get_grade();
        if (!$grade) {
            return;
        }
        $finalgrade = $grade->finalgrade;
        $gradeitem  = $grade->grade_item ?? null;
        if ($finalgrade === null || !$gradeitem) {
            return;
        }

        $passmark = isset($gradeitem->gradepass) ? (float) $gradeitem->gradepass : 0.0;
        if ($passmark <= 0 || (float) $finalgrade < $passmark) {
            return;
        }

        $gradeitemid = (int) $gradeitem->id;
        (new payout_engine())->award_points($userid, $courseid, $points, 'grade_items', $gradeitemid);
    }

    /**
     * Award points for an earned badge.
     *
     * @param \core\event\badge_awarded $event
     * @return void
     */
    public static function badge_awarded(\core\event\badge_awarded $event): void {
        $userid   = (int) $event->relateduserid;
        $courseid = (int) $event->courseid;
        $badgeid  = (int) $event->objectid;
        $points   = course_config::resolve_points($courseid, 'points_badge_awarded');
        if ($points <= 0) {
            return;
        }
        (new payout_engine())->award_points($userid, $courseid, $points, 'badge', $badgeid);
    }
}
