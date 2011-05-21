<?php

class block_ucla_control_panel extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_ucla_control_panel');
    }
    
    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = 'Placeholder';
        return $this->content;
    }

    function instance_allow_config() {
        return true;
    }

    function applicable_formats() {
        return array(
            'site-index' => true,
            'course-view' => true,
            'course-format-week' => true,
            'my' => true,
            'blocks-ucla_control_panel' => false // this option prevents the block from being shown
        );
    }
}

/** eof
