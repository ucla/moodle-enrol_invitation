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
        $subjname = self::subject_exist($course, $location);
        $nodes = array();
        if ($subjname != NULL) {
            foreach ($subjname as $sub) {
                $sub = strtoupper($sub);
                $url = new moodle_url('/blocks/ucla_subject_links/view.php/',array('course_id' => $course->id));
                $nodes[] = navigation_node::create($sub.' Links', $url);
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

    public function subject_exist($course, $location) {
        $subjname = array();
        $courseinfo = ucla_get_course_info($course->id);
        foreach ($courseinfo as $cinfo) {
            $subject = $cinfo->subj_area;
            $subject = preg_replace('/-\s/', '', $subject); 
            if (file_exists($location.$subject.'/index.htm')) {
                $subjname[] = $subject;
            }
        }
        if (!empty($subjname)) {
            return $subjname;
        }
        return NULL; 
    }
}
