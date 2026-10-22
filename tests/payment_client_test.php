<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_btcrewards;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the payment service client.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(payment_client::class)]
final class payment_client_test extends \advanced_testcase {
    /**
     * A client that records the requests it would send and answers each with $response.
     *
     * @param array $response [HTTP code, raw body]
     * @return payment_client
     */
    private function recording_client(array $response = [200, '{"tx_id": "tx-1", "status": "processing"}']): payment_client {
        return new class ($response) extends payment_client {
            /** @var array[] Each request as [method, path, decoded body]. */
            public array $requests = [];

            /**
             * @param array $response [HTTP code, raw body]
             */
            public function __construct(
                /** @var array [HTTP code, raw body] */
                private array $response
            ) {
                parent::__construct();
            }

            protected function request(string $method, string $path, ?string $body, int $timeoutms = 3000): array {
                $this->requests[] = [$method, $path, json_decode((string) $body, true)];
                return $this->response;
            }
        };
    }

    public function test_pay_sends_the_payout_key(): void {
        $this->resetAfterTest();
        $client = $this->recording_client();

        $result = $client->pay(150, 'student@pay.ismos.xyz', payment_client::payout_key(42));

        $this->assertCount(1, $client->requests);
        [$method, $path, $body] = $client->requests[0];
        $this->assertSame('POST', $method);
        $this->assertSame('/pay', $path);
        $this->assertSame([
            'payout_id'   => get_site_identifier() . ':42',
            'amount_sats' => 150,
            'destination' => 'student@pay.ismos.xyz',
        ], $body);
        $this->assertSame('tx-1', $result['tx_id']);
    }

    public function test_payout_keys_differ_per_row(): void {
        $this->resetAfterTest();
        $this->assertNotSame(payment_client::payout_key(1), payment_client::payout_key(2));
        $this->assertStringStartsWith(get_site_identifier() . ':', payment_client::payout_key(1));
    }

    public function test_non_positive_amounts_are_never_sent(): void {
        $this->resetAfterTest();
        $client = $this->recording_client();

        foreach ([0, -10] as $sats) {
            $result = $client->pay($sats, 'student@pay.ismos.xyz', payment_client::payout_key(1));
            $this->assertSame(payment_client::STATUS_FAILED, $result['status']);
            $this->assertFalse($result['retryable']);
        }
        $this->assertSame([], $client->requests);
    }

    public function test_unknown_outcome_from_the_service_is_not_retryable(): void {
        $this->resetAfterTest();
        $client = $this->recording_client([200, json_encode([
            'tx_id' => '', 'status' => 'failed', 'dest_type' => 'ln_address',
            'error' => 'send outcome unknown', 'retryable' => false,
        ])]);

        $result = $client->pay(150, 'student@pay.ismos.xyz', payment_client::payout_key(7));

        $this->assertSame(payment_client::STATUS_FAILED, $result['status']);
        $this->assertFalse($result['retryable']);
    }

    public function test_conflict_from_the_service_is_not_retryable(): void {
        $this->resetAfterTest();
        $client = $this->recording_client([409, '{"detail": "payout_id was already used for a different payment"}']);

        $result = $client->pay(150, 'student@pay.ismos.xyz', payment_client::payout_key(7));

        $this->assertFalse($result['retryable']);
        $this->assertFalse($result['transient']);
        $this->assertStringContainsString('409', $result['error']);
    }

    public function test_unreachable_busy_or_capped_service_is_transient(): void {
        $this->resetAfterTest();
        foreach ([0, 429, 502, 503] as $code) {
            $client = $this->recording_client([$code, '{"detail": "x"}']);
            $result = $client->pay(150, 'student@pay.ismos.xyz', payment_client::payout_key(7));
            $this->assertTrue($result['transient'], "HTTP $code");
            $this->assertTrue($result['retryable'], "HTTP $code");
        }
    }

    public function test_service_answer_is_not_transient(): void {
        $this->resetAfterTest();
        $client = $this->recording_client([200, '{"tx_id": "", "status": "failed", "retryable": true}']);
        $result = $client->pay(150, 'student@pay.ismos.xyz', payment_client::payout_key(7));
        $this->assertFalse($result['transient']);
        $this->assertTrue($result['retryable']);
    }
}
