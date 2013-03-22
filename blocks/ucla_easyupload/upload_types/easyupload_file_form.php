<?php

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../quickform_file.php');

class easyupload_file_form extends easy_upload_form {
    var $allow_renaming = true;
    var $allow_js_select = true;

    private $draftitem = 0;

    function specification() {
        global $CFG;
        $mform =& $this->_form;

        // important to call this before the file upload
        $max_file_size = get_max_upload_file_size($CFG->maxbytes, $this->course->maxbytes);
        // CCLE-3833: For some reason, to fix this ticket the value of
        // 2147483648> (2GB>) somehow causes an integer overflow. So we just
        // set it to 2147483647 (-1). This problem only appears to happen for
        // the "uclafile" form type and not the filepicker.
        if ($max_file_size >= 2147483648) {
            $max_file_size = 2147483647;
        }
        $mform->setMaxFileSize($max_file_size);

        $mform->addElement('uclafile', 'repo_upload_file', 
            get_string('dialog_add_file_box', self::associated_block));
        $mform->addRule('repo_upload_file', '', 'required');

        $mform->addElement('hidden', 'files', false);

        // This is bad
        $files = $mform->getElement('files');
        $draftitem = $files->getValue();
        if ($draftitem === false) {
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
}
