<?php
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');


class officehours_form extends moodleform {
    
    var $usertoedit;
    
    function definition(){
        global $CFG, $USER;
        
        $edit_id = $this->_customdata['edit'];
        
        $mform =& $this->_form;
        $mform->addElement('header', 'header', get_string('header', 'block_ucla_office_hours'));
        $mform->addElement('static', 'edituser', '', 
                get_string('edituser', 'block_ucla_office_hours') . $edit_id->firstname . ' ' . $edit_id->lastname);
        $mform->addElement('text', 'office', get_string('office', 'block_ucla_office_hours'));
        $mform->addElement('text', 'officehours', get_string('officehours', 'block_ucla_office_hours'));
        $mform->addElement('text', 'phone', get_string('phone', 'block_ucla_office_hours'));
        $mform->addElement('text', 'email', get_string('email', 'block_ucla_office_hours'));
        $mform->addElement('text', 'website', get_string('website', 'block_ucla_office_hours'));
        $mform->addElement('button', 'save', get_string('save', 'block_ucla_office_hours'));
        $mform->setType('office', PARAM_NOTAGS);
        //$mform->addRule('office', get_string('emptyfield', 'block_ucla_office_hours'), 'required', null, 'server');
        
    }
    
    /*
    function definition_after_data(){
    }
    */
}


//EOF
