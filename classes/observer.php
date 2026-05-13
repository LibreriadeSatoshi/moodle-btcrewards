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

class observer {
    public static function course_completed(\core\event\course_completed $event): void {
        $userid   = (int) $event->relateduserid;
        $courseid = (int) $event->courseid;
        $points   = course_config::course_completion_points($courseid);
        if ($points <= 0) {
            return;
        }
        (new payout_engine())->award_points($userid, $courseid, $points, 'course', $courseid);
    }

    public static function user_graded(\core\event\user_graded $event): void {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $userid   = (int) $event->relateduserid;
        $courseid = (int) $event->courseid;
        if (!course_config::is_course_enabled($courseid)) {
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
        $points = quiz_config::points($gradeitemid);
        if ($points <= 0) {
            return;
        }
        (new payout_engine())->award_points($userid, $courseid, $points, 'grade_items', $gradeitemid);
    }

    public static function badge_awarded(\core\event\badge_awarded $event): void {
        $userid   = (int) $event->relateduserid;
        $courseid = (int) $event->courseid;
        $badgeid  = (int) $event->objectid;

        // Strict: no rewards for site-wide badges (courseid == SITEID).
        if ($courseid <= SITEID) {
            return;
        }
        if (!course_config::is_course_enabled($courseid)) {
            return;
        }
        $points = badge_config::points($badgeid);
        if ($points <= 0) {
            return;
        }
        (new payout_engine())->award_points($userid, $courseid, $points, 'badge', $badgeid);
    }
}
