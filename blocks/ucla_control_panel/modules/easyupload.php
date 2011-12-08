<?php

defined('MOODLE_INTERNAL') || die();

class ucla_cp_module_easyupload extends ucla_cp_module {

    function __construct($submodule) {
        global $CFG;

        // Let all the auto__() functions handle it
        parent::__construct($submodule);
    }

    function validate() {
        if (!parent::validate()) {
            return false;
        }

        // We need to make sure that we actually have the ability to use
        // easy upload and such
        global $CFG;
        
        // Cheap caching hack
        $eu_exists = false;
        // You can manually check this, but the following FS check shouldn't
        // be too expensive
        if (isset($CFG->control_panel_easyupload_link_established)) {
            $eu_exists = $CFG->control_panel_easyupload_link_established;
        } else {
            $easyuploadpath = $CFG->dirroot 
                . '/blocks/ucla_easyupload/block_ucla_easyupload.php';

            if (file_exists($easyuploadpath)) {
                require_once($easyuploadpath);

                $eu_exists = true;
            } else {
                $eu_exists = false;
            }
        }

        if ($eu_exists) {
            $name = $this->get_key();
            $type = str_replace('add_' , '', $name);
           
            // This one is probably more expensive
            return block_ucla_easyupload::upload_type_exists($type);
        }

        return false;
    }

    function autotag() {
        return array('ucla_cp_mod_common');
    }

    function autocap() {
        return 'moodle/course:update';
    }
}
