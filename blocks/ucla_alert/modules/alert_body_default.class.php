<?php

class ucla_alertblock_body_default extends ucla_alertblock_module {

    function __construct($obj = null) {
        $obj = new stdClass();
        
        $obj->mod = 'body';
        $obj->type = 'default';
        $obj->title = get_string('mod_body_default_title', 'block_ucla_alert');
        $obj->content = array(
            'main' => array(
                array(
                    'content' => 'this is a message',
                    'start' => null,
                    'end' => null,
                ),
                
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
