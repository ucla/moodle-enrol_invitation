<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');

class block_ucla_group_manager extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_ucla_group_manager');
    }

    function instance_allow_multiple() {
        return false;
    }

    function applicable_formats() {
        return array('all' => false, 'not-really' => true); 
    }

    function get_content() {
        return null;
    }

    // TODO hook into control panel
}
