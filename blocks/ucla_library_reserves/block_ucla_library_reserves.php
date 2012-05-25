<?php
defined('MOODLE_INTERNAL') || die();

require_once(dirname(dirname(dirname(__FILE__))) . '/local/ucla/lib.php');

class block_ucla_library_reserves extends block_base {

    /**
     * Called by moodle
     */
    public function init() {
        // Initialize name and title
        $this->title = get_string('title', 'block_ucla_library_reserves');
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
        // get global variables
        global $DB, $COURSE;

        $nodes = array();
        $links = array();        
        
        $courseid = $COURSE->id; // course id from the hook function
        $courseinfo = ucla_get_course_info($courseid);
        
        if (empty($courseinfo)) {
            return $nodes;
        }
                
        foreach ($courseinfo as $index => $courseentry) {
            // see if term/srs is found in library_reserves table
            $lrnodes = $DB->get_records('ucla_library_reserves',
                    array('quarter' => $courseentry->term, 'srs' => $courseentry->srs)); 

            // If srs did not work as lookup, use the term, courseid, and 
            // department code, but make sure that the term/srs for that record
            // is the same section for the course entry (see CCLE-2938)
            if (empty($lrnodes)) {
                $sql = "SELECT  *
                        FROM    {ucla_library_reserves} AS lr, 
                                {ucla_reg_classinfo} AS lr_rci,
                                {ucla_reg_classinfo} AS ce_rci
                        WHERE   lr.quarter=:quarter AND
                                lr.department_code=:department_code AND
                                lr.course_number=:course_number AND
                                lr.quarter=lr_rci.term AND
                                lr.srs=lr_rci.srs AND
                                ce_rci.term=:courseentry_term AND
                                ce_rci.srs=:courseentry_srs AND
                                lr_rci.sectnum=ce_rci.sectnum";                               
                $lrnodes = $DB->get_records_sql($sql,
                        array('quarter' => $courseentry->term, 
                              'department_code' => $courseentry->subj_area, 
                              'course_number' => $courseentry->coursenum,
                              'courseentry_term' => $courseentry->term,
                              'courseentry_srs' => $courseentry->srs));     
            }
            
            if (empty($lrnodes)) {                
                continue;  // no record found for courseentry
            }                
            
            /* since we found some library reserve entries, create link array
             * in the format [reserve link] => ['subj_area'] 
             *                                 ['coursenum']
             */  
            foreach ($lrnodes as $lrnode) {
                $links[$lrnode->url]['subj_area'] = $courseentry->subj_area;
                $links[$lrnode->url]['coursenum'] = $courseentry->coursenum;
            }
        }

        // if only one unique url was found, then just give the name "Library reserves"
        $lr_string = get_string('title', 'block_ucla_library_reserves');        
        if (count($links) == 1) {
            $nodes[] = navigation_node::create($lr_string,
                            new moodle_url(array_pop(array_keys($links))));            
        } else {
            // else display link with subj_area and coursenum appended
            foreach ($links as $url => $entry) {
                $nodes[] = navigation_node::create(sprintf('%s %s %s', 
                        $lr_string, $entry['subj_area'], $entry['coursenum']), 
                        new moodle_url($url));                 
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
            'block-ucla_library_reserves' => false,
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
