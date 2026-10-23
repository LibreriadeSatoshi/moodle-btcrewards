<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_btcrewards\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy coverage for reward history and payouts.
 *
 * @package local_btcrewards
 * @copyright 2026 local_btcrewards contributors
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {
    /** Discover points-only and payout-only users without including unrelated users. */
    public function test_discover_personal_data(): void {
        global $DB;
        $this->resetAfterTest();
        $pointsuser = $this->getDataGenerator()->create_user();
        $payoutuser = $this->getDataGenerator()->create_user();
        $emptyuser = $this->getDataGenerator()->create_user();
        $this->create_history($pointsuser->id);
        $this->create_history($payoutuser->id);
        $DB->delete_records('btcrewards_payout_items');
        $DB->delete_records('btcrewards_payout_queue', ['userid' => $pointsuser->id]);
        $DB->delete_records('btcrewards_points', ['userid' => $payoutuser->id]);

        foreach ([$pointsuser, $payoutuser] as $user) {
            $context = \context_user::instance($user->id);
            $this->assertEquals([$context->id], provider::get_contexts_for_userid($user->id)->get_contextids());
            $userlist = new userlist($context, 'local_btcrewards');
            provider::get_users_in_context($userlist);
            $this->assertEquals([$user->id], $userlist->get_userids());
        }
        $this->assertEmpty(provider::get_contexts_for_userid($emptyuser->id)->get_contextids());
        $userlist = new userlist(\context_system::instance(), 'local_btcrewards');
        provider::get_users_in_context($userlist);
        $this->assertEmpty($userlist->get_userids());
    }

    /** Export all records and their relationships only for approved user contexts. */
    public function test_export_user_data(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        [$pointsid, $payoutid] = $this->create_history($user->id);
        $this->create_history($other->id);
        $context = \context_user::instance($user->id);
        provider::export_user_data(new approved_contextlist($user, 'local_btcrewards', [\context_system::instance()->id]));
        $this->assertFalse(writer::with_context($context)->has_any_data());

        provider::export_user_data(new approved_contextlist($user, 'local_btcrewards', [$context->id]));
        $data = writer::with_context($context)->get_data([get_string('pluginname', 'local_btcrewards')]);
        $this->assertCount(1, $data->points);
        $this->assertEquals(500, $data->points[0]->points);
        $this->assertCount(1, $data->payouts);
        $this->assertSame('student@example.com', $data->payouts[0]->destination);
        $this->assertSame('transaction-reference', $data->payouts[0]->txid);
        $this->assertSame('payment-proof', $data->payouts[0]->preimage);
        $this->assertSame('Payment service error', $data->payouts[0]->last_error);
        $this->assertCount(1, $data->payout_items);
        $this->assertEquals($pointsid, $data->payout_items[0]->pointsid);
        $this->assertEquals($payoutid, $data->payout_items[0]->payoutid);
        $this->assertFalse(writer::with_context(\context_user::instance($other->id))->has_any_data());
    }

    /** Each deletion entry point honours approval and preserves other users' history. */
    public function test_delete_user_data(): void {
        global $DB;
        $this->resetAfterTest();
        foreach (['user', 'users', 'context'] as $method) {
            $user = $this->getDataGenerator()->create_user();
            $other = $this->getDataGenerator()->create_user();
            [$pointsid, $payoutid] = $this->create_history($user->id);
            [$otherpointsid, $otherpayoutid] = $this->create_history($other->id);
            $context = \context_user::instance($user->id);
            if ($method === 'user') {
                provider::delete_data_for_user(new approved_contextlist($user, 'local_btcrewards', []));
                $this->assertTrue($DB->record_exists('btcrewards_points', ['id' => $pointsid]));
                provider::delete_data_for_user(new approved_contextlist($user, 'local_btcrewards', [$context->id]));
            } else if ($method === 'users') {
                provider::delete_data_for_users(new approved_userlist($context, 'local_btcrewards', [$other->id]));
                $this->assertTrue($DB->record_exists('btcrewards_points', ['id' => $pointsid]));
                provider::delete_data_for_users(new approved_userlist($context, 'local_btcrewards', [$user->id]));
            } else {
                provider::delete_data_for_all_users_in_context(\context_system::instance());
                $this->assertTrue($DB->record_exists('btcrewards_points', ['id' => $pointsid]));
                provider::delete_data_for_all_users_in_context($context);
            }
            $this->assertFalse($DB->record_exists('btcrewards_points', ['id' => $pointsid]));
            $this->assertFalse($DB->record_exists('btcrewards_payout_queue', ['id' => $payoutid]));
            $this->assertFalse($DB->record_exists('btcrewards_payout_items', ['pointsid' => $pointsid]));
            $this->assertTrue($DB->record_exists('btcrewards_points', ['id' => $otherpointsid]));
            $this->assertTrue($DB->record_exists('btcrewards_payout_queue', ['id' => $otherpayoutid]));
            $this->assertTrue($DB->record_exists('btcrewards_payout_items', ['pointsid' => $otherpointsid]));
        }
    }

    /**
     * Create a reward, payout, and their association.
     * @param int $userid Owner of the records.
     * @return array Point and payout identifiers.
     */
    private function create_history(int $userid): array {
        global $DB;
        $pointsid = $DB->insert_record('btcrewards_points', (object)[
            'userid' => $userid, 'courseid' => 0, 'points' => 500,
            'component' => 'legacy', 'itemid' => 1, 'timecreated' => 1700000000,
        ]);
        $payoutid = $DB->insert_record('btcrewards_payout_queue', (object)[
            'userid' => $userid, 'usd_cents' => 500, 'btc_usd_rate' => 6000000, 'sats' => 8333,
            'destination' => 'student@example.com', 'dest_type' => 'ln_address', 'status' => 'failed',
            'txid' => 'transaction-reference', 'preimage' => 'payment-proof', 'attempts' => 2,
            'timecreated' => 1700000000, 'timemodified' => 1700000001, 'last_error' => 'Payment service error',
        ]);
        $DB->insert_record('btcrewards_payout_items', (object)['payoutid' => $payoutid, 'pointsid' => $pointsid]);
        return [$pointsid, $payoutid];
    }
}
