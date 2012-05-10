<?php

defined('MOODLE_INTERNAL') || die();

class easyupload_subheading_form extends easy_upload_form {
    var $allow_js_select = true;
    var $default_displayname_field = 'intro';

    function specification() {
        $mform =& $this->_form;
  
        $mform->addElement('text', 'intro', 
            get_string('dialog_add_subheading_box', self::associated_block));
        $mform->addRule('intro', null, 'required');        

        $mform->addElement('hidden', 'introformat', FORMAT_PLAIN);
    }

    function get_coursemodule() {
        return 'label';
    }
}

// EoF
