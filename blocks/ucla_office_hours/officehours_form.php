<?php
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');


class officehours_form extends moodleform {
    
    function definition(){
        global $CFG, $USER, $DB;
        
        $editid = $this->_customdata['editid'];
        $edit_email = $this->_customdata['edit_email'];        
        $courseid = $this->_customdata['courseid'];
        $defaults = $this->_customdata['defaults'];
        $website = $this->_customdata['url'];
        
        $mform = $this->_form;
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->addElement('hidden', 'editid', $editid);
        
        // office info \\
        $mform->addElement('header', 'header_office_info', 
                get_string('header_office_info', 'block_ucla_office_hours'));
        
        // office hours
        $mform->addElement('static', 'f_officehours_text', '', 
                get_string('f_officehours_text', 'block_ucla_office_hours'));
        $mform->addElement('text', 'officehours', get_string('f_officehours', 'block_ucla_office_hours'));
        
        // office location
        $mform->addElement('static', 'f_office_text', '', 
                get_string('f_office_text', 'block_ucla_office_hours'));        
        $mform->addElement('text', 'office', get_string('f_office', 'block_ucla_office_hours'));
 
        // contact info \\
        $mform->addElement('header', 'header_contact_info', 
                get_string('header_contact_info', 'block_ucla_office_hours'));
        
        // email of record
        $mform->addElement('static', 'f_email_of_record', 
                get_string('f_email_of_record', 'block_ucla_office_hours'), $edit_email);
        
        // alternative email
        $mform->addElement('text', 'email', get_string('f_email', 'block_ucla_office_hours'));
        $mform->addElement('static', 'f_email_text', '', 
                get_string('f_email_text', 'block_ucla_office_hours', $edit_email));            
        
        // phone
        $mform->addElement('static', 'f_phone_text', '', 
                get_string('f_phone_text', 'block_ucla_office_hours', $edit_email));        
        $mform->addElement('text', 'phone', get_string('f_phone', 'block_ucla_office_hours'));
        
        // website
        $mform->addElement('text', 'website', get_string('f_website', 'block_ucla_office_hours'));
        
        // set rules and types
        $mform->setType('officehours', PARAM_TEXT);   
        $mform->setType('office', PARAM_TEXT);       
        $mform->setType('phone', PARAM_TEXT);    
        
        $mform->addRule('email', get_string('err_email', 'form'), 'email');     
        $mform->setType('email', PARAM_EMAIL);        
        
        $mform->setDefault('website', $website);
        $mform->setType('website', PARAM_URL);        
        
        // set defaults
        if(!empty($defaults)) {
            $mform->setDefault('office', $defaults->officelocation);
            $mform->setDefault('officehours', $defaults->officehours);
            $mform->setDefault('phone', $defaults->phone);
            $mform->setDefault('email', $defaults->email);
        }
        
        $this->add_action_buttons();
    }
}

//EOF
