<?php

defined('MOODLE_INTERNAL') || die();

class block_ucla_alert extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_alert');
    }
    
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }
        
        // Hook modules here
        $this->content = new stdClass;
        $this->content->text = 'The content of our SimpleHTML block!';
        $this->content->footer = 'Footer here...';

        return $this->content;
    }
    
    public function hide_header() {
        return true;
    }
    
    public function html_attributes() {
        $attributes = parent::html_attributes(); // Get default values
        $attributes['class'] .= ' block-ucla-alert'; // Append our class to class attribute
        // Append alert style
        return $attributes;
    }
    
    function applicable_formats() {
        return array(
            'site-index' => true,
            'course-view' => false,
            'my' => true,
        );
    }
}