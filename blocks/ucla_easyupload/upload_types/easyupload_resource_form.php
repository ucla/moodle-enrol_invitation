<?php

class easyupload_resource_form extends easy_upload_form {
    function specification() {
        $mform =& $this->_form;

        $course = $mform->getElement('course')->getValue();

        $mform->addElement('hidden', 'redirectme', 
            '/course/modedit.php');
        
        $mform->addElement('select', 'add', 
            get_string('dialog_add_resource_box', self::associated_block),
            $this->resources);
    }

    function get_coursemodule() {
        return false;
    }

    function get_send_params() {
        return array('course', 'add', 'section');
    }
}

// End of File
