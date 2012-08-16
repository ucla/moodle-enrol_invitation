<?php


class block_ucla_search extends block_base {
    
    private $search_result_limit;
    
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_search');
        
        // Limit number of results returned
        $this->search_result_limit = 10;
    }
    
    public function get_content() {
        global $CFG;

        if ($this->content !== null) {
            return $this->content;
        }
        
        // Load YUI JS
        $this->load_js();

        $this->content = new stdClass;

        // Fallback form for non-js 
//        $fallback = html_writer::tag();

        // Collab search checkbox
        $checkbox = html_writer::tag('input', '', 
                array('type' => 'checkbox', 'name' => 'as-collab-checkbox', 'id' => 'as-collab-check'));
        $label = html_writer::tag('label', get_string('search_collab', 'block_ucla_search'),
                array('for' => 'as-collab-check'));
        $collab = html_writer::tag('div', $checkbox . $label,
                array('class' => 'as-collab-checkbox'));
        
        // Input box + wrapper
        $input = html_writer::tag('input', '', array('id' => 'advanced-search', 
            'placeholder' => get_string('search', 'block_ucla_search')));
        $wrapper = html_writer::tag('div', $input, array('id' => 'as-search-wrapper'));

        // Write content
        $this->content->text = $collab . $wrapper;
        
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
    
    private function load_js() {
        global $PAGE, $CFG;
        
        $rest_url = $CFG->wwwroot . '/blocks/ucla_search/rest.php';
        
        $PAGE->requires->js('/blocks/ucla_search/module.js');
        $PAGE->requires->js_init_call('M.ucla_search.init', array($rest_url, $this->search_result_limit));
    }

}