<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

class easyupload_file_form extends easy_upload_form {
    var $allow_renaming = true;
    var $allow_js_select = true;

    private $draftitem = 0;

    function specification() {
        global $CFG;
        $mform =& $this->_form;

        // important to call this before the file upload
        $maxfilesize = get_max_upload_file_size($CFG->maxbytes, $this->course->maxbytes);
        // CCLE-3833: For some reason, to fix this ticket the value of
        // 2147483648> (2GB>) somehow causes an integer overflow. So we just
        // set it to 2147483647 (-1). This problem only appears to happen for
        // the "uclafile" form type and not the filepicker.
        if ($maxfilesize >= 2147483648) {
            $maxfilesize = 2147483647;
        }

        $filemanageropts = array();
        $filemanageropts['accepted_types'] = '*';
        $filemanageropts['maxbytes'] = $maxfilesize;
        $filemanageropts['maxfiles'] = 1;
        $filemanageropts['mainfile'] = true;

        $mform->addElement('filemanager', 'files', get_string('dialog_add_file', self::associated_block), null, $filemanageropts);
        $mform->addRule('files', '', 'required');
    }

    /**
     *  Simplified version of mod_resource_mod_form:data_preprocessing()
     *  from /mod/resource/mod_form:160 in Moodle 2.5.1
     * 
     *  Removed display options that are not included in this form.
     */
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance and !$this->current->tobemigrated) {
            $draftitemid = file_get_submitted_draft_itemid('files');
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_resource', 'content', 0, array('subdirs'=>true));
            $defaultvalues['files'] = $draftitemid;
        }
    }

    function get_coursemodule() {
        return 'resource';
    }
}
