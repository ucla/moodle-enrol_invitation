<?php
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');


class officehours_form extends moodleform {
    
    
    function definition(){
        global $CFG, $USER, $DB;
        
        $edit_id = $this->_customdata['edit_id'];
        $course_id = $this->_customdata['course_id'];
        
        
        $mform = $this->_form;
        $mform->addElement('hidden', 'course_id', $course_id);
        $mform->addElement('hidden', 'edit_id', $edit_id);
        
        $mform->addElement('header', 'header', get_string('header', 'block_ucla_office_hours'));
        if($edit_id) {
            $edit_user = $DB->get_record('user', array('id'=>$edit_id), '*', MUST_EXIST);
            $mform->addElement('static', 'edituser', '', 
                    get_string('edituser', 'block_ucla_office_hours') . $edit_user->firstname . ' ' . $edit_user->lastname);
        }
        $mform->addElement('text', 'office', get_string('office', 'block_ucla_office_hours'));
        $mform->addElement('text', 'officehours', get_string('officehours', 'block_ucla_office_hours'));
        $mform->addElement('text', 'phone', get_string('phone', 'block_ucla_office_hours'));
        $mform->addElement('text', 'email', get_string('email', 'block_ucla_office_hours'));
        $mform->addElement('text', 'website', get_string('website', 'block_ucla_office_hours'));
        $this->add_action_buttons();
        //$mform->addRule('office', get_string('emptyfield', 'block_ucla_office_hours'), 'required', null, 'server');
        
    }
    
    /*
    function definition_after_data(){
    }
    */
    /*
    function is_cancelled() {
        global $CFG, $USER, $DB;
        $result = parent::is_cancelled();
        
        if (!empty($result)) {
            $course_id  = $this->_customdata['course_id'];
            $url = new moodle_url($CFG->wwwroot.'/course/view.php', array('id'=>$course_id));
            //redirect($url);
        }
    }
    */
}


//EOF
