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
 * Syllabus alert form definition.
 *
 * @package    local_ucla_syllabus
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');

/**
 * Alert form class.
 * 
 * Used to create a form for alerting user about syllabus.
 * 
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class alert_form extends moodleform {

    /**
     * Generates syllabus alert form for both manually uploaded and regular
     * syllabi.
     */
    public function definition() {
        $mform = $this->_form;

        // Need to use multiple submit buttons so that form is sent without the
        // need for js onclick handlers.
        $alertbuttons = array();

        // Handling manually uploaded syllabus?
        $manualsyllabusid = 0;
        if (!empty($this->_customdata['manualsyllabus'])) {
            $manualsyllabus = $this->_customdata['manualsyllabus'];
            $manualsyllabusid = $manualsyllabus->cmid;
            $mform->addElement('html', html_writer::tag('div',
                    get_string('alert_msg_manual', 'local_ucla_syllabus', $manualsyllabus)));

            $alertbuttons[] = $mform->createElement('submit', 'yesbutton',
                    get_string('yes'));
            $alertbuttons[] = $mform->createElement('submit', 'nobutton',
                    get_string('no'));
        } else {
            // Display regular syllabus alert.
            $mform->addElement('html', html_writer::tag('div',
                    get_string('alert_msg', 'local_ucla_syllabus')));

            $alertbuttons[] = $mform->createElement('submit', 'yesbutton',
                    get_string('alert_yes', 'local_ucla_syllabus'));
            $alertbuttons[] = $mform->createElement('submit', 'laterbutton',
                    get_string('alert_later', 'local_ucla_syllabus'));
            $alertbuttons[] = $mform->createElement('submit', 'nobutton',
                    get_string('alert_no', 'local_ucla_syllabus'));
        }

        $mform->addGroup($alertbuttons, 'alert_buttons', '', array(' '), false);
        $mform->closeHeaderBefore('alert_buttons');

        $mform->addElement('hidden', 'manualsyllabus', $manualsyllabusid);
    }
}