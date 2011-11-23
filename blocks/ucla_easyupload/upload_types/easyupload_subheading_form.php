<?php

defined('MOODLE_INTERNAL') || die();

class easyupload_subheading_form extends easy_upload_form {
    var $allow_js_select = true;

    function specification() {
        $mform =& $this->_form;

        $mform->addElement('text', 'text', 
            get_string('dialog_add_subheading_box', self::associated_block));
        
        $mform->addRule('text', null, 'required');        

        // intro is a required field in the mdl_label table, so force it here
        $mform->addElement('hidden', 'introeditor[text]', '');
    }

    function get_coursemodule() {
        return 'label';
    }
}

// EoF
