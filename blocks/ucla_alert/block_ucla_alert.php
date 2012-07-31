<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/ucla_alert/lib.php');

class block_ucla_alert extends block_base {
    
    private $defaults;
    private $modules;
    
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_alert');
        $this->defaults = array('alert_default');
        
        // 
        $this->modules = array(
            'alert_header_default' => 'ucla_alertblock_header_default',
            'alert_body_default' => 'ucla_alertblock_body_default',
            );
    }
    
    public function get_content() {
        global $CFG;

        if ($this->content !== null) {
            return $this->content;
        }
        
        // Hook modules here
        $this->content = new stdClass;
        
        $this->content->text = $this->get_mod_content();
        
//        $this->content->footer = 'Footer here...';

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
    
    public function applicable_formats() {
        return array(
            'site-index' => true,
            'course-view' => false,
            'my' => true,
        );
    }
    
    static function register_alert($alert) {
        
    }
    
    private function get_mod_content() {
        global $CFG;
        
        $out = '';
        $path = $CFG->dirroot . '/blocks/ucla_alert/modules/';
        
        // Iterate through known mods
        // @todo need logic to determine header
        foreach($this->modules as $mod => $class) {
        
            if(file_exists($path . $mod . '.class.php')) {
                
                require_once $path .$mod . '.class.php';
                
                $load = new $class;
                $out .= $load->html_content();
            }
        }
        
        return $out;
        
//        $default = new ucla_alertblock_header_default();
//        $body = new ucla_alertblock_body_default();
//        return $default->html_content() . $body->html_content();
    }

}