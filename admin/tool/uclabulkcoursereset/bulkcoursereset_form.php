<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/completionlib.php');


class bulkcoursereset_form extends moodleform {
    
    function definition(){
        
        $mform = $this->_form;
        $course_list = $this->_customdata['course_list'];
        
        // Find a better way to display and select courses
        $mform->addElement('header', 'header_selectcourse', 
                get_string('header_selectcourse', 'tool_uclabulkcoursereset'));
        
        $mform->addElement('select', 'course_list', get_string('course_select', 'tool_uclabulkcoursereset'), $course_list);
        
        $mform->addElement('advcheckbox', 'select_all', '', get_string('select_all', 'tool_uclabulkcoursereset'));
    }
    
}