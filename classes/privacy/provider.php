<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_btcrewards\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for reward histories and payouts.
 *
 * Payouts can combine points from several courses, so the complete ledger is
 * owned by the user context, including legacy points without a course.
 *
 * @package local_btcrewards
 * @copyright 2026 local_btcrewards contributors
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Describe personal records and the data sent to the payment service.
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('btcrewards_points', [
            'userid' => 'privacy:metadata:userid',
            'courseid' => 'privacy:metadata:courseid',
            'points' => 'privacy:metadata:points',
            'component' => 'privacy:metadata:component',
            'itemid' => 'privacy:metadata:itemid',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:points_table');
        $collection->add_database_table('btcrewards_payout_queue', [
            'userid' => 'privacy:metadata:userid',
            'usd_cents' => 'privacy:metadata:usd_cents',
            'btc_usd_rate' => 'privacy:metadata:btc_usd_rate',
            'sats' => 'privacy:metadata:sats',
            'destination' => 'privacy:metadata:destination',
            'dest_type' => 'privacy:metadata:dest_type',
            'status' => 'privacy:metadata:status',
            'txid' => 'privacy:metadata:txid',
            'preimage' => 'privacy:metadata:preimage',
            'attempts' => 'privacy:metadata:attempts',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
            'last_error' => 'privacy:metadata:last_error',
        ], 'privacy:metadata:payouts_table');
        $collection->add_database_table('btcrewards_payout_items', [
            'payoutid' => 'privacy:metadata:payoutid',
            'pointsid' => 'privacy:metadata:pointsid',
        ], 'privacy:metadata:items_table');
        $collection->add_external_location_link('payment_service', [
            'amount_sats' => 'privacy:metadata:sats',
            'destination' => 'privacy:metadata:destination',
        ], 'privacy:metadata:payment_service');
        $collection->link_subsystem('core_user', 'privacy:metadata:core_user');
        return $collection;
    }

    /**
     * Find the user context when either rewards or payouts exist.
     * @param int $userid User identifier.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $contextlist = new contextlist();
        if ($DB->record_exists('btcrewards_points', ['userid' => $userid]) ||
                $DB->record_exists('btcrewards_payout_queue', ['userid' => $userid])) {
            $contextlist->add_user_context($userid);
        }
        return $contextlist;
    }

    /**
     * Identify the owner of personal data in a user context.
     * @param userlist $userlist Context and discovered users.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \context_user) {
            return;
        }
        foreach (['btcrewards_points', 'btcrewards_payout_queue'] as $table) {
            $userlist->add_from_sql('userid', "SELECT userid FROM {{$table}} WHERE userid = :userid",
                ['userid' => $context->instanceid]);
        }
    }

    /**
     * Export the approved user's points, payouts, and their associations.
     * @param approved_contextlist $contextlist Approved user contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        $context = \context_user::instance($userid);
        if (!in_array($context->id, $contextlist->get_contextids())) {
            return;
        }
        $points = $DB->get_records('btcrewards_points', ['userid' => $userid], 'id');
        $payouts = $DB->get_records('btcrewards_payout_queue', ['userid' => $userid], 'id');
        if (!$points && !$payouts) {
            return;
        }
        foreach ($points as $point) {
            $point->timecreated = transform::datetime($point->timecreated);
        }
        foreach ($payouts as $payout) {
            $payout->timecreated = transform::datetime($payout->timecreated);
            $payout->timemodified = transform::datetime($payout->timemodified);
        }
        $items = $DB->get_records_sql("SELECT i.*
                                        FROM {btcrewards_payout_items} i
                                        JOIN {btcrewards_payout_queue} q ON q.id = i.payoutid
                                       WHERE q.userid = :userid
                                    ORDER BY i.id", ['userid' => $userid]);
        writer::with_context($context)->export_data([get_string('pluginname', 'local_btcrewards')], (object)[
            'points' => array_values($points),
            'payouts' => array_values($payouts),
            'payout_items' => array_values($items),
        ]);
    }

    /**
     * Delete records belonging to this user context only.
     * @param \context $context Approved context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if ($context instanceof \context_user) {
            self::delete_user_data($context->instanceid);
        }
    }

    /**
     * Delete a user's records only when their user context is approved.
     * @param approved_contextlist $contextlist Approved user contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        $userid = $contextlist->get_user()->id;
        $context = \context_user::instance($userid);
        if (in_array($context->id, $contextlist->get_contextids())) {
            self::delete_user_data($userid);
        }
    }

    /**
     * Delete only an approved owner of this user context.
     * @param approved_userlist $userlist Approved users and context.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context instanceof \context_user && in_array($context->instanceid, $userlist->get_userids())) {
            self::delete_user_data($context->instanceid);
        }
    }

    /**
     * Remove associations before their parent records, atomically.
     * @param int $userid Record owner.
     */
    private static function delete_user_data(int $userid): void {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records_select('btcrewards_payout_items',
            'payoutid IN (SELECT id FROM {btcrewards_payout_queue} WHERE userid = :payoutuserid)
             OR pointsid IN (SELECT id FROM {btcrewards_points} WHERE userid = :pointsuserid)',
            ['payoutuserid' => $userid, 'pointsuserid' => $userid]);
        $DB->delete_records('btcrewards_payout_queue', ['userid' => $userid]);
        $DB->delete_records('btcrewards_points', ['userid' => $userid]);
        $transaction->allow_commit();
    }
}
