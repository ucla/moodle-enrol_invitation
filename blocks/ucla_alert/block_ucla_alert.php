<?php

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/ucla_alert/locallib.php');

class block_ucla_alert extends block_base {
    
    private $defaults;
    private $modules;
    
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_alert');
        $this->defaults = array('alert_default');
        
        // 
        $this->modules = array(
            'alert_header_default' => 'ucla_alertblock_header_default',
            'alert_body_default' => 'ucla_alertblock_body_default',
            );
    }
    
    public function get_content() {
        global $CFG;

        if ($this->content !== null) {
            return $this->content;
        }
        
        $b = new ucla_alert_block_site(23);
        
        // Hook modules here
        $this->content = new stdClass;
        
//        $this->content->text = $this->get_mod_content();
        $this->content->text = $b->render();
        
//        $this->content->footer = 'Footer here...';

        return $this->content;
    }
    
    public function hide_header() {
        return true;
    }
    
    public function html_attributes() {
        $attributes = parent::html_attributes(); // Get default values
        $attributes['class'] .= ' block-ucla-alert'; // Append our class to class attribute
        $attributes['id'] = 'ucla-alert';
        // Append alert style
        return $attributes;
    }
    
    public function applicable_formats() {
        return array(
            'site-index' => true,
            'course-view' => true,
            'my' => true,
        );
    }
    
    static function alert_edit_js() {
        global $PAGE;
        
        $PAGE->requires->js('/blocks/ucla_alert/module.js');
        $PAGE->requires->js_init_call('M.alert_block.init', array());
    }
    
    static function write_alert_edit_ui() {
        
        // Supported colors
        $colors = array(
            'blue' => 'primary', 
            'cyan' => 'info', 
            'green' => 'success', 
            'orange' => 'warning', 
            'red' => 'danger'
            );

        // Start creating buttons
        $buttons = array();

        $buttons[] = html_writer::tag('button', 'Title', 
                array('class' => 'alert-edit-title btn btn-mini'));
        $buttons[] = html_writer::tag('button', 'Message', 
                array('class' => 'alert-edit-message btn btn-mini'));
        // Add color buttons
        foreach($colors as $k => $v) {
            $buttons[] = html_writer::tag('button', '&blacksquare;', 
                array('class' => 'alert-edit-list-color btn btn-mini btn-'.$v, 'rel' => $k));
        }
        
        //@todo: add alert edit somehow..
//        $buttons[] = html_writer::tag('button', 'Edit',
//                array('class' => 'alert-edit-edit btn btn-mini'));
        $buttons[] = html_writer::tag('button', 'Delete',
                array('class' => 'alert-edit-delete btn btn-mini'));

        // Button HTML
        $buttons_html = implode('', $buttons);

        $buttons_div = html_writer::tag('div', $buttons_html,
                array('class' => 'alert-edit-buttons'));

        $out = '';

        // @todo: abstract so that each module has a list of standard settings
        // ...for now hard-code
        // 
        // Headers
        $out .= html_writer::tag('h3', 'Inactive alerts');
        $out .= html_writer::tag('h3', 'Active alerts');

        // Inactive list
//        $out .= html_writer::tag('h4', 'Staging area');
        $mod = array('module' => 'body', 'type' => 'default', 'visible' => 0);
        $out .= self::write_edit_items_list($buttons_div, $mod, 'alert-inactive-list');
        
        //
        $out .= html_writer::tag('div', '', array('class' => 'alert-edit-temp'));
        
        // Header list
        $out .= html_writer::tag('h4', 'Header notices');
        $mod = array('module' => 'header', 'type' => 'default', 'visible' => 1);
        $out .= self::write_edit_items_list($buttons_div, $mod, 'alert-active-list-header');
        
        // Active list
        $out .= html_writer::tag('h4', 'CCLE notices');
        $mod = array('module' => 'body', 'type' => 'default', 'visible' => 1);
        $out .= self::write_edit_items_list($buttons_div, $mod, 'alert-active-list');
        
        // Save button
        $out .= html_writer::tag('button', 'Save changes', array('class' => 'btn', 'id' => 'alert-save'));
        
        // Print out content
        return html_writer::tag('div', $out, 
                array('id' => 'alert-block-edit'));

    }
    
    static function write_edit_items_list(&$buttons, $mod, $class) {
        global $DB;
        
        $items = array();
        $records = $DB->get_records('ucla_alert', $mod, 'sortorder');

        foreach($records as $r) {
            $o = json_decode($r->content);

            $css = '';

            // Aply visible styling
            if(!empty($o->type)) {
                switch($o->type) {
                    case 'title':
                        $css = 'alert-block-body-subtitle';
                        break;
                    case 'msg':
                        $css = '';
                        break;
                    case 'blue':
                    case 'cyan':
                    case 'green':
                    case 'orange':
                    case 'red':
                        $css = 'alert-block-list-' . $o->type;
                }
            }

            $content_div = html_writer::tag('div', $o->content,
                array('class' => 'alert-edit-content '.$css, 'alerttype' => $o->type, 'alertid' => $r->id));

            $items[] = html_writer::tag('li', $buttons . $content_div, 
                    array('class' => $class));
        }

        $items_html = implode('', $items);
        $ul = html_writer::tag('ul', $items_html, array('id' => $class));
        
        return $ul;
    }
    
    private function get_mod_content() {
        global $CFG, $OUTPUT, $PAGE;
        
        $out = '';
        $path = $CFG->dirroot . '/blocks/ucla_alert/modules/';
        
        // Iterate through known mods
        // @todo need logic to determine header
        foreach($this->modules as $mod => $class) {
        
            if(file_exists($path . $mod . '.class.php')) {
                
                require_once $path .$mod . '.class.php';
                
                $load = new $class;
                $out .= $load->html_content();
            }
        }

        // @todo: limit to admins
        if($PAGE->user_allowed_editing()) {
            // Get image
            $pix = $OUTPUT->pix_url('gear', 'block_ucla_alert');
            $img = html_writer::tag('img', '', array('src' => $pix));
            
            // Get url
            $url = new moodle_url($CFG->wwwroot . '/blocks/ucla_alert/edit.php');
            $link = html_writer::link($url, 'Edit alerts');
            $span = html_writer::tag('span', $link);
            
            // Write image + link
            $out .= html_writer::tag('div', $img . $span, array('class' => 'alert-edit-settings'));
        }
        
        return $out;
    }

}