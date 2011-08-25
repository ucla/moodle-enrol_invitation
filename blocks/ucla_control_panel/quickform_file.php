<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/pear/HTML/QuickForm/file.php');
require_once($CFG->dirroot . '/lib/form/filepicker.php');
require_once($CFG->dirroot . '/lib/form/file.php');

class MoodleQuickForm_uclafile extends MoodleQuickForm_file {
    private $_filepicker;

    private $_draftid = false;

    function MoodleQuickForm_uclafile($elname=null, $ellabel=null, $attr=null) {
        parent::HTML_QuickForm_file($elname, $ellabel, $attr);
    }

}

