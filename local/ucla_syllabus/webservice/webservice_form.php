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


if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

require_once($CFG->libdir.'/formslib.php');

class syllabus_ws_form extends moodleform {

    public function definition() {
        global $DB, $USER;

        $mform =& $this->_form;
        $subjareas = $this->_customdata['subjareas'];

        $mform->addElement('header', 'header', get_string('ws_header', 'local_ucla_syllabus'));

        // Subject areas.
        $mform->addElement('select', 'subjectarea',
                get_string('subject_area', 'local_ucla_syllabus'),
                $subjareas,
                array());
        $mform->addHelpButton('subjectarea', 'subject_area', 'local_ucla_syllabus');

        // Leading SRS.
        $mform->addElement('text', 'leadingsrs',
                get_string('leading_srs', 'local_ucla_syllabus'),
                array('maxlength' => 9));
        $mform->addRule('leadingsrs',
                get_string('leading_srs', 'local_ucla_syllabus'),
                'numeric', null, 'client');

        // POST url.
        $mform->addElement('text', 'url',
                get_string('post_url', 'local_ucla_syllabus'),
                array('maxlength' => 200, 'size' => 50));
        $mform->addRule('url',
                get_string('post_url_required', 'local_ucla_syllabus'),
                'required', null, 'client');

        // Contact email.
        $mform->addElement('text', 'contact',
                get_string('contact_email', 'local_ucla_syllabus'),
                array('maxlength' => 100, 'size' => 50));
        $mform->addRule('contact',
                get_string('contact_email_required', 'local_ucla_syllabus'),
                'required', null, 'client');
        $mform->addRule('contact',
                get_string('contact_email_required', 'local_ucla_syllabus'),
                'email', null, 'client');
        $mform->addHelpButton('contact', 'contact_email', 'local_ucla_syllabus');

        // Optional token.
        $mform->addElement('text', 'token',
                get_string('token', 'local_ucla_syllabus'),
                array('maxlength' => 64, 'size' => 50));
        $mform->addHelpButton('token', 'token', 'local_ucla_syllabus');

        $mform->addElement('select', 'action',
                get_string('select_action', 'local_ucla_syllabus'),
                syllabus_ws_manager::get_event_actions(),
                array());

        $this->add_action_buttons();
    }
}
