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
        $subjname = self::get_subject_areas($course, $location);
        $nodes = array();
        if (!empty($subjname)) {
            foreach ($subjname as $sub) {
                $url = new moodle_url('/blocks/ucla_subject_links/view.php',array('course_id' => $course->id, 'subj_area' => $sub));
                $nodes[] = navigation_node::create(get_string('link_text', 'block_ucla_subject_links', $sub), $url);
            }
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

    public function get_subject_areas($course, $location) {
        $subjname = array();
        $courseinfo = ucla_get_course_info($course->id);
        if (!empty($courseinfo)) {
            foreach ($courseinfo as $cinfo) {
                $subject = $cinfo->subj_area;
                $subject = preg_replace('/-\s/', '', $subject);
                $subject = strtoupper($subject); 
                if (file_exists($location.$subject.'/index.htm')) {
                    $subjname[] = $subject;
                }   
            }
        }
        return $subjname; 
    }
    
    public function subject_exist($course, $location, $subjarea) {
        $subjname = self::get_subject_areas($course, $location);
        return in_array($subjarea, $subjname);
    }
}
