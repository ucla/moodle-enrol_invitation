<?php
// This file is not a part of Moodle - http://moodle.org/
// This is a none core contributed module.
//
// This is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// The GNU General Public License
// can be see at <http://www.gnu.org/licenses/>.

/**
 * Always include formslib
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once ('locallib.php');
require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/lib/enrollib.php');

// required to get figure out roles
require_once($CFG->dirroot . '/local/ucla/lib.php');

/**
 * The mform class for sending invitation to enrol users in a course
 *
 * @copyright 2011 Jerome Mouneyrac
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package enrol
 * @subpackage invitation
 */
class invitations_form extends moodleform {

    /**
     * The form definition
     */
    function definition() {
        global $CFG, $USER, $OUTPUT, $PAGE;
        $mform = & $this->_form;

        // Add some hidden fields
        $courseid = $this->_customdata['courseid']; 
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $courseid);       
        
        // set roles
        $mform->addElement('static', 'role_desc', '', get_string('role_desc', 'enrol_invitation'));
        
        $roles = $this->get_appropiate_roles($courseid);
        $label = get_string('assignrole', 'enrol_invitation');
        foreach ($roles as $role) {
            $role_string = html_writer::tag('span', $role->name . ':', 
                    array('class' => 'role-name'));
            $role_string .= ' ' . strip_tags($role->description);
            $mform->addElement('radio', 'roleid', $label, $role_string, $role->id);
            $label = '';    // only apply label to first role
        }
        $mform->addRule('roleid', get_string('norole', 'enrol_invitation'), 'required');
        
        // Email address fields
        $mform->addElement('text', 'email', get_string('emailaddressnumber', 'enrol_invitation'), 'size="50"');
        //first email address is required
        $mform->addRule('email', get_string('required'), 'required');

        $mform->setType('email', PARAM_EMAIL);
        
        $this->add_action_buttons(false, get_string('inviteusers', 'enrol_invitation'));
    }
    
    /**
     * Private class method to return a list of appropiate roles for given
     * course.
     * 
     * @param type $courseid 
     */
    private function get_appropiate_roles($courseid) {
        global $DB, $USER;
        $course = new stdClass();
        $course->id = $courseid;
        // project/research sites need to only use project roles
        if (is_collab_site($course)) {
            $roles = array('projectlead', 'projectcontributor', 
                'projectparticipant', 'projectviewer');            
        } else {
            // TODO: add in support for instructional collab sites to use class roles                        
            $roles = array('sa_2', 'sa_3', 'sa_4', 'sp_1', 'sp_2');
        }
        
        // now get role names and descriptions
        return $DB->get_records_list('role', 'shortname', $roles);        
    }

}
