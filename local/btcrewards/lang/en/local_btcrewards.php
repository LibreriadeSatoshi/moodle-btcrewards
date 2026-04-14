<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * English strings for local_btcrewards.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Bitcoin Rewards';

$string['source_native'] = 'Native (built-in)';
$string['source_xp'] = 'Level Up XP';

$string['setting_points_source'] = 'Points source';
$string['setting_points_source_desc'] = 'Backend used to read and write student points.';
$string['setting_payout_threshold'] = 'Payout threshold';
$string['setting_payout_threshold_desc'] = 'Points a student must accumulate (above the last payout watermark) before a payout is queued.';
$string['setting_sats_per_point'] = 'Sats per point';
$string['setting_sats_per_point_desc'] = 'How many satoshis each point is worth at payout time.';
$string['setting_max_attempts'] = 'Max payment attempts';
$string['setting_max_attempts_desc'] = 'Maximum number of times the payout queue worker will retry a failed payment.';
$string['setting_payment_service_url'] = 'Payment service URL';
$string['setting_payment_service_url_desc'] = 'Base URL of the internal Lightning payment microservice.';
$string['setting_payment_service_secret'] = 'Payment service secret';
$string['setting_payment_service_secret_desc'] = 'Shared secret sent to the payment service via the X-Internal-Token header.';
$string['setting_points_course_completed'] = 'Points for course completion';
$string['setting_points_course_completed_desc'] = 'Points awarded when a student completes a course.';
$string['setting_points_quiz_passed'] = 'Points for passing a quiz';
$string['setting_points_quiz_passed_desc'] = 'Points awarded when a student is graded on an item at or above the pass mark.';
$string['setting_points_badge_awarded'] = 'Points for earning a badge';
$string['setting_points_badge_awarded_desc'] = 'Points awarded when a student is granted a badge.';

$string['task_process_payout_queue'] = 'Process Bitcoin rewards payout queue';

$string['error_source_unavailable'] = 'Points source "{$a}" is not available.';
$string['error_payment_http'] = 'Payment service HTTP error: {$a}';
