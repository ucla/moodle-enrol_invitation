<?php

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/ucla_alert/locallib.php');

class block_ucla_alert extends block_base {
    
    public function init() {
        $this->title = get_string('pluginname', 'block_ucla_alert');
        $this->content_type = BLOCK_TYPE_TEXT;
    }
    
    public function get_content() {
        global $COURSE, $PAGE, $CFG;
        
        if ($this->content !== null) {
            return $this->content;
        }

        // Prepare to render block content
        $this->content = new stdClass;

        // If the alert banner is displaying, we don't want to display block
        if(get_config('block_ucla_alert', 'alert_sitewide') && $COURSE->id === SITEID) {
            $this->content->text = '';
            
        } else {
            // Get alert block renderer and display
            $alertblock = new ucla_alert_block($COURSE->id);
            $this->content->text = $alertblock->render();
        }

        // If 'editing' is ON, then we display a button to edit the 
        // alert block contents
        if($PAGE->user_is_editing()) {
            
            // Make sure proper pemissions are set
            $context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
            
            if(has_capability('moodle/course:update', $context)) {
                $editurl = $CFG->wwwroot . '/blocks/ucla_alert/edit.php?id=' . $COURSE->id;

                $edit = new html_element('a', 'Edit');
                $edit->add_class('btn alert-block-edit-on-btn');
                $edit->add_attrib('href', $editurl);

                $box = new alert_html_box_content($edit);
                $box->add_class('alert-block-edit-on-box');

                // Render this in the footer
                $this->content->footer = $box->render();
            }
        }
        
        // Load required modules
        // Temporarily disabling tweet module to prevent security warning over
        // calling non-encrypted twitter API
//        $PAGE->requires->yui_module('moodle-block_ucla_alert-tweet', 
//                'M.ucla_alert_tweet.init_tweets', 
//                array(array('post_url' => $CFG->wwwroot . '/blocks/ucla_alert/rest.php')));
        
        return $this->content;
    }
    
    public function hide_header() {
        return true;
    }
    
    public function html_attributes() {
        $attributes = parent::html_attributes(); // Get default values
        $attributes['class'] .= ' block-ucla-alert ucla-alert'; // Append our class to class attribute
//        $attributes['id'] = 'ucla-alert';
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

    /**
     * Installs base alert block elements when block is installed 
     * via 'add blocks' dropdown
     * 
     * @global type $COURSE 
     */
    public function instance_create() {
        global $COURSE;
        
        $alert = new ucla_alert_block($COURSE->id);
        $alert->install();
    }
    
    /**
     * Deletes all alert records when block is removed
     * 
     * @global type $DB
     * @global type $COURSE
     * @return boolean 
     */
    public function instance_delete() {
        global $DB, $COURSE;

        // The SITE block exists in two pages, my-sites and frontpage so it 
        // has two instances.  
        // When the block is deleted from the front page (site-index), it 
        // will also delete it from my-sites. 
        if(intval($COURSE->id) === intval(SITEID)) {
            $query = "SELECT *
                      FROM {block_instances}
                      WHERE 
                          blockname = 'ucla_alert'
                      AND pagetypepattern = 'my-index'
                      AND id <> :id";
            
            if($records = $DB->get_records_sql($query, array('id' => $this->instance->id))) {
                $instance = array_pop($records);
                blocks_delete_instance($instance);
            }
        }
        
        // Delete records from alert table
        return $DB->delete_records(ucla_alert::DB_TABLE, 
                array('courseid' => $COURSE->id));
    }

}