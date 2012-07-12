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
        global $CFG, $DB, $USER;
        $mform = & $this->_form;

        // Add some hidden fields
        $course = $this->_customdata['course']; 
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $course->id);       
        
        // set roles
        $mform->addElement('header', 'header_role', get_string('header_role', 'enrol_invitation'));
        
        $roles = $this->get_appropiate_roles($course);
        $label = get_string('assignrole', 'enrol_invitation');
        $role_group = array();
        foreach ($roles as $role) {
            $role_string = html_writer::tag('span', $role->name . ':', 
                    array('class' => 'role-name'));
            
            // role description has a <hr> tag to separate out info for users
            // and admins
            $role_description = explode('<hr />', $role->description);
            
            $role_description = $role_description[0];
            $role_description = strip_tags($role_description, '<b><i><strong>');
            
            $role_string .= ' ' . $role_description;
            $role_group[] = &$mform->createElement('radio', 'roleid', '', 
                    $role_string, $role->id);
        }
        $mform->addGroup($role_group, 'role_group', $label, 
                html_writer::empty_tag('br'));
        $mform->addGroupRule('role_group', 
                get_string('norole', 'enrol_invitation'), 'required');
        
        // email address field
        $mform->addElement('header', 'header_email', get_string('header_email', 'enrol_invitation'));        
        $mform->addElement('text', 'email', get_string('emailaddressnumber', 'enrol_invitation'));
        $mform->addRule('email', get_string('err_email', 'form'), 'required');
        $mform->setType('email', PARAM_EMAIL);
        
        // subject field
        $mform->addElement('text', 'subject', get_string('subject', 'enrol_invitation'));
        $mform->addRule('subject', get_string('required'), 'required');       
        // default subject is "Site invitation for <course title>"        
        $default_subject = get_string('default_subject', 'enrol_invitation', 
                sprintf('%s: %s', $course->shortname, $course->fullname));
        $mform->setDefault('subject', $default_subject);
        
        // message field
        $mform->addElement('textarea', 'message', get_string('message', 'enrol_invitation'));
        // put help text to show what default message invitee gets
        $mform->addHelpButton('message', 'message', 'enrol_invitation', 
                get_string('message_help_link', 'enrol_invitation'));
        
        // email options
        // prepare string variables
        $temp = new stdClass();
        $temp->email = $USER->email;
        $temp->supportemail = $CFG->supportemail;        
        $mform->addElement('checkbox', 'show_from_email', '', 
                get_string('show_from_email', 'enrol_invitation', $temp));
        $mform->addElement('checkbox', 'notify_inviter', '', 
                get_string('notify_inviter', 'enrol_invitation', $temp));
        $mform->setDefault('show_from_email', 1);
        $mform->setDefault('notify_inviter', 0);        
        
        $this->add_action_buttons(false, get_string('inviteusers', 'enrol_invitation'));
    }
    
    /**
     * Private class method to return a list of appropiate roles for given
     * course.
     * 
     * @param object $course    Course record
     */
    private function get_appropiate_roles($course) {
        global $CFG, $DB;
        $roles = array();
        
        // project/research sites need to only use project roles
        if (is_collab_site($course)) {
            // see if site indicator is installed
            $site_type = '';
            $collab_site_indicator = dirname(__FILE__) . '/../../' . 
                    $CFG->admin . '/tool/uclasiteindicator/lib.php';
            if (file_exists($collab_site_indicator)) {
                require($collab_site_indicator);
                // try to get type
                $siteindicator_site = siteindicator_site::load($course->id);
                if (!empty($siteindicator_site)) {
                    $site_type = $siteindicator_site->property->type;
                }
            }
            
            // figure out what roles to display
            // See CCLE-2948/CCLE-2949/CCLE-2913/site indicator
            switch ($site_type) {
                case 'test':    
                    $roles = array('editinginstructor', 'student', 'sa_1', 
                                   'sa_2', 'sa_3', 'sa_4', 'sp_1', 'sp_2', 
                                   'projectlead', 'projectcontributor', 
                                   'projectparticipant', 'projectviewer');      
                    break;
                case 'instruction':
                    $roles = array('editinginstructor', 'student', 'sa_1', 
                                   'sa_2', 'sa_3', 'sa_4', 'sp_2');                    
                    break;
                default:    // default to project roles 
                    $roles = array('projectlead', 'projectcontributor', 
                                   'projectparticipant', 'projectviewer');     
            }
        } else {
            $roles = array('sa_2', 'sa_3', 'sa_4', 'sp_1', 'sp_2');
        }
        
        // now get role names and descriptions
        return $DB->get_records_list('role', 'shortname', $roles, 'sortorder');        
    }

}
