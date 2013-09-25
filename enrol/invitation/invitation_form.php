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
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclaroles/lib.php');

/**
 * The mform class for sending invitation to enrol users in a course
 *
 * @copyright 2011 Jerome Mouneyrac
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package enrol
 * @subpackage invitation
 */
class invitation_form extends moodleform {
    public static $daysexpire_options = array(3 => 3, 7 => 7, 30 => 30, 90 => 90, 180 => 180);

    /**
     * The form definition
     */
    function definition() {
        global $CFG, $DB, $USER;
        $mform = & $this->_form;

        // Get rid of "Collapse all".
        $mform->setDisableShortforms(true);

        // Add some hidden fields
        $course = $this->_customdata['course']; 
        $prefilled = $this->_customdata['prefilled'];
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $course->id);       
        
        // set roles
        $mform->addElement('header', 'header_role', get_string('header_role', 'enrol_invitation'));
        
        $site_roles = $this->get_appropiate_roles($course);
        $label = get_string('assignrole', 'enrol_invitation');
        $role_group = array();        
        foreach ($site_roles as $role_type => $roles) {          
            $role_type_string = html_writer::tag('div', 
                    get_string($role_type, 'tool_uclaroles'), 
                    array('class' => 'label-bstp label-primary'));
            $role_group[] = &$mform->createElement('static', 'role_type_header', 
                    '', $role_type_string);            
            
            foreach ($roles as $role) {
                $role_string = $this->format_role_string($role);
                $role_group[] = &$mform->createElement('radio', 'roleid', '', 
                        $role_string, $role->id);                
            }
        }

        // Give "Temporary Participant" option if site is Registrar or TA site.
        if (get_config('enrol_invitation', 'enabletempparticipant')) {
            $site_type = null;
            if (is_collab_site($course)) {
                $siteindicator = siteindicator_site::load($course->id);
                if (!empty($siteindicator)) {
                    $site_type = $siteindicator->property->type;
                }
            } else {
                $site_type = siteindicator_manager::SITE_TYPE_SRS_INSTRUCTION;
            }
            
            if ($site_type == siteindicator_manager::SITE_TYPE_SRS_INSTRUCTION ||
                    $site_type == siteindicator_manager::SITE_TYPE_TASITE) {

                // Create Temporary Roles group.
                $role_type_string = html_writer::tag('div', 
                        get_string('tempgroup', 'enrol_invitation'),
                        array('class' => 'label-bstp label-warning'));
                $role_group[] = &$mform->createElement('static', 'role_type_header', 
                        '', $role_type_string);
                
                // Add Temporary Participant role.
                $role = $DB->get_record('role',
                        array('shortname' => 'tempparticipant'));
                $role_string = $this->format_role_string($role);

                $role_group[] = &$mform->createElement('radio', 'roleid', '', 
                        $role_string, $role->id);

                // Create dropdown for choosing day expiration.
                $daysexpire_dropdown = &$mform->createElement('select',
                        'daysexpire', '', invitation_form::$daysexpire_options);
                $daysexpire_string = html_writer::tag('span',
                        get_string('daysexpire_string', 'enrol_invitation',
                                $daysexpire_dropdown->toHtml()),
                        array('class' => 'well well-sm daysexpire_string'));
                $role_group[] = &$mform->createElement('static', 
                        'daysexpire_string', '', $daysexpire_string);
            }
        }

        $mform->addGroup($role_group, 'role_group', $label);
        $mform->addRule('role_group', 
                get_string('norole', 'enrol_invitation'), 'required');
        
        // email address field
        $mform->addElement('header', 'header_email', get_string('header_email', 'enrol_invitation'));        
        $mform->addElement('textarea', 'email', get_string('emailaddressnumber', 'enrol_invitation'), 
                array('maxlength' => 1000, 'class' => 'form-invite-email'));
        $mform->addRule('email', null, 'required', null, 'client');
        $mform->setType('email', PARAM_TEXT);
        // Check for correct email formating later in validation() function
        $mform->addElement('static', 'email_clarification', '', get_string('email_clarification', 'enrol_invitation'));
        
        // subject field
        $mform->addElement('text', 'subject', get_string('subject', 'enrol_invitation'),
                array('class' => 'form-invite-subject'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('required'), 'required');       
        // default subject is "Site invitation for <course title>"        
        $default_subject = get_string('default_subject', 'enrol_invitation', 
                sprintf('%s: %s', $course->shortname, $course->fullname));
        $mform->setDefault('subject', $default_subject);
        
        // message field
        $mform->addElement('textarea', 'message', get_string('message', 'enrol_invitation'),
                array('class' => 'form-invite-message'));  
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

        // Set defaults if the user is resending an invite that expired
        if ( !empty($prefilled) ) {
            $mform->setDefault('role_group[roleid]', $prefilled['roleid']);
            $mform->setDefault('email', $prefilled['email']);
            $mform->setDefault('subject', $prefilled['subject']);
            $mform->setDefault('message', $prefilled['message']);
            $mform->setDefault('show_from_email', $prefilled['show_from_email']);
            $mform->setDefault('notify_inviter', $prefilled['notify_inviter']);
        }
        
        $this->add_action_buttons(false, get_string('inviteusers', 'enrol_invitation'));
    }

    /**
     * Overriding get_data, because we need to be able to handle daysexpire,
     * which is not defined as a regular form element.
     *
     * @return object
     */
    public function get_data() {
        $retval = parent::get_data();

        // Check if form validated, and if user submitted daysexpire from POST.
        if (!empty($retval) && isset($_POST['daysexpire'])) {
            if (in_array($_POST['daysexpire'], self::$daysexpire_options)) {
                // Cannot indicate to user a real error message, so just slightly
                // ignore user setting.
                $retval->daysexpire = $_POST['daysexpire'];
            }
        }

        return $retval;
    }

    /**
     * Given a role record, format string to be displayable to user. Filter out
     * role notes and other information.
     *
     * @param object $role  Record from role table.
     * @return string
     */
    private function format_role_string($role) {
        $role_string = html_writer::tag('span', $role->name . ':',
                array('class' => 'role-name'));

        // role description has a <hr> tag to separate out info for users
        // and admins
        $role_description = explode('<hr />', $role->description);

        // need to clean html, because tinymce adds a lot of extra tags that mess up formatting
        $role_description = $role_description[0];
        // whitelist some formatting tags
        $role_description = strip_tags($role_description, '<b><i><strong><ul><li><ol>');

        $role_string .= ' ' . $role_description;

        return $role_string;
    }

    /**
     * Private class method to return a list of appropiate roles for given
     * course.
     * 
     * @param object $course    Course record
     * 
     * @return array            Returns array of roles indexed by role type
     */
    private function get_appropiate_roles($course) {
        $roles = uclaroles_manager::get_assignable_roles_by_courseid($course);        
        // sort roles into type
        return uclaroles_manager::orderby_role_type($roles);
    }
    
    /*
     * Provides custom validation rules.
     *  - Validating the email field here, rather than in definition, to allow
     *    multiple email addresses to be specified
     *  - Validating that access end date is in the future
     */
    function validation($data, $files) {
        $errors = array();
        $delimiters = "/[;, \r\n]/";
        $email_list = invitation_form::parse_dsv_emails($data['email'], $delimiters);
        
        if (empty($email_list)) {
            $errors['email'] = get_string('err_email', 'form');
        }

        return $errors;
    }
    
    /**
    * Parses a string containing delimiter seperated values for email addresses.
    * Returns an empty array if an invalid email is found.
    * 
    * @param string $emails           string of emails to be parsed
    * @param string $delimiters       list of delimiters as regex
    * @return array $parsed_emails    array of emails
    */
    static function parse_dsv_emails($emails, $delimiters) {
        $parsed_emails = array();
        $emails = trim($emails);
        if (preg_match($delimiters, $emails)) {
            // Multiple email addresses specified
            $dsv_emails = preg_split($delimiters, $emails, NULL, PREG_SPLIT_NO_EMPTY);
            foreach ($dsv_emails as $email_value) {
                $email_value = trim($email_value);
                if (!clean_param($email_value, PARAM_EMAIL)){
                    return array();
                }
                $parsed_emails[] = $email_value;
            }
        } else if (clean_param($emails, PARAM_EMAIL)) {
            // single email
            return (array)$emails;
        } else {
            return array();
        }
        
        return $parsed_emails;   
    }
}
