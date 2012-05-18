<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Form for editing voiceemail block instances.
 *
 * @package   block_bvoiceemail
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Form for editing voiceemail block instances.
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_bvoiceemail_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        global $DB;
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $config = $this->block->config;
        $mform->addElement('advcheckbox', 'config_all_users_enrolled', get_string('block_send_vmail_all','voiceemail'));
        if (!isset($block_config->all_users_enrolled)) {
            $mform->setDefault('config_all_users_enrolled', 1);
        }
        $mform->addElement('advcheckbox', 'config_instructor', get_string('block_send_vmail_instructors','voiceemail'));
        if (!isset($block_config->instructor)) {
            $mform->setDefault('config_instructor', 1);
        }
        $mform->addElement('advcheckbox', 'config_student', get_string('block_send_vmail_students','voiceemail'));
        if (!isset($block_config->student)) {
            $mform->setDefault('config_student', 1);
        }
        $mform->addElement('advcheckbox', 'config_recipient', get_string('block_send_vmail_selected','voiceemail'));
        if (!isset($block_config->recipient)) {
            $mform->setDefault('config_recipient', 1);
        }
    }
}
