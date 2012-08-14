<?php

require_once($CFG->dirroot . '/blocks/ucla_alert/lib.php');


class ucla_alertblock_header_default extends ucla_alertblock_module {
    
    function __construct($obj = null) {
        $content = array(
            'type' => 'msg',
            'content' => get_string('mod_header_default_msg', 'block_ucla_alert')
        );
        
        $payload = new stdClass();
        $payload->content = array((object)$content);
        $payload->overrides = null;
        
        $obj = new stdClass();
        
        $obj->mod = 'header';
        $obj->type = 'default';
        $obj->title = get_string('mod_header_default_title', 'block_ucla_alert');
        $obj->subtitle  = 'As of ' . date('F j, Y');
        $obj->payload = array($payload);
        
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
    protected function get_title($title = null) {
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
    
    public function get_editor() {
        $alert = $this->defaults['header']['color'];
        
        return html_writer::tag('div', '$title . $subtitle', 
                array('class' => 'alert-block-header-'.$alert));
    }
}
