<?php
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');


class officehours_form extends moodleform {
    
    function definition(){
        global $CFG, $USER, $DB;
        
        $edit_id = $this->_customdata['edit_id'];
        $edit_email = $this->_customdata['edit_email'];        
        $course_id = $this->_customdata['course_id'];
        $defaults = $this->_customdata['defaults'];
        $website = $this->_customdata['url'];
        
        $mform = $this->_form;
        $mform->addElement('hidden', 'course_id', $course_id);
        $mform->addElement('hidden', 'edit_id', $edit_id);
        
        $mform->addElement('header', 'header_office_info', 
                get_string('header_office_info', 'block_ucla_office_hours'));
        $mform->addElement('static', 'f_officehours_text', '', 
                get_string('f_officehours_text', 'block_ucla_office_hours'));
        $mform->addElement('text', 'officehours', get_string('f_officehours', 'block_ucla_office_hours'));
        $mform->addElement('static', 'f_office_text', '', 
                get_string('f_office_text', 'block_ucla_office_hours'));        
        $mform->addElement('text', 'office', get_string('f_office', 'block_ucla_office_hours'));
 
        $mform->addElement('header', 'header_contact_info', 
                get_string('header_contact_info', 'block_ucla_office_hours'));
        $mform->addElement('static', 'f_email_text', '', 
                get_string('f_email_text', 'block_ucla_office_hours', $edit_email));            
        $mform->addElement('text', 'email', get_string('f_email', 'block_ucla_office_hours'));
        $mform->addElement('static', 'f_phone_text', '', 
                get_string('f_phone_text', 'block_ucla_office_hours', $edit_email));        
        $mform->addElement('text', 'phone', get_string('f_phone', 'block_ucla_office_hours'));
        $mform->addElement('text', 'website', get_string('f_website', 'block_ucla_office_hours'));
        
        $mform->addRule('email', get_string('err_email', 'form'), 'email');     
        $mform->setType('email', PARAM_EMAIL);        
        
        $mform->setDefault('website', $website);
        $mform->setType('website', PARAM_URL);        
        
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
