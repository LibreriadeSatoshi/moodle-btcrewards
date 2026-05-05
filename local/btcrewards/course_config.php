<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Edit per-course btcrewards configuration.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);

require_login($course);
$context = context_course::instance($course->id);
require_capability('moodle/course:update', $context);

$pageurl = new moodle_url('/local/btcrewards/course_config.php', ['courseid' => $course->id]);
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('course_config_title', 'local_btcrewards'));
$PAGE->set_heading($course->fullname);

$form = new \local_btcrewards\form\course_config_form($pageurl->out(false));

$existing = \local_btcrewards\course_config::get($course->id);
$form->set_data([
    'courseid'                => $course->id,
    'enabled'                 => $existing ? (int) $existing->enabled : 0,
    'points_course_completed' => $existing && $existing->points_course_completed !== null
        ? (int) $existing->points_course_completed : '',
    'points_quiz_passed'      => $existing && $existing->points_quiz_passed !== null
        ? (int) $existing->points_quiz_passed : '',
    'points_badge_awarded'    => $existing && $existing->points_badge_awarded !== null
        ? (int) $existing->points_badge_awarded : '',
]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
}

if ($data = $form->get_data()) {
    $tonull = function ($value): ?int {
        if ($value === '' || $value === null) {
            return null;
        }
        return (int) $value;
    };

    \local_btcrewards\course_config::save(
        $course->id,
        !empty($data->enabled),
        $tonull($data->points_course_completed),
        $tonull($data->points_quiz_passed),
        $tonull($data->points_badge_awarded)
    );
    redirect($pageurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('course_config_title', 'local_btcrewards'));
$form->display();
echo $OUTPUT->footer();
