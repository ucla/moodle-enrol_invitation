<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/course/lib.php');

class block_ucla_office_hours extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_office_hours');
    }
    
    public function get_content() {
        if($this->content !== null) {
            return $this->content;
            print_object($this->content->text);
        }
        
        $this->content = new stdClass;
        $this->content->text = 'The content goes here';
        $this->content->footer = 'Footer goes here';
        print_object($this->content->text);
        return $this->content;
    }
}

?>
