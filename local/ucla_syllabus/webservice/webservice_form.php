<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
}

require_once($CFG->libdir.'/formslib.php');

class syllabus_ws_form extends moodleform {
    
    function definition() {
        global $DB, $USER;

        $mform =& $this->_form;
        $mform->addElement('header','header', 'Foobar');
        // Subject areas
        $mform->addElement('text', 
                'subject_area', 
                'Subject areas', 
                array());
        // Leading srs
        $mform->addElement('text', 
                'srs', 
                'Leading SRS', 
                array());
        
        // POST url
        $mform->addElement('text', 
                'url', 
                'URL', 
                array());
        $mform->addRule('url', 'You must provide a service URL', 'required');

        // Required email
        $mform->addElement('text', 
                'contact', 
                'Contact email', 
                array());
        $mform->addRule('contact', 'You must provide a contact email', 'required');
        $mform->addRule('contact', 'Your email is invalid', 'email');
        
                
        // Optional token
        $mform->addElement('text', 
                'token', 
                'Token', 
                array());
        
        $mform->addElement('select', 
                'action', 
                'Service action', 
                array('Syllabus alert', 'Syllabus transfer'), 
                array());
        
        $this->add_action_buttons();
        
    }
}