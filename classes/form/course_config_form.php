<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Course-level reward config form: enable flag + course-completion points,
 * plus per-quiz and per-badge opt-in lists.
 *
 * @package    local_btcrewards
 * @copyright  2026 local_btcrewards contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_btcrewards\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

class course_config_form extends \moodleform {
    /** HTML attributes for the numeric points inputs. */
    private const POINT_ATTRS = [
        'type' => 'number', 'min' => '0', 'step' => '1', 'inputmode' => 'numeric',
    ];

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('advcheckbox', 'enabled',
            get_string('course_config_enabled', 'local_btcrewards'));
        $mform->addHelpButton('enabled', 'course_config_enabled', 'local_btcrewards');

        $mform->addElement('text', 'points_course_completed',
            get_string('setting_points_course_completed', 'local_btcrewards'),
            self::POINT_ATTRS);
        $mform->setType('points_course_completed', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('points_course_completed', 'course_config_override', 'local_btcrewards');
        $mform->disabledIf('points_course_completed', 'enabled', 'notchecked');

        $mform->addElement('select', 'claim_mode',
            get_string('course_config_claim_mode', 'local_btcrewards'),
            [
                \local_btcrewards\course_config::CLAIM_MODE_ADMIN_APPROVAL =>
                    get_string('course_config_claim_mode_admin_approval', 'local_btcrewards'),
                \local_btcrewards\course_config::CLAIM_MODE_SELF =>
                    get_string('course_config_claim_mode_self', 'local_btcrewards'),
            ]
        );
        $mform->addHelpButton('claim_mode', 'course_config_claim_mode', 'local_btcrewards');
        $mform->disabledIf('claim_mode', 'enabled', 'notchecked');

        // Per-quiz section.
        $quizzes = $this->_customdata['quizzes'] ?? [];
        $mform->addElement('header', 'quizheader',
            get_string('course_config_quizzes_heading', 'local_btcrewards'));
        if (empty($quizzes)) {
            $mform->addElement('static', 'quiz_empty', '',
                get_string('course_config_quizzes_empty', 'local_btcrewards'));
        } else {
            $mform->addElement('static', 'quiz_help', '',
                get_string('course_config_quizzes_help', 'local_btcrewards'));
            foreach ($quizzes as $q) {
                $name = "quiz_{$q->id}";
                $label = $q->itemname !== null && $q->itemname !== ''
                    ? format_string($q->itemname)
                    : "#{$q->id}";
                $mform->addElement('text', $name, $label, self::POINT_ATTRS);
                $mform->setType($name, PARAM_RAW_TRIMMED);
                $mform->disabledIf($name, 'enabled', 'notchecked');
            }
        }

        // Per-badge section.
        $badges = $this->_customdata['badges'] ?? [];
        $mform->addElement('header', 'badgeheader',
            get_string('course_config_badges_heading', 'local_btcrewards'));
        if (empty($badges)) {
            $mform->addElement('static', 'badge_empty', '',
                get_string('course_config_badges_empty', 'local_btcrewards'));
        } else {
            $mform->addElement('static', 'badge_help', '',
                get_string('course_config_badges_help', 'local_btcrewards'));
            foreach ($badges as $b) {
                $name = "badge_{$b->id}";
                $mform->addElement('text', $name, format_string($b->name), self::POINT_ATTRS);
                $mform->setType($name, PARAM_RAW_TRIMMED);
                $mform->disabledIf($name, 'enabled', 'notchecked');
            }
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        foreach ($data as $key => $value) {
            if (!preg_match('/^(points_course_completed|quiz_\d+|badge_\d+)$/', $key)) {
                continue;
            }
            if ($value === '' || $value === null) {
                continue;
            }
            if (!ctype_digit((string) $value)) {
                $errors[$key] = get_string('course_config_must_be_int', 'local_btcrewards');
            }
        }
        return $errors;
    }
}
