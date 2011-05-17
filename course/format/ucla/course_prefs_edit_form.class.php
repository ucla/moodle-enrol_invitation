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
 * @package    uclaformat
 * @copyright  
 * @license    
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');

class course_prefs_edit_form extends moodleform {
    protected $course;
    protected $context;

    function definition() {
        global $USER, $CFG, $DB;

        $mform = $this->_form;

        $course = $this->_customdata['course'];
        $currprefs = $this->_customdata['currprefs'];

        $sections = array();
        $course_sections = get_all_sections($course->id);

        for ($i = 0; $i < $course->numsections; $i++) {
            $sections[$i] = get_section_name($course, $course_sections[$i]);
        }

        $sections['-1'] = get_string('show_all', 'format_ucla');

        // We want to make sure our edit form does not break
        $mform->addElement('hidden', 'courseid', null);
        $mform->setType('courseid', PARAM_INT);
        $mform->setConstant('courseid', $course->id);

        // Make a section
        $mform->addElement('header', 'general', 
            get_string('course_pref', 'format_ucla'));

        // Select the landing page
        $opt = 'landing_page';
        $mform->addElement('select', $opt,
            get_string('landing_page', 'format_ucla'), $sections);
        $mform->setDefault($opt, $currprefs->get_preference($opt));

        // Finished
        $this->add_action_buttons();
    }
}
