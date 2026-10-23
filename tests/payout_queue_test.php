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
 * Tests for compare-and-set status changes and the admin actions built on them.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(payout_queue::class)]
#[CoversClass(payout_engine::class)]
final class payout_queue_test extends \advanced_testcase {
    /**
     * Insert a payout with one linked point and return its id.
     *
     * @param string $status
     * @return int
     */
    private function payout(string $status): int {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $id = (int) $DB->insert_record('btcrewards_payout_queue', (object) [
            'userid' => $user->id, 'usd_cents' => 100, 'btc_usd_rate' => 6000000, 'sats' => 1667,
            'destination' => 'student@pay.ismos.xyz', 'dest_type' => 'ln_address', 'status' => $status,
            'txid' => 'tx-' . $status, 'attempts' => 1, 'timecreated' => time(), 'timemodified' => time(),
        ]);
        $DB->insert_record('btcrewards_payout_items', (object) ['payoutid' => $id, 'pointsid' => $id * 100]);
        return $id;
    }

    /**
     * @param int $id
     * @return string
     */
    private function status_of(int $id): string {
        global $DB;
        return $DB->get_field('btcrewards_payout_queue', 'status', ['id' => $id]);
    }

    /**
     * @param int $id
     * @return int
     */
    private function items(int $id): int {
        global $DB;
        return $DB->count_records('btcrewards_payout_items', ['payoutid' => $id]);
    }

    /**
     * Run $fn with PHP's error_log redirected to a file and return what was logged.
     *
     * @param callable $fn
     * @return string
     */
    private function capture_error_log(callable $fn): string {
        $file = make_request_directory() . '/error.log';
        $previous = ini_set('error_log', $file);
        try {
            $fn();
        } finally {
            ini_set('error_log', $previous);
        }
        return is_readable($file) ? file_get_contents($file) : '';
    }

    public function test_transition_applies_from_the_expected_status(): void {
        global $DB;
        $this->resetAfterTest();
        $id = $this->payout(payout_status::FAILED);

        $this->assertTrue(payout_queue::transition($id, payout_status::FAILED, payout_status::PAID,
            ['preimage' => 'p1', 'last_error' => 'note']));

        $row = $DB->get_record('btcrewards_payout_queue', ['id' => $id]);
        $this->assertSame(payout_status::PAID, $row->status);
        $this->assertSame('p1', $row->preimage);
        $this->assertSame('note', $row->last_error);
    }

    public function test_transition_from_a_stale_status_changes_nothing(): void {
        $this->resetAfterTest();
        $id = $this->payout(payout_status::PAID);  // A webhook got here first.

        $this->assertFalse(payout_queue::transition($id, payout_status::FAILED, payout_status::REQUEUED));
        $this->assertSame(payout_status::PAID, $this->status_of($id));
    }

    public function test_release_returns_the_points_of_a_failed_payout(): void {
        $this->resetAfterTest();
        $id = $this->payout(payout_status::FAILED);

        (new payout_engine())->requeue_failed($id);

        $this->assertSame(payout_status::REQUEUED, $this->status_of($id));
        $this->assertSame(0, $this->items($id));
    }

    public function test_release_of_a_payout_that_is_no_longer_failed_keeps_its_points(): void {
        $this->resetAfterTest();
        $id = $this->payout(payout_status::PAID);

        try {
            (new payout_engine())->requeue_failed($id);
            $this->fail('A paid payout must not be released.');
        } catch (\moodle_exception $e) {
            $this->assertSame('requeue_not_failed', $e->errorcode);
        }
        $this->assertSame(payout_status::PAID, $this->status_of($id));
        $this->assertSame(1, $this->items($id));
    }

    public function test_reject_returns_the_points_and_approve_releases_to_the_queue(): void {
        $this->resetAfterTest();
        $rejected = $this->payout(payout_status::PENDING_APPROVAL);
        $approved = $this->payout(payout_status::PENDING_APPROVAL);

        (new payout_engine())->reject_pending($rejected);
        (new payout_engine())->approve_pending($approved);

        $this->assertSame(payout_status::REQUEUED, $this->status_of($rejected));
        $this->assertSame(0, $this->items($rejected));
        $this->assertSame(payout_status::PENDING, $this->status_of($approved));
        $this->assertSame(1, $this->items($approved));
    }

    public function test_settled_webhook_after_a_release_flags_instead_of_paying_again(): void {
        $this->resetAfterTest();
        $id = $this->payout(payout_status::FAILED);
        (new payout_engine())->requeue_failed($id);

        $log = $this->capture_error_log(function () use (&$code, &$body) {
            [$code, $body] = payout_event::apply('tx-failed', 'settled');
        });

        $this->assertStringContainsString('double payment', $log);
        $this->assertSame(200, $code);
        $this->assertSame('settled after requeue', $body['warning']);
        $this->assertSame(payout_status::REQUEUED, $this->status_of($id));
    }
}
