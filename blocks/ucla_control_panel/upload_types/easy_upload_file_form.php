<?php

defined('MOODLE_INTERNAL') || die();

class easy_upload_file_form extends easy_upload_form {
    var $allow_renaming = true;

    function specification() {
        $mform = $this->_form;

        $mform->addElement('filepicker', 'externalfile', 
            get_string('dialog_add_file_box', 'block_ucla_control_panel'));
    }

    function process_data($data) {
        var_dump($data);
        die;
    }
}
