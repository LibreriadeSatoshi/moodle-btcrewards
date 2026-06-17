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

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_btcrewards_admin_claim',
        get_string('admin_claim_title', 'local_btcrewards'),
        new moodle_url('/local/btcrewards/admin_claim.php')
    ));

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
        'local_btcrewards/min_payout_usd',
        get_string('setting_min_payout_usd', 'local_btcrewards'),
        get_string('setting_min_payout_usd_desc', 'local_btcrewards'),
        '0.50',
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_configtext(
        'local_btcrewards/usd_per_point',
        get_string('setting_usd_per_point', 'local_btcrewards'),
        get_string('setting_usd_per_point_desc', 'local_btcrewards'),
        '0.01',
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_configtext(
        'local_btcrewards/max_attempts',
        get_string('setting_max_attempts', 'local_btcrewards'),
        get_string('setting_max_attempts_desc', 'local_btcrewards'),
        5,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_btcrewards/allowed_ln_domain',
        get_string('setting_allowed_ln_domain', 'local_btcrewards'),
        get_string('setting_allowed_ln_domain_desc', 'local_btcrewards'),
        'pay.libreriadesatoshi.com',
        PARAM_HOST
    ));

    $settings->add(new admin_setting_configtext(
        'local_btcrewards/payment_service_url',
        get_string('setting_payment_service_url', 'local_btcrewards'),
        get_string('setting_payment_service_url_desc', 'local_btcrewards'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_btcrewards/payment_service_secret',
        get_string('setting_payment_service_secret', 'local_btcrewards'),
        get_string('setting_payment_service_secret_desc', 'local_btcrewards'),
        ''
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_btcrewards/webhook_secret',
        get_string('setting_webhook_secret', 'local_btcrewards'),
        get_string('setting_webhook_secret_desc', 'local_btcrewards'),
        ''
    ));

    $webhookurl = (new moodle_url('/local/btcrewards/webhook.php'))->out(false);
    $settings->add(new admin_setting_description(
        'local_btcrewards/webhook_url_info',
        get_string('setting_webhook_url', 'local_btcrewards'),
        get_string('setting_webhook_url_desc', 'local_btcrewards', $webhookurl)
    ));

    // Per-event point values are configured per-course only — there is no
    // site-wide default. This is intentional: a site-wide default could
    // silently award points across every course (and site-level badges) the
    // moment the plugin is installed, so we force opt-in.
}
