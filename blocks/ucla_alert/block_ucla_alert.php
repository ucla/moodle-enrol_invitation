<?php

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/ucla_alert/locallib.php');

class block_ucla_alert extends block_base {
    
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_alert');
        $this->content_type = BLOCK_TYPE_TEXT;
    }
    
    public function get_content() {
        global $COURSE;

        if ($this->content !== null) {
            return $this->content;
        }

        if($COURSE->id === SITEID) {
            $alertblock = new ucla_alert_block_site($COURSE->id);
        } else {
            $alertblock = new ucla_alert_block($COURSE->id);
        }
        
        $this->content = new stdClass;
        $this->content->text = $alertblock->render();

        return $this->content;
    }
    
    public function hide_header() {
        return true;
    }
    
    public function html_attributes() {
        $attributes = parent::html_attributes(); // Get default values
        $attributes['class'] .= ' block-ucla-alert ucla-alert'; // Append our class to class attribute
//        $attributes['id'] = 'ucla-alert';
        // Append alert style
        return $attributes;
    }
    
    public function applicable_formats() {
        return array(
            'site-index' => true,
            'course-view' => true,
            'my' => true,
        );
    }

}