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

/**
 * Build a link to the Moodle resource that earned a points row, if any.
 * Returns an HTML <a> string, or '' for components without a meaningful link
 * (e.g. 'course' — the per-course card header already names the course).
 */
function local_btcrewards_resource_link(string $component, int $itemid, int $courseid): string {
    global $CFG, $DB;
    if ($component === 'grade_items') {
        require_once($CFG->libdir . '/gradelib.php');
        $gi = grade_item::fetch(['id' => $itemid]);
        if (!$gi || $gi->itemtype !== 'mod') {
            return '';
        }
        $cm = get_coursemodule_from_instance($gi->itemmodule, $gi->iteminstance,
            $courseid, false, IGNORE_MISSING);
        if (!$cm) {
            return '';
        }
        $url = new moodle_url("/mod/{$gi->itemmodule}/view.php", ['id' => $cm->id]);
        return html_writer::link($url, format_string($gi->itemname), ['target' => '_blank']);
    }
    if ($component === 'badge') {
        $badge = $DB->get_record('badge', ['id' => $itemid]);
        if (!$badge) {
            return '';
        }
        $url = new moodle_url('/badges/overview.php', ['id' => $itemid]);
        return html_writer::link($url, format_string($badge->name), ['target' => '_blank']);
    }
    return '';
}

/**
 * Ensure the user-profile field for storing a Lightning address exists.
 * Idempotent — safe to call from install + upgrade.
 *
 * @return int field id
 */
function local_btcrewards_ensure_profile_field(): int {
    global $DB;

    $shortname = 'btclnaddress';
    $existing = $DB->get_record('user_info_field', ['shortname' => $shortname]);
    if ($existing) {
        return (int) $existing->id;
    }

    $catname = get_string('pluginname', 'local_btcrewards');
    $category = $DB->get_record('user_info_category', ['name' => $catname]);
    if (!$category) {
        $maxorder = (int) $DB->get_field_sql('SELECT COALESCE(MAX(sortorder), 0) FROM {user_info_category}');
        $category = (object) ['name' => $catname, 'sortorder' => $maxorder + 1];
        $category->id = $DB->insert_record('user_info_category', $category);
    }

    $field = (object) [
        'shortname'         => $shortname,
        'name'              => get_string('profile_field_lnaddress', 'local_btcrewards'),
        'datatype'          => 'text',
        'description'       => get_string('profile_field_lnaddress_desc', 'local_btcrewards'),
        'descriptionformat' => FORMAT_HTML,
        'categoryid'        => $category->id,
        'sortorder'         => 1,
        'required'          => 0,
        'locked'            => 0,
        'visible'           => 1,
        'forceunique'       => 0,
        'signup'            => 0,
        'defaultdata'       => '',
        'defaultdataformat' => FORMAT_PLAIN,
        'param1'            => 40,
        'param2'            => 100,
        'param3'            => 0,
        'param4'            => '',
        'param5'            => '',
    ];
    return (int) $DB->insert_record('user_info_field', $field);
}

/**
 * Read the user's saved Lightning address from the custom profile field.
 * Returns empty string if unset.
 */
function local_btcrewards_get_user_ln_address(int $userid): string {
    global $DB;
    return (string) $DB->get_field_sql('
        SELECT d.data
          FROM {user_info_field} f
          JOIN {user_info_data} d ON d.fieldid = f.id
         WHERE f.shortname = ? AND d.userid = ?
    ', ['btclnaddress', $userid]);
}

/**
 * Save (or overwrite) the user's Lightning address in the custom profile field.
 */
function local_btcrewards_save_user_ln_address(int $userid, string $address): void {
    global $DB;
    $field = $DB->get_record('user_info_field', ['shortname' => 'btclnaddress'], '*', MUST_EXIST);
    $existing = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $field->id]);
    if ($existing) {
        $existing->data = $address;
        $existing->dataformat = FORMAT_PLAIN;
        $DB->update_record('user_info_data', $existing);
        return;
    }
    $DB->insert_record('user_info_data', [
        'userid'     => $userid,
        'fieldid'    => $field->id,
        'data'       => $address,
        'dataformat' => FORMAT_PLAIN,
    ]);
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
