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
$string['setting_min_payout_usd'] = 'Minimum payout (USD)';
$string['setting_min_payout_usd_desc'] = 'Smallest amount, in USD, a student must accumulate before they can claim. Decimal dollars: 0.50 = $0.50, 1 = $1.00, 5 = $5.00. The Lightning rail accepts any amount above this. Onchain Bitcoin payouts are subject to an additional live minimum set by the swap provider (currently around $19–$20); students whose claim is below that floor will see onchain marked unavailable.';
$string['setting_usd_per_point'] = 'USD per point';
$string['setting_usd_per_point_desc'] = 'USD value of each point (decimal dollars). Examples: 0.01 = $0.01 per point, 0.10 = $0.10 per point, 1 = $1.00 per point. BTC is computed from this at claim time using the live BTC/USD rate.';
$string['setting_max_attempts'] = 'Max payment attempts';
$string['setting_max_attempts_desc'] = 'Maximum number of times the payout queue worker will retry a failed payment.';
$string['setting_payment_service_url'] = 'Payment service URL';
$string['setting_payment_service_url_desc'] = 'Base URL of the internal Lightning payment microservice.';
$string['setting_payment_service_secret'] = 'Payment service secret';
$string['setting_payment_service_secret_desc'] = 'Shared secret sent to the payment service via the X-Internal-Token header.';
$string['setting_webhook_secret'] = 'Webhook secret';
$string['setting_webhook_secret_desc'] = 'Shared secret used to verify the X-Webhook-Signature (HMAC-SHA256) header on inbound payout notifications. Must match WEBHOOK_SECRET on the payment service.';
$string['setting_webhook_url'] = 'Webhook URL';
$string['setting_webhook_url_desc'] = 'Configure the payment service\'s MOODLE_WEBHOOK_URL to POST terminal payout events to: <code>{$a}</code>';
$string['setting_points_course_completed'] = 'Points for course completion';
$string['setting_points_course_completed_desc'] = 'Points awarded when a user completes the course. Set per course on Course → More → Bitcoin rewards.';

$string['task_process_payout_queue'] = 'Process Bitcoin rewards payout queue';

$string['error_source_unavailable'] = 'Points source "{$a}" is not available.';
$string['error_payment_http'] = 'Payment service HTTP error: {$a}';

$string['course_config_nav'] = 'Bitcoin rewards';
$string['course_config_title'] = 'Bitcoin rewards for this course';
$string['course_config_enabled'] = 'Enable rewards for this course';
$string['course_config_enabled_help'] = 'When unchecked, no points are awarded for activity in this course. Rewards are opt-in per course.';
$string['course_config_override'] = 'Points awarded';
$string['course_config_override_help'] = 'How many points this course awards for the event. Leave empty (or 0) to award nothing for this event in this course.';
$string['course_config_must_be_int'] = 'Must be a non-negative integer or left empty.';
$string['course_config_quizzes_heading'] = 'Per-quiz rewards';
$string['course_config_quizzes_help'] = 'Points to award when a user passes each graded item. Leave empty (or 0) for no reward.';
$string['course_config_quizzes_empty'] = 'No graded items in this course yet.';
$string['course_config_badges_heading'] = 'Per-badge rewards';
$string['course_config_badges_help'] = 'Points to award when a user earns each course badge. Leave empty (or 0) for no reward. Site-wide badges are not eligible.';
$string['course_config_badges_empty'] = 'No course badges defined yet.';
$string['course_config_claim_mode'] = 'Claim mode';
$string['course_config_claim_mode_help'] = 'How payouts are triggered for points earned in this course. Admin approval is the safer default — students submit but an administrator must approve before the payment is sent.';
$string['course_config_claim_mode_self'] = 'Student self-service (claim is sent to the payment service immediately)';
$string['course_config_claim_mode_admin_approval'] = 'Admin approval required (claim waits until an administrator approves)';

$string['admin_claim_title'] = 'Bitcoin Rewards: payouts';
$string['admin_claim_pending_heading'] = 'Awaiting admin approval';
$string['admin_claim_pending_empty'] = 'No pending claims.';
$string['admin_claim_initiate_heading'] = 'Trigger payout for a user';
$string['admin_claim_initiate_empty'] = 'No users currently have unclaimed points above the threshold.';
$string['admin_claim_col_user'] = 'User';
$string['admin_claim_col_amount'] = 'Amount';
$string['admin_claim_col_unclaimed'] = 'Unclaimed';
$string['admin_claim_col_destination'] = 'Destination';
$string['admin_claim_col_when'] = 'Submitted';
$string['admin_claim_col_actions'] = 'Actions';
$string['admin_claim_approve'] = 'Approve';
$string['admin_claim_reject'] = 'Reject';
$string['admin_claim_pay'] = 'Pay out';
$string['admin_claim_approved'] = 'Claim approved — the payment will be sent on the next cron tick.';
$string['admin_claim_rejected'] = 'Claim rejected — the points were returned to the user.';
$string['admin_claim_submitted'] = 'Payment queued.';
$string['approval_not_pending'] = 'This claim is not in pending-approval state.';
$string['payout_status_pending_approval'] = 'Awaiting admin approval';

$string['my_title'] = 'My Bitcoin rewards';
$string['my_nav'] = 'My Bitcoin rewards';
$string['my_total_points'] = 'Total points earned';
$string['my_unclaimed_points'] = 'Unclaimed points';
$string['my_threshold'] = 'Claim threshold';
$string['my_usd_per_point'] = 'USD per point';
$string['my_current_rate'] = 'BTC/USD';
$string['my_claim_value'] = 'You can claim';
$string['my_claim_value_sats'] = '{$a} sats';
$string['my_rate_unavailable'] = 'Cannot fetch the BTC/USD rate right now — claims are paused. Details: {$a}';
$string['error_rate_unavailable'] = 'BTC/USD rate unavailable: {$a}';
$string['my_below_threshold'] = 'Keep learning — you need at least ${$a} of unclaimed rewards to claim.';
$string['my_onchain_available'] = 'Onchain Bitcoin (bc1…) is available for this claim — minimum {$a}.';
$string['my_onchain_unavailable'] = 'Onchain Bitcoin not available — needs at least {$a}. Use a Lightning destination instead.';
$string['error_limits_unavailable'] = 'Payment service limits unavailable: {$a}';
$string['error_parse_unavailable'] = 'Could not validate destination: {$a}';
$string['claim_onchain_below_min'] = 'Onchain Bitcoin payouts require at least ${$a}. Your claim is smaller — use a Lightning invoice or LN address instead.';
$string['claim_bolt11_amount_mismatch'] = 'The Lightning invoice you pasted expects {$a->invoice} sats but your claim is for {$a->expected} sats. Generate a fresh invoice for exactly {$a->expected} sats, or paste a Lightning address (user@domain) which has no fixed amount.';
$string['my_payout_amount_label'] = 'You will receive';
$string['my_address_label'] = 'Where should we send it?';
$string['my_address_placeholder'] = 'bc1…, lnbc…, lno…, or user@domain';
$string['my_address_help'] = 'Paste one of:<ul class="mb-0 mt-1 ps-3"><li>An on-chain Bitcoin address (<code>bc1…</code>)</li><li>A Lightning invoice for <strong>exactly {$a} sats</strong> (<code>lnbc…</code>)</li><li>A Lightning offer (<code>lno…</code>)</li><li>A Lightning address (<code>user@domain</code>)</li></ul>';
$string['my_address_warning'] = '⚠ Payouts are final and cannot be reversed. Double-check before submitting.';
$string['my_claim_button'] = 'Claim rewards';
$string['my_history_heading'] = 'Points history';
$string['my_queue_heading'] = 'Payouts';
$string['my_col_when'] = 'When';
$string['my_col_event'] = 'Event';
$string['my_col_points'] = 'Points';
$string['my_col_sats'] = 'Sats';
$string['my_col_status'] = 'Status';
$string['my_col_destination'] = 'Destination';
$string['my_col_dest_type'] = 'Type';
$string['my_col_txid'] = 'Transaction';

$string['dest_type_onchain'] = 'Onchain';
$string['dest_type_ln_address'] = 'Lightning address';
$string['dest_type_bolt11'] = 'Bolt11 invoice';
$string['dest_type_bolt12'] = 'Bolt12 offer';
$string['dest_type_auto'] = 'Detecting…';

$string['payout_status_pending'] = 'Pending';
$string['payout_status_accepted'] = 'Accepted';
$string['payout_status_processing'] = 'Processing';
$string['payout_status_paid'] = 'Paid';
$string['payout_status_failed'] = 'Failed';
$string['my_progress_label'] = '{$a->current} / {$a->threshold} toward next claim';
$string['my_courses_heading'] = 'Points by course';
$string['my_course_unknown'] = 'Other';
$string['my_empty_history'] = 'No points earned yet.';

$string['component_course'] = 'Course completed';
$string['component_grade_items'] = 'Quiz passed';
$string['component_badge'] = 'Badge awarded';
$string['component_legacy'] = 'Legacy';
$string['component_manual'] = 'Manual award';
$string['my_retry_button'] = 'Try again with a new destination';
$string['my_retry_hint'] = 'This releases the points back into your balance so you can submit a new claim.';
$string['my_requeue_ok'] = 'Points released — submit a new claim below.';
$string['my_requeued_note'] = 'Points from this attempt were returned to your balance.';
$string['payout_status_requeued'] = 'Points returned';
$string['requeue_forbidden'] = 'You can only retry your own payouts.';
$string['requeue_not_failed'] = 'Only failed payouts can be retried.';
$string['claim_concurrent'] = 'Another claim for these points is already in progress. Refresh the page and try again.';
$string['my_col_claimed'] = 'Status';
$string['my_status_claimed'] = 'Claimed';
$string['my_status_unclaimed'] = 'Unclaimed';
$string['my_payout_rewards'] = 'Rewards included in this payout';
$string['my_empty_queue'] = 'No payouts yet.';
$string['my_claimed_ok'] = 'Payout queued. It will be processed on the next cron run.';

$string['claim_misconfigured'] = 'Rewards are not fully configured. Contact your site admin.';
$string['claim_below_threshold'] = 'You do not have enough unclaimed points to claim yet.';
$string['claim_no_destination'] = 'Please enter a Bitcoin address.';
