<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Event observer registration for local_btcrewards.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\\core\\event\\course_completed',
        'callback'  => '\\local_btcrewards\\observer::course_completed',
    ],
    [
        'eventname' => '\\core\\event\\user_graded',
        'callback'  => '\\local_btcrewards\\observer::user_graded',
    ],
    [
        'eventname' => '\\core\\event\\badge_awarded',
        'callback'  => '\\local_btcrewards\\observer::badge_awarded',
    ],
];
