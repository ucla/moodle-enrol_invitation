<?php

defined('MOODLE_INTERNAL') || die();

class block_bruincast extends block_base {

    private $active; 

    /**
      * Called by moodle
      **/
    public function init() {
        global $DB, $COURSE; // get global variables
      
        $this->active = false; // fallback on $active

        /** check if block should be set active **/
        $courseid =$COURSE->id;
        $coursefound = $DB->get_record('course', array('id'=>$courseid)); // get course record in db
        
        if (!empty($coursefound)) {
            $coursesrs = substr($coursefound->idnumber, strpos($coursefound->idnumber,'-')+1); // generate the courseid as in bruincast table
           
            if (!empty($coursesrs)) {
                $bcnodes = $DB->get_record('ucla_bruincast', array('srs'=>$coursesrs)); // get entry in bruincast table by the generated courseid
            }

            if (!empty($bcnodes)) {

                // get course record again, uses info from bc table for double checking      
                $course = $DB->get_record('course', array('idnumber'=>($bcnodes->term.'-'.$bcnodes->srs))); 

                if (!empty($course) && !empty($bcnodes->bruincast_url)) {

                    if (strcmp($bcnodes->restricted, "Restricted")==0) {

                        // get contexts for permission checking
                        $context = get_context_instance(CONTEXT_COURSE, $courseid); 
                    
                        // check if has permission, set block to active if does
                        if (is_enrolled($context) || has_capability('moodle/site:config', $context)) {
                            $this->active = true;
                        }
    
                    } else { // if not restricted, no need for restriction checking
                        $this->active = true;
                    }                    
     
                }
            }   
        }

        // initialize title and name
        $this->title = get_string('title', 'block_bruincast');
        $this->name = get_string('pluginname', 'block_bruincast');
    }

    /**
      * Called by moodle
      **/
    public function get_content() {
        global $COURSE;

        $courseid = $COURSE->id;
        $context = get_context_instance(CONTEXT_BLOCK, $courseid);

        if ($this->content !== null) {
            return $this->content;
        }
            
        $this->content = new stdClass;
        
        // hide block if not is role block/bruincast:viewblock
        if (!has_capability('block/bruincast:viewblock', $context)) {
            return $this->content;
        }

        // set block content by whether or not course contents found
        if ($this->active) {
            $this->content->text   = get_string('contfound', 'block_bruincast');
        } else {
            $this->content->text   = get_string('contnotfound', 'block_bruincast');
        }

        $this->content->footer     = get_string('footer', 'block_bruincast');

        return $this->content;
    }

    /**
      * Use UCLA Course menu block hook
      **/
    public function get_navigation_nodes($course) {
        // get global variable
        global $DB;
                                    
        $courseid = $course->id; // course id from the hook function
        $coursefound = $DB->get_record('course', array('id'=>$courseid)); // get course record in db

        $nodes = array(); // initialize $nodes with an empty array for a good fallback; the empty array has no effect in coursemenu block hook.

        $present = false;

        // name has to be hardcoded, no way since $this references coursemenu on hook
        if ($this->page->blocks->is_block_present("bruincast")) {
            $present = true;
        }

        if (!empty($coursefound) && $present) {
            $coursesrs = substr($coursefound->idnumber, strpos($coursefound->idnumber,'-')+1); // generate the courseid as in bruincast table
           
            if (!empty($coursesrs)) {
                $bcnodes = $DB->get_record('ucla_bruincast', array('srs'=>$coursesrs)); // get entry in bruincast table by the generated courseid
            }

            if (!empty($bcnodes)) {
                // get course record again, uses info from bc table for double checking      
                $course = $DB->get_record('course', array('idnumber'=>($bcnodes->term.'-'.$bcnodes->srs))); 

                if (!empty($course) && !empty($bcnodes->bruincast_url)) {

                    if(strcmp($bcnodes->restricted, "Restricted") == 0){
                        // get contexts for permission checking
                        $context = get_context_instance(CONTEXT_COURSE, $courseid); 
                        $usercontext = get_context_instance(CONTEXT_USER, $courseid);
                    
                        // check if has permission, then generate menu nodes if does
                        if(is_enrolled($context) || has_capability('moodle/site:config', $context)){
                            $nodes = array(navigation_node::create('Bruincast', new moodle_url($bcnodes->bruincast_url)));
                        }

                    } else { // if not restricted, no need for restriction checking, just generate nodes
                        $nodes = array(navigation_node::create('Bruincast', new moodle_url($bcnodes->bruincast_url)));
                    }
                }                    
            }   
        }        
        return $nodes;
    } 

    /** 
      *  Called by moodle
      **/
   public function applicable_formats() {
        return array('course'=> true); // block only available on course pages
   }

    /** 
      *  Called by moodle
      **/
    public function instance_allow_multiple() {
        return false; // disables multiple block instances per page
    }  
    
   /** 
     *  Called by moodle
     **/
    public function instance_allow_config() {
        return false; // disables instance configuration
    }

}
