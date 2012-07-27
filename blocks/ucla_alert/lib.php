<?php

//require_once(dirname(__FILE__) . '/../../../config.php');

// library file for alert block


/**
 *  An alert block module must extend this abstract class
 */
abstract class ucla_alertblock_module {
    
    // Basic module properties
    protected $prop;

    // Module defined defaults
    protected $defaults;

    function __construct($obj) {
        
        // Create property object
        foreach($obj as $k => $v) {
            $this->prop->$k = $v;
        }
    }

    /**
     * Generic function to generate the header body message html
     * 
     * @return string html 
     */
    protected function get_body() {
        
        $out = '';
        
        // Put messages items in <p> tags
        if(!empty($this->prop->content['main'])) {
            foreach($this->prop->content['main'] as $msg) {
                $out .= html_writer::tag('p', $msg, 
                        array('class' => 'alert-block-msg-text'));
            }
        }

        // Put list items in a <div> list
        if(!empty($this->prop->content['list'])) {
            foreach($this->prop->content['list'] as $list) {
                
                $color = $this->defaults['list']['color'];
                if(!empty($list['color'])) {
                    $color = $list['color'];
                }
                
                if(empty($list['link'])) {
                    $out .= html_writer::tag('div', $list['content'],
                            array('class' => 'alert-block-list alert-block-list-'.$color));
                } else {
                    $link = html_writer::link($list['link'], $list['content']);
                    $out .= html_writer::tag('div', $link,
                            array('class' => 'alert-block-list alert-block-list-link alert-block-list-'.$color));
                }
            }
        }
        
        // Output
        return html_writer::tag('div', $out,
                array('class' => 'alert-block-msg'));
    }
    
    function available() {
        if(empty($this->prop->start)) {
            return true;
        }
    }
    
    function expired() {
        if(empty($this->prop->end)) {
            return false;
        }
    }
    
    protected function get_mod_type_content() {
        global $DB;
        
        
    }

    abstract function html_content();

}

class ucla_alertblock_content {
    
    public $main;
    public $list;
    
    function __construct() {
        $this->main = array();
        $this->list = array();
    }
    
    function expired() {
        return false;
    }
    
    function active() {
        return true;
    }
}

class ucla_alertblock_body_default extends ucla_alertblock_module {

    function __construct($obj = null) {
        $obj = new stdClass();
        
        $obj->mod = 'body';
        $obj->type = 'default';
        $obj->title = get_string('mod_body_default_title', 'block_ucla_alert');
        $obj->content = array(
            'main' => array(
                'this is a message',
            ),
            'list' => array(
                array(
                    'content' => 'Maecenas accumsan ante quis lacus pulvinar a rutrum nibh lobortis.',
                    'color' => 'blue',

                ),
                array(
                    'content' => 'Donec eu tortor vel sapien interdum viverra.',
                    'link' => 'http://www.foo.com',
                ),
            ),
        );
        
        $obj->start = null;
        $obj->end = null;
        
        $this->defaults = array(
            'list' => array(
                'color' => 'default',
            )
        );
        
        parent::__construct($obj);
    }
    
    function html_content() {
        return $this->get_title() . $this->get_body();
    }
    
    protected function get_title() {
        
        $title = html_writer::tag('div', $this->prop->title, 
                array('class' => 'alert-block-body-title'));
        
        return $title;
    }
        
}

class ucla_alertblock_header_default extends ucla_alertblock_module {
    
    function __construct($obj = null) {
        $obj = new stdClass();
        
        $obj->mod = 'header';
        $obj->type = 'default';
        $obj->title = 'all systems go!';
        $obj->subtitle  = 'As of ' . date('F j, Y');
        $obj->content = array(
            'main' => array(
                'You should be experiencing uninterrupted awesomeness on CCLE.',
            ),
            'list' => array(
                array( 
                    'link' => null,
                    'color' => 'green',
                    'content' => 'Lorem ipsum dolor sit amet, consectetur 
                                adipiscing elit. Proin fringilla 
                                massa eget ante tristique condimentum. ', 
                    ),
                array(
                    'link' => 'http://ccle.ucla.edu',
                    'color' => 'green',
                    'content' => 'Duis ultricies tortor vitae massa 
                                scelerisque eu blandit neque bibendum.'
                    ),
                ),
        );
        $obj->start = null;
        $obj->end = null;
        
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
