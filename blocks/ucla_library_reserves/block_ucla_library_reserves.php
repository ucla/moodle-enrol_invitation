<?php

defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(__FILE__))) . '/local/ucla/lib.php');

class block_ucla_library_reserves extends block_base {

    /**
      * Called by moodle
      **/
    public function init() {

        // Initialize name and title
        $this->title = get_string('title','block_ucla_library_reserves');

    }

    /**
      * Called by moodle
      **/
    public function get_content() {
        global $COURSE;


        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;

        return $this->content;
    }

    /**
      * Use UCLA Course menu block hook
      **/
    public function get_navigation_nodes($course) {

        // get global variables
        global $DB,$COURSE;
        
        $courseid = $COURSE->id; // course id from the hook function
        $coursefound = ucla_map_courseid_to_termsrses($courseid); 
       
        $nodes = array();
        $lrnodes = array();

        if (!empty($coursefound)) {

            foreach($coursefound as $courseentry) {
           
                $coursesrs = $courseentry->srs; // generate the courseid as in libraryreserves table
                $courseterm = $courseentry->term;

                if (!empty($coursesrs) && !empty($courseterm)) {
                    $lrnodes = $DB->get_records('ucla_library_reserves', array('quarter'=>$courseterm,'srs'=>$coursesrs)); // get entry in libraryreserves table by the generated courseid

                    $reginfo = ucla_get_reg_classinfo($courseterm, $coursesrs);

                    // If srs did not work as lookup, use the term, courseid, and department code 
                    if($lrnodes == false) {

                        if(!empty($reginfo)) {
                            $lrnodes = $DB->get_records('ucla_library_reserves', array('quarter'=>$courseterm, 'department_code'=>$reginfo->subj_area, 'course_number'=>$reginfo->coursenum));
                        }
                    }
                }


                if($lrnodes != false) {

                    foreach($lrnodes as $lrnode) {

                        $reginfo = ucla_get_reg_classinfo($lrnode->quarter,$lrnode->srs);

                        if (!empty($lrnode->url)) {

                            // Must hardcode the naming string since this is a static function
                            $nodes[] = navigation_node::create('Library Reserves '.$reginfo->subj_area.$reginfo->coursenum, new moodle_url($lrnode->url)); 
                            break;
                        }
                    }
                }
            }
        }

        return $nodes;

    }

    /**
      * Called by moodle
      **/
    public function applicable_formats() {
        
        return array(
            'site-index' => false,
            'course-view'=> false,
            'my' => false,
            'block-ucla_library_reserves' => false,
            'not-really-applicable' => true
        ); 
        // hack to make sure the block can never be instantiated
        
    }

    /**
      * Called by moodle
      **/
    public function instance_allow_multiple() {
        return false; //disables multiple blocks per page
    }

    /**
      * Called by moodle
      **/
    public function instance_allow_config() {
        return false; // disables instance configuration
    }
}
