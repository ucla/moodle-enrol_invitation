<?php


class block_ucla_search extends block_base {
    
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_search');
        $this->alert_edit_js();
    }
    
    public function get_content() {
        global $CFG;

        if ($this->content !== null) {
            return $this->content;
        }
        
        // Hook modules here
        $this->content = new stdClass;
        
        $input = html_writer::tag('input', '', array('id' => 'advanced-search', 
            'placeholder' => 'Search...'));
        $wrapper = html_writer::tag('div', $input, array('id' => 'as-search-wrapper'));
        $out = html_writer::tag('div', $wrapper, array('class' => 'ac-search-div'));
        
        $temp = '
            <div class="as-search-result">
            temporary text
            </div>
            ';
        
        $this->content->text = $wrapper;
        
//        $this->content->footer = 'Footer here...';

        return $this->content;
    }
    
    public function hide_header() {
        return false;
    }
    
    public function html_attributes() {
        $attributes = parent::html_attributes(); // Get default values
        $attributes['class'] .= ' block-ucla-search'; // Append our class to class attribute
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
    
    private function alert_edit_js() {
        global $PAGE, $CFG;
        
        $rest_url = $CFG->wwwroot . '/blocks/ucla_search/rest.php';
        $course_url = $CFG->wwwroot . '/course/view.php?id=';
        
        $PAGE->requires->js('/blocks/ucla_search/module.js');
        $PAGE->requires->js_init_call('M.ucla_search.init', array($rest_url, $course_url));
    }
    


}