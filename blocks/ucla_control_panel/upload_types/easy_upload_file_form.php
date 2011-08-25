<?php

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../quickform_file.php');

class easy_upload_file_form extends easy_upload_form {
    var $allow_renaming = true;
    var $allow_js_select = true;

    private $draftitem = 0;

    function specification() {
        $mform = $this->_form;

        $mform->addElement('uclafile', 'repo_upload_file', 
            get_string('dialog_add_file_box', 'block_ucla_control_panel'));

        $mform->addElement('hidden', 'files', false);

        // This is bad
        $files = $mform->getElement('files');
        $draftitem = $files->getValue();
        if ($draftitem  === false) {
            $draftitem = file_get_unused_draft_itemid();
            $files->setValue($draftitem);
        }

        $mform->addElement('hidden', 'itemid', $draftitem);
    }

    function get_data() {
        global $PAGE;

        $get_data = parent::get_data();
        if ($get_data) {
            $ret = easyupload::upload($PAGE->context->id);
        }
        
        return $get_data;
    }

    function get_coursemodule() {
        return 'resource';
    }
}
