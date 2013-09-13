<?php


class block_ucla_search extends block_base {
        
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_search');
    }
    
    public function get_content() {
        global $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }
        
        // Load YUI module
        $PAGE->requires->yui_module('moodle-block_ucla_search-search', 
                'M.ucla_search.init', 
                array(array('name' => 'block-search')));
        
        $this->content = new stdClass;

        // Write content
        $this->content->text = self::search_form();
        
        return $this->content;
    }
    
    public function applicable_formats() {
        return array(
            'site-index' => true,
            'course-view' => false,
            'my' => true,
        );
    }
    
    /**
     * Print advanced search form html for various components.  Compatible 
     * with default moodle search if javascript of off.
     * 
     * @global type $CFG
     * @param type $type
     * @return html
     */
    static function search_form($type = 'block-search') {
        global $CFG;
        
        // Default 
        $collab = true;
        $course = true;
        $visibility = 'hidden';
        $cssgrid = 'col-md-8 col-md-offset-2';
        $size = 'lg';
        
        switch ($type) {
            case 'frontpage-search':
                break;
            case 'course-search':
                $collab = false;
                $course = true;
                break;
            case 'collab-search':
                $collab = true;
                $course = false;
                break;
            case 'block-search':
                $visibility = '';
                $cssgrid = 'col-lg-12';
                $size = 'md';
        }
        
        $inputgroup = html_writer::div(
                html_writer::empty_tag('input', 
                        array(
                            'id' => 'ucla-search', 
                            'type' => 'text', 
                            'class' => 'form-control ucla-search-input', 
                            'name' => 'search',
                            'placeholder' => get_string('placeholder', 'block_ucla_search')
                            )) .
                html_writer::span(
                        html_writer::tag('button', 
                                html_writer::span('', 'glyphicon glyphicon-search'),
                                array('class' => 'btn btn-primary', 'type' => 'submit')
                                ),
                        'input-group-btn'
                        ),
                'input-group input-group-' . $size
                );
        
        $checkboxes = html_writer::div(
                html_writer::tag('label', 
                        html_writer::checkbox('collab', 1, $collab) . ' ' . get_string('collab', 'block_ucla_search'),
                        array('class' => 'checkbox-inline')
                        ) . 
                html_writer::tag('label', 
                        html_writer::checkbox('course', 1, $course) . ' ' . get_string('course', 'block_ucla_search'),
                        array('class' => 'checkbox-inline')
                        )
                );
        
        $form = html_writer::tag('form', 
                    html_writer::span(get_string('show', 'block_ucla_search'), $visibility) .
                    html_writer::tag('fieldset', $checkboxes, array('class' => $visibility)) .
                    $inputgroup, 
                    array('class' => '', 'action' => $CFG->wwwroot . '/course/search.php')
                );
        
        $grid = html_writer::div(html_writer::div($form, $cssgrid), 'row ucla-search ' . $type);
        
        return $grid;   
    }
    
}