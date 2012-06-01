<?php
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');


class officehours_form extends moodleform {
    
    function definition(){
        global $CFG, $USER, $DB;
        
        $edit_id = $this->_customdata['edit_id'];
        $course_id = $this->_customdata['course_id'];
        $defaults = $this->_customdata['defaults'];
        $edit_name = $this->_customdata['edit_name'];
        $website = $this->_customdata['url'];
        
        $mform = $this->_form;
        $mform->addElement('hidden', 'course_id', $course_id);
        $mform->addElement('hidden', 'edit_id', $edit_id);
        
        $mform->addElement('header', 'header', get_string('header', 'block_ucla_office_hours'));
        $mform->addElement('static', 'edituser', '', 
                get_string('edituser', 'block_ucla_office_hours') . $edit_name);

        $mform->addElement('text', 'office', get_string('f_office', 'block_ucla_office_hours'));
        $mform->addElement('text', 'officehours', get_string('f_officehours', 'block_ucla_office_hours'));
        $mform->addElement('text', 'phone', get_string('f_phone', 'block_ucla_office_hours'));
        $mform->addElement('text', 'email', get_string('f_email', 'block_ucla_office_hours'));
        $mform->addHelpButton('email', 'email_info', 'block_ucla_office_hours');
        
        $mform->addElement('text', 'website', get_string('f_website', 'block_ucla_office_hours'));
        $mform->setDefault('website', $website);
        $mform->addHelpButton('website', 'website_info', 'block_ucla_office_hours');
        
        if($defaults) {
            $mform->setDefault('office', $defaults->officelocation);
            $mform->setDefault('officehours', $defaults->officehours);
            $mform->setDefault('phone', $defaults->phone);
            $mform->setDefault('email', $defaults->email);
        }
        
        $this->add_action_buttons();
    }
}


//EOF
