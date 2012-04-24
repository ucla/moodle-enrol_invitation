<?php

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../quickform_file.php');

class easyupload_file_form extends easy_upload_form {
    var $allow_renaming = true;
    var $allow_js_select = true;

    private $draftitem = 0;

    function specification() {
        $mform =& $this->_form;

        $this->set_maxsize();   // important to call this before the file upload
        
        $mform->addElement('uclafile', 'repo_upload_file', 
            get_string('dialog_add_file_box', self::associated_block));
        $mform->addRule('repo_upload_file', '', 'required');

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
            $ret = block_ucla_easyupload::upload($PAGE->context->id);
        }
        
        return $get_data;
    }

    function get_coursemodule() {
        return 'resource';
    }

    /**
     * Sets the form's _maxFileSize variable since for some reason, it isn't set 
     * by Moodle form object.
     */
    function set_maxsize() {
        global $CFG;
        $this->_form->_maxFileSize = get_max_upload_file_size($CFG->maxbytes, $this->course->maxbytes);                  
    }
}
