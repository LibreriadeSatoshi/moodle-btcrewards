<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Points source factory.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards\points_source;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolves the configured points source implementation.
 */
class factory {
    /**
     * Instantiate the configured points source.
     *
     * @return base
     * @throws \moodle_exception If the configured source is unknown or unavailable.
     */
    public static function get(): base {
        $map = [
            'native' => native::class,
            // 'xp' => xp::class,
        ];

        $configured = get_config('local_btcrewards', 'points_source');
        if (empty($configured) || !isset($map[$configured])) {
            $configured = 'native';
        }

        $class = $map[$configured];
        /** @var base $instance */
        $instance = new $class();

        if (!$instance->is_available()) {
            throw new \moodle_exception('error_source_unavailable', 'local_btcrewards', '', $configured);
        }

        return $instance;
    }
}
