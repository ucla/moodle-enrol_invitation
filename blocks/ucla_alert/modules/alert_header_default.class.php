<?php

require_once($CFG->dirroot . '/blocks/ucla_alert/lib.php');


class ucla_alertblock_header_default extends ucla_alertblock_module {
    
    function __construct($obj = null) {
        $obj = new stdClass();
        
        $obj->mod = 'header';
        $obj->type = 'default';
        $obj->title = 'all systems go!';
        $obj->subtitle  = 'As of ' . date('F j, Y');
        $obj->content = array(
            'content' => array(
                array(
                    'type' => 'msg',
                    'content' => 'You should be experiencing uninterrupted awesomeness on CCLE.',
                    'start' => null,
                    'end' => null,
                ),
                array(
                    'type' => 'list',
                    'color' => 'green',
                    'content' => 'Lorem ipsum dolor sit amet, consectetur 
                                adipiscing elit. Proin fringilla 
                                massa eget ante tristique condimentum. ', 
                    ),
                array(
                    'type' => 'link',
                    'link' => 'http://ccle.ucla.edu',
                    'color' => 'green',
                    'content' => 'Duis ultricies tortor vitae massa 
                                scelerisque eu blandit neque bibendum.'
                    ),
            ),
        );
        
        $this->defaults = array(
            'header' => array(
                'color' => 'default',            
            ),
            'list' => array(
                'color' => 'default',
            )
        );
        
        parent::__construct($obj);
    }
    
    function html_content() {
        return $this->get_title() . $this->get_body();
    }
    
    /**
     * Generates html content for alert block header title & body
     * 
     * @return string html 
     */
    protected function get_title() {
        // Override color, or use default based on module|type
        $alert = $this->defaults['header']['color'];
        
        // Create title
        $title = html_writer::tag('div', $this->prop->title,
                array('class' => 'alert-block-header-title alert-block-header-title-'.$alert));

        // Create subtitle
        $subtitle = '';
        if(!empty($this->prop->subtitle)) {
            $subtitle = html_writer::tag('div', $this->prop->subtitle, 
                    array('class' => 'alert-block-header-subtitle'));
        }
        
        // Output
        return html_writer::tag('div', $title . $subtitle, 
                array('class' => 'alert-block-header alert-block-header-'.$alert));
        
    }
}
