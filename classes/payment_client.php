<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * HTTP client for the internal payment microservice.
 *
 * The service classifies the destination itself (bolt11/bolt12/ln_address/onchain)
 * and pushes terminal state back via webhook — the plugin no longer polls.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards;

defined('MOODLE_INTERNAL') || die();

/**
 * Raised when the payment microservice returns a non-2xx HTTP response.
 */
class payment_exception extends \moodle_exception {
}

/**
 * REST client wrapping the payment microservice POST /pay contract.
 */
class payment_client {
    /** @var string */
    private $baseurl;

    /** @var string */
    private $secret;

    /** Initial state — submitted, nothing observable yet. */
    public const STATUS_ACCEPTED = 'accepted';

    /** In flight on Lightning or broadcast awaiting confirmations. */
    public const STATUS_PROCESSING = 'processing';

    /** Final, confirmed. */
    public const STATUS_SETTLED = 'settled';

    /** Terminal failure. */
    public const STATUS_FAILED = 'failed';

    public function __construct() {
        $this->baseurl = rtrim((string) get_config('local_btcrewards', 'payment_service_url'), '/');
        $this->secret  = (string) get_config('local_btcrewards', 'payment_service_secret');
    }

    /**
     * Submit a new payment. The service auto-detects the destination type.
     * Terminal state arrives later via the webhook, not via polling.
     *
     * Never throws — transport / HTTP errors are reported as status=failed with
     * a `retryable` flag so the caller can decide whether to retry.
     *
     * @param int    $sats
     * @param string $destination  Bitcoin address, bolt11, bolt12 offer, or LN address.
     * @return array{tx_id: string, status: string, dest_type: string, error: string, retryable: bool}
     */
    public function pay(int $sats, string $destination): array {
        $payload = json_encode([
            'amount_sats' => $sats,
            'destination' => $destination,
        ]);

        [$code, $raw] = $this->request('POST', '/pay', $payload);

        // HTTP 4xx from the service = permanent (bad destination, bad amount).
        // HTTP 5xx or transport error (code 0) = transient, worth retrying.
        if ($code < 200 || $code >= 300) {
            $detail = '';
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded)) {
                $detail = (string) ($decoded['detail'] ?? $decoded['error'] ?? '');
            }
            return [
                'tx_id'     => '',
                'status'    => self::STATUS_FAILED,
                'dest_type' => '',
                'error'     => "HTTP $code: $detail",
                'retryable' => ($code === 0 || $code >= 500),
            ];
        }

        $response = json_decode((string) $raw, true) ?: [];
        return [
            'tx_id'     => (string) ($response['tx_id'] ?? ''),
            'status'    => (string) ($response['status'] ?? ''),
            'dest_type' => (string) ($response['dest_type'] ?? ''),
            'error'     => (string) ($response['error'] ?? ''),
            // Default to retryable=true if the service omits the flag, so older
            // service builds stay compatible.
            'retryable' => (bool) ($response['retryable'] ?? true),
        ];
    }

    /**
     * Fetch the current BTC/USD rate in cents per BTC from the payment service.
     *
     * @return int
     * @throws \moodle_exception If the service is unreachable or can't resolve a rate.
     */
    public function fetch_rate(): int {
        $body = $this->get_json('/rate', 'error_rate_unavailable');
        $cents = (int) ($body['cents_per_btc'] ?? 0);
        if ($cents <= 0) {
            throw new \moodle_exception('error_rate_unavailable', 'local_btcrewards', '', 'no rate in body');
        }
        return $cents;
    }

    /**
     * Fetch current per-rail send limits in sats.
     *
     * @return array{onchain_min: int, lightning_min: int}
     * @throws \moodle_exception If the service is unreachable.
     */
    public function fetch_limits(): array {
        $body = $this->get_json('/limits', 'error_limits_unavailable');
        return [
            'onchain_min'   => (int) ($body['onchain_send']['min_sat'] ?? 0),
            'lightning_min' => (int) ($body['lightning_send']['min_sat'] ?? 0),
        ];
    }

    /**
     * GET a JSON endpoint and return the decoded body. Centralises the
     * request → 2xx → decode dance shared by every read endpoint.
     *
     * @param string $errkey Lang string used when the response is non-2xx.
     * @return array Decoded body (empty array if not JSON-parseable).
     * @throws \moodle_exception on non-2xx response.
     */
    private function get_json(string $path, string $errkey): array {
        [$code, $raw] = $this->request('GET', $path, null);
        if ($code < 200 || $code >= 300) {
            throw new \moodle_exception($errkey, 'local_btcrewards', '', "HTTP $code");
        }
        return json_decode((string) $raw, true) ?: [];
    }

    /**
     * @return array{0: int, 1: string} HTTP code and raw body.
     */
    private function request(string $method, string $path, ?string $body): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        if ($this->baseurl === '') {
            return [0, ''];
        }

        // ignoresecurity: the payment service URL is an admin-configured internal
        // endpoint and usually resolves to localhost/LAN, which Moodle's default
        // SSRF blocklist would reject.
        $curl = new \curl(['ignoresecurity' => true]);
        $curl->setopt([
            'CURLOPT_CONNECTTIMEOUT_MS' => 2000,
            'CURLOPT_TIMEOUT_MS'        => 3000,
        ]);
        $curl->setHeader([
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Internal-Token: ' . $this->secret,
        ]);

        $url = $this->baseurl . $path;
        if ($method === 'POST') {
            $raw = $curl->post($url, $body ?? '');
        } else {
            $raw = $curl->get($url);
        }

        $info = $curl->get_info();
        $code = (int) ($info['http_code'] ?? 0);
        return [$code, (string) $raw];
    }
}
