<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Per-quiz (grade-item) reward config.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards;

defined('MOODLE_INTERNAL') || die();

class quiz_config extends item_config {
    protected static function table(): string {
        return 'btcrewards_quiz_config';
    }

    protected static function key_column(): string {
        return 'gradeitemid';
    }

    /**
     * List the course's grade-items along with their configured points (if any).
     * Returns rows with: id, itemname, itemmodule, iteminstance, points (null if none).
     */
    public static function list_for_course(int $courseid): array {
        global $DB;
        return $DB->get_records_sql(
            "SELECT gi.id, gi.itemname, gi.itemmodule, gi.iteminstance, qc.points
               FROM {grade_items} gi
          LEFT JOIN {btcrewards_quiz_config} qc ON qc.gradeitemid = gi.id
              WHERE gi.courseid = ? AND gi.itemtype = 'mod'
           ORDER BY gi.sortorder, gi.itemname",
            [$courseid]
        );
    }
}
