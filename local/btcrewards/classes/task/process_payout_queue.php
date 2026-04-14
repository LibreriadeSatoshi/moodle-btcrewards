<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Scheduled task that drains the payout queue.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards\task;

use local_btcrewards\payout_engine;

defined('MOODLE_INTERNAL') || die();

/**
 * Cron entry point for payout processing.
 */
class process_payout_queue extends \core\task\scheduled_task {
    /**
     * Localised task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_process_payout_queue', 'local_btcrewards');
    }

    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute() {
        (new payout_engine())->process_queue();
    }
}
