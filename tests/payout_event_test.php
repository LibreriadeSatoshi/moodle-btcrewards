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
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for applying the payout service's webhook events.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(payout_event::class)]
final class payout_event_test extends \advanced_testcase {
    /**
     * Insert a payout with the given status and tx id.
     *
     * @param string $status
     * @return \stdClass The row.
     */
    private function payout(string $status): \stdClass {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $id = $DB->insert_record('btcrewards_payout_queue', (object) [
            'userid' => $user->id, 'usd_cents' => 100, 'btc_usd_rate' => 6000000, 'sats' => 1667,
            'destination' => 'student@pay.ismos.xyz', 'dest_type' => 'ln_address', 'status' => $status,
            'txid' => 'tx-1', 'attempts' => 1, 'timecreated' => time(), 'timemodified' => time(),
        ]);
        return $DB->get_record('btcrewards_payout_queue', ['id' => $id]);
    }

    /**
     * Reload a payout.
     *
     * @param \stdClass $row
     * @return \stdClass
     */
    private function reload(\stdClass $row): \stdClass {
        global $DB;
        return $DB->get_record('btcrewards_payout_queue', ['id' => $row->id]);
    }

    public function test_unknown_tx_id_is_404_so_the_service_retries(): void {
        $this->resetAfterTest();
        [$code, $body] = payout_event::apply('tx-unknown', 'settled');

        $this->assertSame(404, $code);
        $this->assertSame(['error' => 'tx_id not found', 'tx_id' => 'tx-unknown'], $body);
    }

    /**
     * Status before the event, event, expected status after.
     *
     * @return array[]
     */
    public static function transitions(): array {
        return [
            'accepted, settled' => [payout_status::ACCEPTED, 'settled', payout_status::PAID],
            'accepted, failed' => [payout_status::ACCEPTED, 'failed', payout_status::FAILED],
            'pending, settled' => [payout_status::PENDING, 'settled', payout_status::PAID],
            'failed, settled: the money went out' => [payout_status::FAILED, 'settled', payout_status::PAID],
            'paid, failed: never undone' => [payout_status::PAID, 'failed', payout_status::PAID],
            'paid, settled again' => [payout_status::PAID, 'settled', payout_status::PAID],
            'failed, failed again' => [payout_status::FAILED, 'failed', payout_status::FAILED],
            'requeued, failed: points stay returned' => [payout_status::REQUEUED, 'failed', payout_status::REQUEUED],
        ];
    }

    #[DataProvider('transitions')]
    public function test_transition(string $before, string $event, string $after): void {
        $this->resetAfterTest();
        $row = $this->payout($before);

        [$code] = payout_event::apply('tx-1', $event);

        $this->assertSame(200, $code);
        $this->assertSame($after, $this->reload($row)->status);
    }

    public function test_settled_after_failed_records_why(): void {
        $this->resetAfterTest();
        $row = $this->payout(payout_status::FAILED);

        [, $body] = payout_event::apply('tx-1', 'settled', 'preimage-1');

        $row = $this->reload($row);
        $this->assertTrue($body['settled_after_failure']);
        $this->assertSame('preimage-1', $row->preimage);
        $this->assertStringContainsString('then settled', $row->last_error);
    }

    public function test_settled_after_requeue_is_flagged_for_a_double_payment_check(): void {
        $this->resetAfterTest();
        $row = $this->payout(payout_status::REQUEUED);

        [$code, $body] = payout_event::apply('tx-1', 'settled');

        $row = $this->reload($row);
        $this->assertSame(200, $code);
        $this->assertSame('settled after requeue', $body['warning']);
        $this->assertSame(payout_status::REQUEUED, $row->status);
        $this->assertStringContainsString('double payment', $row->last_error);
    }
}
