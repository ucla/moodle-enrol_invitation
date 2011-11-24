<?php

defined('MOODLE_INTERNAL') || die();

class easyupload_subheading_form extends easy_upload_form {
    var $allow_js_select = true;

    function specification() {
        $mform =& $this->_form;
  
        $mform->addElement('text', 'intro', get_string('dialog_add_subheading_box', self::associated_block));
        $mform->addElement('hidden', 'introformat', FORMAT_PLAIN);
        
        $mform->addRule('intro', null, 'required');        
    }

    function get_coursemodule() {
        return 'label';
    }
}

// EoF
