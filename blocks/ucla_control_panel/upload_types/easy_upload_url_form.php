<?php

defined('MOODLE_INTERNAL') || die();

class easy_upload_url_form extends easy_upload_form {
    function specification() {
        $mform = $this->_form;

        $mform->addElement('url', 'externalurl', get_string('dialog_add_url_box',
            'block_ucla_control_panel'), array('size' => 60));
    }

    function process_data($data) {
        
    }
}
