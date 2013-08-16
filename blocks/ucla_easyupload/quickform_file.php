<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/pear/HTML/QuickForm/file.php');
require_once($CFG->dirroot . '/lib/form/filepicker.php');
// The following required file has been removed from Moodle 2.5:
// require_once($CFG->dirroot . '/lib/form/file.php');

/**
 * This class is deprecated.  It extends MoodleQuickForm_file, 
 * which has been deprecated since Moodle 2.0. 
 * 
 * Use MoodleQuickForm_filepicker instead (/lib/form/filepicker.php).
 */
class MoodleQuickForm_uclafile extends MoodleQuickForm_file {
    private $_filepicker;

    private $_draftid = false;

    function MoodleQuickForm_uclafile($elname=null, $ellabel=null, $attr=null) {
        parent::HTML_QuickForm_file($elname, $ellabel, $attr);
    }

}

