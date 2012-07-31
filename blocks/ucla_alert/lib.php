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
                $out .= html_writer::tag('p', $msg['content'], 
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


