<?php

defined('MOODLE_INTERNAL') || die();

class easyupload_label_form extends easy_upload_form {
    var $allow_js_select = true;

    function specification() {
        $mform =& $this->_form;

        $mform->addElement('text', 'text', 
            get_string('dialog_add_label_box', self::associated_block));

        $mform->addElement('hidden', 'format', 1);
    }

    function get_coursemodule() {
        return 'label';
    }
}

// EoF
