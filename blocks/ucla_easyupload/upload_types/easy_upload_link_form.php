<?php

defined('MOODLE_INTERNAL') || die();

class easy_upload_link_form extends easy_upload_form {
    var $allow_renaming = true;
    var $allow_js_select = true;

    function specification() {
        $mform = $this->_form;

        $mform->addElement('url', 'externalurl', 
            get_string('dialog_add_link_box', 'block_ucla_control_panel'), 
            array('size' => 60), array('usefilepicker' => false));
    }

    function get_coursemodule() {
        return 'url';
    }
}
