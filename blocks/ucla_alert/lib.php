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
        global $DB;
        
        $out = '';

        // Get visible records for module
        $records = $DB->get_records('ucla_alert', 
                array('module' => $this->prop->mod, 'type' => $this->prop->type, 'visible' => 1),
                'sortorder');
        
        foreach($records as $rec) {
            $content = json_decode($rec->content);
                        
            // Check for links
            if(strstr($content->content, '| http') || strstr($content->content, '|http')) {
                $link = explode('|', $content->content);
                $text = $link[0];
                $url = $link[1];
                
                $content->content = html_writer::link($url, $text);
                
                if(empty($content->type) || $content->type == 'title' || $content->type == 'msg') {
                    $content->type = 'blue';
                }
            }
            
            switch($content->type) {
                default:
                case 'msg':
                    $out .= html_writer::tag('p', $content->content, 
                            array('class' => 'alert-block-msg-text'));
                    break;
                case 'title':
                    $out .= html_writer::tag('span', $content->content, 
                            array('class' => 'alert-block-body-subtitle'));
                    break;
                case 'blue':
                case 'cyan':
                case 'green':
                case 'orange':
                case 'red':
                    $out .= html_writer::tag('div', $content->content,
                                array('class' => 'alert-block-list alert-block-list-'.$content->type));
                    break;
                }
        }
        
        // Output
        return html_writer::tag('div', $out,
                array('class' => 'alert-block-msg'));    
    }
        
    /**
     * All modules must generate their own html content 
     */
    abstract function html_content();

}


