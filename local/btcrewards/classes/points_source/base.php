<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Points source interface.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards\points_source;

defined('MOODLE_INTERNAL') || die();

/**
 * Contract implemented by any pluggable points backend.
 */
interface base {
    /**
     * Return the total lifetime points for a user.
     *
     * @param int $userid
     * @return int
     */
    public function get_points(int $userid): int;

    /**
     * Return the total unclaimed points for a user (not yet linked to any payout).
     *
     * @param int $userid
     * @return int
     */
    public function get_unclaimed_points(int $userid): int;

    /**
     * Return the IDs of all unclaimed point rows for a user.
     *
     * @param int $userid
     * @return int[]
     */
    public function get_unclaimed_point_ids(int $userid): array;

    /**
     * Check whether this source's dependencies are satisfied and usable.
     *
     * @return bool
     */
    public function is_available(): bool;

    /**
     * Human-readable name for display in admin screens.
     *
     * @return string
     */
    public function get_name(): string;
}
