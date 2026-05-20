<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Plugin-side status vocabulary for btcrewards_payout_queue rows.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards;

defined('MOODLE_INTERNAL') || die();

/**
 * State machine values for the payout queue. The legal transitions are:
 *
 *   PENDING_APPROVAL → PENDING   (admin approved)
 *   PENDING_APPROVAL → REQUEUED  (admin rejected; points returned)
 *   PENDING          → ACCEPTED  (cron submitted to service, awaiting webhook)
 *   PENDING          → PAID      (rare: service settled synchronously)
 *   PENDING          → FAILED    (max attempts hit, or non-retryable error)
 *   ACCEPTED         → PAID      (settlement webhook arrived)
 *   ACCEPTED         → FAILED    (failure webhook arrived)
 *   FAILED           → REQUEUED  (user clicked "try again", points returned)
 */
final class payout_status {
    /** Submitted by user, held until an admin approves. */
    public const PENDING_APPROVAL = 'pending_approval';

    /** Awaiting cron pickup; points are reserved on the row. */
    public const PENDING  = 'pending';

    /** Submitted to the service, awaiting the webhook's terminal verdict. */
    public const ACCEPTED = 'accepted';

    /** Terminal success — sats sent, preimage stored. */
    public const PAID     = 'paid';

    /** Terminal failure — points stay locked unless the user requeues. */
    public const FAILED   = 'failed';

    /** User-initiated unwind of a FAILED row; points are back in the balance. */
    public const REQUEUED = 'requeued';
}
