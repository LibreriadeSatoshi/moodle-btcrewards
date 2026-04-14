<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin settings for local_btcrewards.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_btcrewards', get_string('pluginname', 'local_btcrewards'));
    $ADMIN->add('localplugins', $settings);

    $sources = [
        'native' => get_string('source_native', 'local_btcrewards'),
        // 'xp' => get_string('source_xp', 'local_btcrewards'),
    ];
    $settings->add(new admin_setting_configselect(
        'local_btcrewards/points_source',
        get_string('setting_points_source', 'local_btcrewards'),
        get_string('setting_points_source_desc', 'local_btcrewards'),
        'native',
        $sources
    ));

    $settings->add(new admin_setting_configtext(
        'local_btcrewards/payout_threshold',
        get_string('setting_payout_threshold', 'local_btcrewards'),
        get_string('setting_payout_threshold_desc', 'local_btcrewards'),
        500,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_btcrewards/sats_per_point',
        get_string('setting_sats_per_point', 'local_btcrewards'),
        get_string('setting_sats_per_point_desc', 'local_btcrewards'),
        10,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_btcrewards/max_attempts',
        get_string('setting_max_attempts', 'local_btcrewards'),
        get_string('setting_max_attempts_desc', 'local_btcrewards'),
        5,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_btcrewards/payment_service_url',
        get_string('setting_payment_service_url', 'local_btcrewards'),
        get_string('setting_payment_service_url_desc', 'local_btcrewards'),
        'http://10.0.0.2:3000',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_btcrewards/payment_service_secret',
        get_string('setting_payment_service_secret', 'local_btcrewards'),
        get_string('setting_payment_service_secret_desc', 'local_btcrewards'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_btcrewards/points_course_completed',
        get_string('setting_points_course_completed', 'local_btcrewards'),
        get_string('setting_points_course_completed_desc', 'local_btcrewards'),
        200,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_btcrewards/points_quiz_passed',
        get_string('setting_points_quiz_passed', 'local_btcrewards'),
        get_string('setting_points_quiz_passed_desc', 'local_btcrewards'),
        50,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_btcrewards/points_badge_awarded',
        get_string('setting_points_badge_awarded', 'local_btcrewards'),
        get_string('setting_points_badge_awarded_desc', 'local_btcrewards'),
        100,
        PARAM_INT
    ));
}
