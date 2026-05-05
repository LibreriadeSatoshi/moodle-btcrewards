<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Course-level reward config form.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Moodleform for editing a course's btcrewards override.
 */
class course_config_form extends \moodleform {
    /**
     * Build the form.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('advcheckbox', 'enabled', get_string('course_config_enabled', 'local_btcrewards'));
        $mform->addHelpButton('enabled', 'course_config_enabled', 'local_btcrewards');

        $fields = [
            'points_course_completed',
            'points_quiz_passed',
            'points_badge_awarded',
        ];
        foreach ($fields as $name) {
            $mform->addElement('text', $name, get_string('setting_' . $name, 'local_btcrewards'));
            $mform->setType($name, PARAM_RAW_TRIMMED);
            $mform->addHelpButton($name, 'course_config_override', 'local_btcrewards');
            $mform->disabledIf($name, 'enabled', 'notchecked');
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Validate numeric overrides — allow empty (fall back) or non-negative int.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        foreach (['points_course_completed', 'points_quiz_passed', 'points_badge_awarded'] as $name) {
            $value = $data[$name] ?? '';
            if ($value === '' || $value === null) {
                continue;
            }
            if (!ctype_digit((string) $value)) {
                $errors[$name] = get_string('course_config_must_be_int', 'local_btcrewards');
            }
        }
        return $errors;
    }
}
