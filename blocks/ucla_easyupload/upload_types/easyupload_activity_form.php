<?php

class easyupload_activity_form extends easy_upload_form {
    
    function specification() {
        $mform = $this->_form;

        $course = $mform->getElement('course')->getValue();

        $mform->addElement('hidden', 'redirectme', 
            '/course/modedit.php');

        // Add the select form
        $mform->addElement('select', 'add', '');

        // Stolen from course/lib.php:1838
    }

    function get_coursemodule() {
        return false;
    }
}

// End of File
