<?php
defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(__FILE__))) . '/local/ucla/lib.php');

class block_ucla_subject_links extends block_base {

    /**
     * Called by moodle
     */
    public function init() {
        // Initialize name and title
        $this->title = get_string('title', 'block_ucla_subject_links');
    }

    /**
     * Called by moodle
     */
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
     */
    public function get_navigation_nodes($course) {
        global $CFG;
        $location = $CFG->dirroot . '/blocks/ucla_subject_links/content/';
        $subjname = subject_exist($course, $location);
        $nodes = array();
        if ($subjname != NULL) {
            $subjname = strtoupper($subjname);
            $url = new moodle_url('/blocks/ucla_subject_links/content/'.$subjname.'.htm');
            $nodes[] = navigation_node::create($subjname.' Links', $url); 
        }
        return $nodes;
    }

    /**
     * Called by moodle
     */
    public function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'not-really-applicable' => true
        );
        // hack to make sure the block can never be instantiated
    }

    /**
     * Called by moodle
     */
    public function instance_allow_multiple() {
        return false; //disables multiple blocks per page
    }

    /**
     * Called by moodle
     */
    public function instance_allow_config() {
        return false; // disables instance configuration
    }
}
if (!function_exists('subject_exist')) {
    function subject_exist($course, $location) {
        $courseinfo = ucla_get_course_info($course->id);
        foreach ($courseinfo as $cinfo) {
            $subject = $cinfo->subj_area;
        }
        $subjname = preg_replace('/-\s/ ', '', $subject); 
        if (file_exists($location.$subjname.'.htm')) {
            return $subjname;
        }
        return NULL; 
    }
}
