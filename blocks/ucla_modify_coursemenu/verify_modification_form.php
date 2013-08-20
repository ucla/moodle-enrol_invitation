<?php

defined('MOODLE_INTERNAL') || die();

class verify_modification_form extends moodleform {

    function definition() {
        $mform =& $this->_form;
        
        $passthrudata = $this->_customdata['passthrudata'];

        $mform->addElement('hidden', 'passthrudata', 
            serialize($passthrudata));
        $mform->setType('passthrudata', PARAM_RAW);
        
        $mform->addElement('hidden', 'courseid',
            $this->_customdata['courseid']);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('html', $this->_customdata['displayhtml']);
        
        $this->add_action_buttons(true, get_string('deleteconfirm', 
            'block_ucla_modify_coursemenu'));
    }
}
