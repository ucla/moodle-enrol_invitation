<?php

defined('MOODLE_INTERNAL') || die();

class easyupload_text_form extends easy_upload_form {
    var $allow_js_select = true;

    function specification() {
        $mform =& $this->_form;

        $mform->addElement('editor', 'introeditor', 
            get_string('dialog_add_text_box', self::associated_block));
    }

    function get_coursemodule() {
        return 'label';
    }
}

// EoF
