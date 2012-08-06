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
        
        // Check if we have content to print out
        if(!empty($this->prop->content['content'])) {
            // Set default color
            $color = $this->defaults['list']['color'];

            // Print out content based on type
            foreach($this->prop->content['content'] as $content) {
                
                // Override color
                if(!empty($content['color'])) {
                    $color = $content['color'];
                }
                
                switch($content['type']) {
                    case 'msg':
                        $out .= html_writer::tag('p', $content['content'], 
                                array('class' => 'alert-block-msg-text'));
                        break;
                    case 'link':
                        $link = html_writer::link($content['link'], $content['content']);
                        $out .= html_writer::tag('div', $link,
                                array('class' => 'alert-block-list alert-block-list-link alert-block-list-'.$color));
                        break;
                    case 'list':
                        $out .= html_writer::tag('div', $content['content'],
                                array('class' => 'alert-block-list alert-block-list-'.$color));
                        break;
                }
            }
        }
                
        // Output
        return html_writer::tag('div', $out,
                array('class' => 'alert-block-msg'));
    }
        
    protected function get_inner_content() {
        global $DB;
        $recods = $DB->get_records('ucla_alerts', 
                array('mod' => $this->prop->mod, 'type' => $this->prop->type));
        
    }

    /**
     * All modules must generate their own html content 
     */
    abstract function html_content();

}


