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
 * Tests for draining the payout queue.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(payout_engine::class)]
final class payout_engine_test extends \advanced_testcase {
    /**
     * A client that answers pay() with the given results in order and records each call.
     *
     * @param array[] $results Results pay() returns, one per call.
     * @return payment_client
     */
    private function scripted_client(array $results): payment_client {
        return new class ($results) extends payment_client {
            /** @var array[] Each call as [sats, destination, payout key]. */
            public array $calls = [];

            /**
             * @param array[] $results Results pay() returns, one per call.
             */
            public function __construct(
                /** @var array[] Results pay() returns, one per call. */
                private array $results
            ) {
                parent::__construct();
            }

            public function pay(int $sats, string $destination, string $payoutkey): array {
                $this->calls[] = [$sats, $destination, $payoutkey];
                return array_shift($this->results) + ['tx_id' => '', 'dest_type' => '', 'error' => '', 'transient' => false];
            }
        };
    }

    /**
     * Insert a pending payout and return its id.
     *
     * @param int $attempts
     * @return int
     */
    private function pending_payout(int $attempts = 0): int {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        return (int) $DB->insert_record('btcrewards_payout_queue', (object) [
            'userid' => $user->id, 'usd_cents' => 100, 'btc_usd_rate' => 6000000, 'sats' => 1667,
            'destination' => 'student@pay.ismos.xyz', 'dest_type' => '', 'status' => payout_status::PENDING,
            'attempts' => $attempts, 'timecreated' => time(), 'timemodified' => time(),
        ]);
    }

    /**
     * Run one cron pass with the given client.
     *
     * @param payment_client $client
     */
    private function drain(payment_client $client): void {
        (new payout_engine())->process_queue($client);
    }

    public function test_every_attempt_sends_the_same_key(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('max_attempts', 5, 'local_btcrewards');
        $id = $this->pending_payout();
        $client = $this->scripted_client([
            ['status' => payment_client::STATUS_FAILED, 'retryable' => true, 'error' => 'no route'],
            ['status' => payment_client::STATUS_PROCESSING, 'retryable' => true, 'tx_id' => 'tx-1'],
        ]);

        $this->drain($client);
        $this->drain($client);

        $this->assertSame(payment_client::payout_key($id), $client->calls[0][2]);
        $this->assertSame($client->calls[0][2], $client->calls[1][2]);
        $row = $DB->get_record('btcrewards_payout_queue', ['id' => $id]);
        $this->assertSame(payout_status::ACCEPTED, $row->status);
        $this->assertSame('tx-1', $row->txid);
        $this->assertEquals(2, $row->attempts);
    }

    public function test_transient_failure_keeps_the_payout_pending_without_spending_an_attempt(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('max_attempts', 1, 'local_btcrewards');
        $id = $this->pending_payout();
        $client = $this->scripted_client([
            ['status' => payment_client::STATUS_FAILED, 'retryable' => true, 'transient' => true, 'error' => 'HTTP 0: '],
            ['status' => payment_client::STATUS_FAILED, 'retryable' => true, 'transient' => true, 'error' => 'HTTP 429: cap'],
        ]);

        $this->drain($client);
        $this->drain($client);

        $row = $DB->get_record('btcrewards_payout_queue', ['id' => $id]);
        $this->assertSame(payout_status::PENDING, $row->status);
        $this->assertEquals(0, $row->attempts);
        $this->assertSame('HTTP 429: cap', $row->last_error);
        $this->assertCount(2, $client->calls);
    }

    public function test_service_failure_fails_the_payout_after_the_last_attempt(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('max_attempts', 2, 'local_btcrewards');
        $id = $this->pending_payout(1);
        $client = $this->scripted_client([
            ['status' => payment_client::STATUS_FAILED, 'retryable' => true, 'error' => 'no route'],
        ]);

        $this->drain($client);

        $row = $DB->get_record('btcrewards_payout_queue', ['id' => $id]);
        $this->assertSame(payout_status::FAILED, $row->status);
        $this->assertSame('no route', $row->last_error);
    }

    public function test_final_failure_fails_the_payout_at_once(): void {
        global $DB;
        $this->resetAfterTest();
        set_config('max_attempts', 5, 'local_btcrewards');
        $id = $this->pending_payout();
        $client = $this->scripted_client([
            ['status' => payment_client::STATUS_FAILED, 'retryable' => false, 'tx_id' => 'tx-1', 'error' => ''],
        ]);

        $this->drain($client);

        $this->assertSame(payout_status::FAILED, $DB->get_field('btcrewards_payout_queue', 'status', ['id' => $id]));
    }
}
