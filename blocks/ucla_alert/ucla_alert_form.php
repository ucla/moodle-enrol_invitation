<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

//require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');
require_once($CFG->libdir.'/formslib.php');

class ucla_alert_form extends moodleform {
    
    function definition() {
        global $DB, $USER;

        $mform =& $this->_form;
        
        $mform->addElement('header', 'header_header', 'Editing the alert block');
    }
    
}

class ucla_alert_add_form extends moodleform {
    function definition() {
        global $DB, $USER;
        
        $mform =& $this->_form;
        
        $mform->addElement('header', 'header_header', 'Add alert');
        
        $mform->addElement('textarea', 'content', 'Message', 'wrap="virtual" rows="2" cols="50"');
        $mform->addRule('content', 'Where is the message?', 'required');

        $buttonarray=array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', 'Add alert');
        $buttonarray[] = &$mform->createElement('reset', 'resetbutton', 'Clear');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
}