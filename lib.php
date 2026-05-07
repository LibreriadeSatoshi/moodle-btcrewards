<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Public library callbacks for local_btcrewards.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Read the "USD per point" admin setting and return it as integer cents.
 *
 * The admin setting is a decimal-dollar string (e.g. "0.01"). We keep integer
 * cents internally so all math (points × cents → sats) stays exact.
 *
 * @return int cents per point; 0 if unconfigured or invalid.
 */
function local_btcrewards_cents_per_point(): int {
    $raw = (string) get_config('local_btcrewards', 'usd_per_point');
    if ($raw === '') {
        return 0;
    }
    return (int) round(((float) $raw) * 100);
}

/**
 * Read the "Minimum payout" admin setting and return it as integer cents.
 *
 * @return int minimum claim in cents; 0 if unconfigured or invalid.
 */
function local_btcrewards_min_payout_cents(): int {
    $raw = (string) get_config('local_btcrewards', 'min_payout_usd');
    if ($raw === '') {
        return 0;
    }
    return (int) round(((float) $raw) * 100);
}

/**
 * Cheap, prefix-based detection of the destination type used for client-side
 * validation. Authoritative classification still happens server-side via the
 * Breez SDK parser; this is just to gate "this rail can't take a claim this
 * small" decisions before we POST.
 *
 * @return string One of 'onchain'|'bolt11'|'bolt12'|'ln_address'|'unknown'.
 */
function local_btcrewards_guess_dest_type(string $destination): string {
    $d = strtolower(trim($destination));
    if ($d === '') {
        return 'unknown';
    }
    if (preg_match('/^(bc1|tb1|bcrt1)/', $d) || preg_match('/^[13][a-km-z1-9]{20,}$/', $d)) {
        return 'onchain';
    }
    if (str_starts_with($d, 'lnbc') || str_starts_with($d, 'lntb') ||
        str_starts_with($d, 'lnbcrt') || str_starts_with($d, 'lnsb')) {
        return 'bolt11';
    }
    if (str_starts_with($d, 'lno')) {
        return 'bolt12';
    }
    if (preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $d)) {
        return 'ln_address';
    }
    return 'unknown';
}

/**
 * Adds a "Bitcoin rewards" link to the course administration menu.
 *
 * @param navigation_node $navigation
 * @param stdClass        $course
 * @param context         $context
 * @return void
 */
/**
 * Injects a "My Bitcoin rewards" link directly into the header navbar.
 *
 * Core calls this hook when rendering the navbar and appends the returned
 * HTML next to the user menu. This is the most reliable way to surface a
 * plugin link in Moodle 4.x Boost.
 *
 * @param renderer_base $renderer
 * @return string
 */
function local_btcrewards_render_navbar_output(renderer_base $renderer) {
    if (!isloggedin() || isguestuser()) {
        return '';
    }
    $url = new moodle_url('/local/btcrewards/my.php');
    $label = get_string('my_nav', 'local_btcrewards');
    $svg = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">'
    . '<circle cx="12" cy="12" r="8.25" stroke="currentColor" stroke-width="1.5"/>'
    . '<text x="12" y="17" text-anchor="middle" font-family="Georgia, \'Times New Roman\', serif" font-weight="600" font-size="13" fill="currentColor">&#8383;</text>'
    . '</svg>';
    return html_writer::link(
        $url,
        $svg,
        [
            'class' => 'rui-topbar-special-btn rui-tooltip--bottom text-decoration-none',
            'aria-label' => $label,
            'data-title' => $label,
            'data-bs-placement' => 'left',
        ]
    );
}

function local_btcrewards_extend_navigation_course(navigation_node $navigation, stdClass $course, context $context) {
    if (!is_siteadmin()) {
        return;
    }
    $url = new moodle_url('/local/btcrewards/course_config.php', ['courseid' => $course->id]);
    $node = navigation_node::create(
        get_string('course_config_nav', 'local_btcrewards'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'local_btcrewards_course_config'
    );
    $navigation->add_node($node);
}
