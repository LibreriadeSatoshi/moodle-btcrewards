<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * HTTP client for the internal Lightning payment microservice.
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
 * REST client wrapping the payment microservice contract.
 */
class payment_client {
    /** @var string */
    private $baseurl;

    /** @var string */
    private $secret;

    /**
     * Construct the client from plugin configuration.
     */
    public function __construct() {
        $this->baseurl = rtrim((string) get_config('local_btcrewards', 'payment_service_url'), '/');
        $this->secret  = (string) get_config('local_btcrewards', 'payment_service_secret');
    }

    /**
     * Request a payout from the payment service.
     *
     * @param int    $userid       Moodle user id (for logging by caller).
     * @param int    $sats         Amount to pay in satoshis.
     * @param string $destination  Destination address/invoice/LN address.
     * @param string $desttype     One of ln_address|onchain|bolt11.
     * @return array{success: bool, txid: string, error: string}
     */
    public function pay(int $userid, int $sats, string $destination, string $desttype): array {
        $payload = json_encode([
            'amount_sats' => $sats,
            'destination' => $destination,
            'dest_type'   => $desttype,
        ]);

        $response = $this->request('POST', '/pay', $payload);

        return [
            'success' => !empty($response['success']),
            'txid'    => (string) ($response['txid'] ?? ''),
            'error'   => (string) ($response['error'] ?? ''),
        ];
    }

    /**
     * Fetch the status of a previously submitted payment.
     *
     * @param string $txid
     * @return array{status: string, txid: string}
     */
    public function get_status(string $txid): array {
        $response = $this->request('GET', '/status/' . rawurlencode($txid), null);
        return [
            'status' => (string) ($response['status'] ?? ''),
            'txid'   => (string) ($response['txid'] ?? $txid),
        ];
    }

    /**
     * Perform an HTTP request using Moodle's curl wrapper.
     *
     * @param string      $method
     * @param string      $path
     * @param string|null $body
     * @return array
     * @throws payment_exception
     */
    private function request(string $method, string $path, ?string $body): array {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $curl = new \curl();
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
        if ($code < 200 || $code >= 300) {
            throw new payment_exception('error_payment_http', 'local_btcrewards', '', $code);
        }

        $decoded = json_decode((string) $raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
