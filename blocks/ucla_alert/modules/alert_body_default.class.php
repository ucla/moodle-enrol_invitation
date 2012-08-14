<?php

class ucla_alertblock_body_default extends ucla_alertblock_module {

    function __construct($obj = null) {
        // Default payload
        $content = array(
            'type' => 'msg',
            'content' => get_string('mod_header_default_msg', 'block_ucla_alert')
        );
        
        $payload = new stdClass();
        $payload->content = array((object)$content);
        $payload->overrides = null;
        
        // Create prop obj
        $obj = new stdClass();
        
        $obj->mod = 'body';
        $obj->type = 'default';
        $obj->title = get_string('mod_body_default_title', 'block_ucla_alert');
        $obj->payload = array($payload);
        
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
