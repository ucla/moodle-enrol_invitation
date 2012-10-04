<?php


class block_ucla_search extends block_base {
    
    const SEARCH_LIMIT = 10;
    
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_search');
    }
    
    public function get_content() {
        global $CFG;

        if ($this->content !== null) {
            return $this->content;
        }
        
        // Load YUI JS
        self::load_search_js();

        $this->content = new stdClass;

        // Fallback form for non-js 
        $search_url = $CFG->wwwroot . '/course/search.php';
        
        $checkboxes = self::print_collab_checkboxes('form', '', '<br/>');
        
        $label = html_writer::tag('label', 'Search sites...', 
                array('for' => 'coursesearchbox'));
        $input = html_writer::tag('input', '',
                array('id' => 'coursesearchbox', 'style' => 'width: 97%;',
                    'size' => '30', 'name' => 'search', 'type' => 'text', 'value' => ''));
        $button = html_writer::tag('input', '', 
                array('value' => 'Go', 'type' => 'submit'));
        $fieldset = html_writer::tag('fieldset', $checkboxes . $label . $input . $button,
                array('class' => 'coursesearchbox invisiblefieldset'));
        
        $fallback = html_writer::tag('form', $fieldset,
                array('action' => $search_url, 'id' => 'coursesearch2',
                    'style' => 'text-align: left;'));

        // YUI searchbox
        $checkboxes = self::print_collab_checkboxes('check', 'as-collab-checkbox', '<br/>');
        
        $wrapper = self::print_collab_searchbox('advanced-search', 'as-search-wrapper', $checkboxes);
        
        // Write content
        $this->content->text = $fallback . $wrapper;
        
        return $this->content;
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
    
    /**
     * Load YUI for searchbox
     * 
     * @param type $alwayshowlist 
     */
    static function load_search_js($alwayshowlist = true) {
        global $PAGE, $CFG;
        
        $rest_url = $CFG->wwwroot . '/blocks/ucla_search/rest.php';
        $search_url = $CFG->wwwroot . '/course/search.php';
        
        $PAGE->requires->js('/blocks/ucla_search/module.js');
        $PAGE->requires->js_init_call('M.ucla_search.init', 
                array($rest_url, $search_url, self::SEARCH_LIMIT, $alwayshowlist));
    }
    
    /**
     * Load YUI JS for browse-by searchbox
     * @param type $collab 
     */
    static function load_browseby_search_js($collab = false) {
        global $PAGE, $CFG;
        
        $rest_url = $CFG->wwwroot . '/blocks/ucla_search/rest.php';
        $search_url = $CFG->wwwroot . '/course/search.php';
        
        $PAGE->requires->js('/blocks/ucla_search/module.js');
        $PAGE->requires->js_init_call('M.ucla_search_browseby.init', 
                array($rest_url, $search_url, self::SEARCH_LIMIT, false, $collab));
    }
    
    /**
     * Print browse-by YUI searchbox
     * 
     * @return type 
     */
    static function print_browseby_search() {
        return self::print_collab_searchbox('advanced-search-browseby', 'as-search-wrapper-page');
    }
    
    /**
     * Print standard search page YUI searchbox with non-js fallback
     * 
     * @param type $search
     * @param type $collab
     * @param type $course
     * @return string html
     */
    static function print_course_search($search, $collab = 1, $course = 1) {
        global $CFG;

        $search_url = $CFG->wwwroot . '/course/search.php';

        $collab_div = self::print_collab_checkboxes('form', '', '', $collab, $course);

        // Non javascript form fallback
        $label = html_writer::tag('label', 'Search sites: ', 
                array('for' => 'coursesearchbox'));
        $input = html_writer::tag('input', '',
                array('id' => 'coursesearchbox', 
                    'size' => '30', 'name' => 'search', 'type' => 'text', 'value' => $search));
        $button = html_writer::tag('input', '', 
                array('value' => 'Go', 'type' => 'submit'));
        $fieldset = html_writer::tag('fieldset', $collab_div . $label . $input . $button,
                array('class' => 'coursesearchbox invisiblefieldset', 'style' => 'width: 100%'));

        $fallback = html_writer::tag('form', $fieldset,
                array('action' => $search_url, 'id' => 'coursesearch',
                    'style' => 'text-align: left;'));

        $collab_div = self::print_collab_checkboxes('check', 'as-collab-checkbox', '', $collab, $course);
        
        $wrapper = self::print_collab_searchbox('advanced-search', 'as-search-wrapper-page', $collab_div, $search);

        // Write content
        return $fallback . $wrapper;
     
    }
    
    /**
     * Print YUI searchbox
     * 
     * @param type $searchid
     * @param type $wrapperid
     * @param type $content
     * @param type $search
     * @return string html 
     */
    static function print_collab_searchbox($searchid, $wrapperid, $content = '', $search = '') {
        // Input box + wrapper
        $input = html_writer::tag('input', '', array('id' => $searchid, 'value' => $search,
            'placeholder' => get_string('search_placeholder', 'block_ucla_search')));
        $input_wrapper = html_writer::tag('div', $input,
                array('class' => 'as-input-wrapper'));
        $wrapper = html_writer::tag('div', $content . $input_wrapper, array('id' => $wrapperid));

        // Write content
        return $wrapper;
    }
    
    /**
     * Print checkboxes
     * 
     * @param type $id
     * @param type $class
     * @param type $break
     * @param type $collab
     * @param type $course
     * @return string html 
     */
    static function print_collab_checkboxes($id, $class = '', $break = '', $collab = 1, $course = 1) {

        $collab_check = empty($collab) ? '' : 'checked';
        $course_check = empty($course) ? '' : 'checked';

        // Collab filter
        $checkbox = html_writer::tag('input', '', 
                array('type' => 'checkbox', 'name' => 'collab', 'id' => 'as-collab-'.$id, 'value' => 1, $collab_check => ''));
        $label = html_writer::tag('label', get_string('search_collab', 'block_ucla_search'),
                array('for' => 'as-collab-check'));
        $checkbox_course = html_writer::tag('input', '', 
                array('type' => 'checkbox', 'name' => 'course', 'id' => 'as-course-'.$id, 'value' => 1, $course_check => ''));
        $label_course = html_writer::tag('label', get_string('search_course', 'block_ucla_search'),
                array('for' => 'as-course-check'));
        // Container
        $collab_div = html_writer::tag('div', get_string('search_show', 'block_ucla_search') . ': ' . $break . $checkbox . $label . $break . $checkbox_course . $label_course,
                array('class' => $class));
        
        return $collab_div;
    }
    
}