<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Shared implementation for per-item reward tables — both per-quiz and
 * per-badge configs are upserts on (key_column → points) with a uniform
 * shape. Subclasses declare the table name and key column; list_for_course
 * is specific to each (different join targets).
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards;

defined('MOODLE_INTERNAL') || die();

abstract class item_config {
    abstract protected static function table(): string;
    abstract protected static function key_column(): string;

    /** Points to award for this item. 0 if not configured. */
    public static function points(int $itemid): int {
        global $DB;
        $row = $DB->get_record(static::table(), [static::key_column() => $itemid]);
        return $row ? (int) $row->points : 0;
    }

    /** Upsert; deleting the row when $points is null or <= 0. */
    public static function save(int $itemid, ?int $points): void {
        global $DB;
        $existing = $DB->get_record(static::table(), [static::key_column() => $itemid]);
        if ($points === null || $points <= 0) {
            if ($existing) {
                $DB->delete_records(static::table(), ['id' => $existing->id]);
            }
            return;
        }
        $now = time();
        if ($existing) {
            $DB->update_record(static::table(), (object) [
                'id'           => $existing->id,
                'points'       => $points,
                'timemodified' => $now,
            ]);
            return;
        }
        $DB->insert_record(static::table(), (object) [
            static::key_column() => $itemid,
            'points'             => $points,
            'timemodified'       => $now,
        ]);
    }
}
