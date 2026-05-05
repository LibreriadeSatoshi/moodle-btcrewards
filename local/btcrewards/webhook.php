<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Inbound webhook from the BTC payout microservice.
 *
 * Receives terminal payment events (settled/failed) and updates the payout
 * queue. Authenticated via HMAC-SHA256 over the raw body using the shared
 * webhook_secret. Idempotent by tx_id — the service will retry until 2xx.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Public endpoint — no Moodle session, no login required. Don't define
// ABORT_AFTER_CONFIG here: Moodle checks it with `defined()`, not its value,
// so even defining it as false aborts before get_config() is available.
define('NO_MOODLE_COOKIES', true);
define('NO_DEBUG_DISPLAY', true);

// phpcs:disable moodle.Files.MoodleInternal.MoodleInternalGlobalState
require(__DIR__ . '/../../config.php');

header('Content-Type: application/json');

/**
 * Emit a JSON response and exit.
 *
 * @param int   $status HTTP status code.
 * @param array $body   Body payload.
 */
function local_btcrewards_webhook_respond(int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    local_btcrewards_webhook_respond(405, ['error' => 'method not allowed']);
}

$secret = (string) get_config('local_btcrewards', 'webhook_secret');
if ($secret === '') {
    local_btcrewards_webhook_respond(503, ['error' => 'webhook_secret not configured']);
}

$body = (string) file_get_contents('php://input');
$provided = (string) ($_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '');
$expected = 'sha256=' . hash_hmac('sha256', $body, $secret);

if (!hash_equals($expected, $provided)) {
    local_btcrewards_webhook_respond(401, ['error' => 'invalid signature']);
}

$payload = json_decode($body, true);
if (!is_array($payload)) {
    local_btcrewards_webhook_respond(400, ['error' => 'invalid json']);
}

$txid   = (string) ($payload['tx_id'] ?? '');
$status = (string) ($payload['status'] ?? '');
$preimage = isset($payload['preimage']) ? (string) $payload['preimage'] : '';

if ($txid === '' || !in_array($status, ['settled', 'failed'], true)) {
    local_btcrewards_webhook_respond(400, ['error' => 'missing tx_id or invalid status']);
}

global $DB;

$row = $DB->get_record('btcrewards_payout_queue', ['txid' => $txid]);
if (!$row) {
    // Webhook may arrive before our /pay response is persisted. Return 4xx so
    // the service retries with backoff; on the next attempt the row will exist.
    local_btcrewards_webhook_respond(404, ['error' => 'tx_id not found', 'tx_id' => $txid]);
}

// Idempotency: if already terminal, acknowledge without rewriting state.
if (in_array($row->status,
    [\local_btcrewards\payout_status::PAID, \local_btcrewards\payout_status::FAILED],
    true)) {
    local_btcrewards_webhook_respond(200, ['received' => true, 'duplicate' => true]);
}

$row->status = ($status === 'settled')
    ? \local_btcrewards\payout_status::PAID
    : \local_btcrewards\payout_status::FAILED;
$row->timemodified = time();
if ($preimage !== '') {
    $row->preimage = $preimage;
}
$DB->update_record('btcrewards_payout_queue', $row);

local_btcrewards_webhook_respond(200, ['received' => true]);
