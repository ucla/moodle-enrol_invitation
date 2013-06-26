<?php

class subject_area_report extends uclastats_base {
    
    private $data = array();
    private $params;
    private $courseids = array();
    
    /**
     * This query will get all the courses for a given term/subjarea.  It will
     * also get the number of students enrolled.
     * 
     * @global type $DB
     * @global type $CFG
     * @return bool true if there are records
     */
    private function query_courses() {
        global $DB, $CFG;
        
        // Query to get courses in a subject area 
        $query = "
            SELECT DISTINCT c.id AS courseid"
            . $this->from_filtered_courses(false) .
            "
            WHERE urc.department = :subjarea
            ORDER BY urci.term, urci.subj_area, urci.crsidx, urci.secidx";
        $records = $DB->get_records_sql($query, $this->params);
        
        foreach($records as $r) {         
            $course_url = null;
            // link to site
            $course_url = html_writer::link(new moodle_url('/course/view.php', 
                    array('id' => $r->courseid)), $r->courseid,
                    array('target' => '_blank'));

            // build course title
            $courseinfos = ucla_get_course_info($r->courseid);
            $titles = array();
            foreach ($courseinfos as $courseinfo) {
                $titles[] = ucla_make_course_title($courseinfo);
            }
            $course_title = implode(' / ', $titles);            
            
            // Course object to be printed
            $course = array(
                'course_id' => $course_url,
                'course_title' => $course_title,
                'course_instructors' => '',
                'course_students' => 'N/A',
                'course_hits' => 'N/A ',
                'course_student_percent' => 'N/A',
                'course_forums' => 0,
                'course_posts' => 0,
                'course_files' => 0,
                'course_size' => 0,
                'course_syllabus' => 'N',
            );
            
            // Key by course ID
            if(!empty($r->courseid)) {
                $this->data[$r->courseid] = (object)$course;
                $this->courseids[] = $r->courseid;
            }
        }

        // True if we found sites from (subjarea,term) on our system
        return !empty($this->courseids);
    }
    
    /**
     * This query will return the instructors for classes in a given 
     * term/subjectarea.  The instructors are filtered via role shortnames.
     * 
     * @global type $DB
     */
    private function query_instructors() {
        global $DB;
        
        // Query to get instructor names for courses in a subject area
        $query = "
            SELECT DISTINCT CONCAT(u.id, '-', c.id), u.id, c.id AS courseid, u.id AS userid,
            CONCAT(u.lastname, ', ', u.firstname, ' (', r.shortname, ')') AS name_role"
            . $this->from_filtered_courses(false) .
                "
                JOIN {context} ctx ON ctx.instanceid = c.id
                JOIN {role_assignments} ra ON ra.contextid = ctx.id
                JOIN {role} AS r ON r.id = ra.roleid
                JOIN {user} AS u ON u.id = ra.userid
            WHERE ctx.contextlevel = :context 
            AND urc.department = :subjarea
            AND r.shortname IN (
                'editinginstructor', 'supervising_instructor'
            )";

        // Get the records
        $records = $DB->get_records_sql($query, $this->params);
        
        foreach($records as $r) {
            // If an array key does not exist, it means the course is no longer
            // listed by the registrar, but still exists on the site
            if(array_key_exists($r->courseid, $this->data)) {
                $this->data[$r->courseid]->course_instructors .=
                        html_writer::tag('div', $r->name_role);
            }
        }
    }
    
    /**
     * Query to get the discussion forums for a given term/subjarea.
     * The forum name, topic count, and post count per topic is retrieved.  
     * The total post count is tabulated in a separate column
     * 
     * @global type $DB
     */
    private function query_forums() {
        global $DB;
        
        // Query to get the discussion topics per forum per course, along with 
        // the number of posts per discussion topic
        $query = "
            SELECT f.id AS fid, c.id AS courseid, fd.id AS fdid, f.name AS forum_name, 
                    count( DISTINCT fd.name ) AS discussion_count, 
                    count( DISTINCT fp.id ) AS posts
                "
                . $this->from_filtered_courses(false) .
                "
                JOIN {context} ctx ON ctx.instanceid = urc.courseid
                JOIN {role_assignments} ra ON ra.contextid = ctx.id
                JOIN {role} AS r ON r.id = ra.roleid
                JOIN {ucla_reg_subjectarea} AS rsa ON rsa.subjarea = urci.subj_area
                JOIN {forum} AS f ON f.course = c.id
                JOIN {forum_discussions} AS fd ON fd.forum = f.id
                JOIN {forum_posts} AS fp ON fp.discussion = fd.id
            WHERE  ctx.contextlevel = :context AND
                   rsa.subj_area_full = :subjarea
            GROUP BY fd.forum
        ";
        
        $records = $DB->get_records_sql($query, $this->params);

        foreach($records as $r) {
            $this->data[$r->courseid]->course_forums += $r->discussion_count;
            $this->data[$r->courseid]->course_posts += $r->posts;
        }
//        foreach($records as $r) {
//            $content = html_writer::tag('strong', $r->forum_name) 
//                    . ', topics (' . $r->discussion_count . '), posts (' . $r->posts . ')';
//            $this->data[$r->courseid]->course_forums .= html_writer::tag('div', $content);
//            $this->data[$r->courseid]->course_posts += $r->posts;
//        }
    }
    
    /**
     * Query to get the number of times students visited a given course.  The 
     * output will show: student (number of times visited)
     * 
     * This is based on how many times the mld_log logs a 'view *' action
     * 
     * @global type $DB
     * @global type $CFG
     */
    private function query_visits($term) {
        global $DB;
        
        $term_info = $this->get_term_info($term);
        
        if(is_summer_term($term)) {
            $term_info['start'] = $term_info['start_8a'];
            $term_info['end'] = $term_info['end_c'];
        }
        
        $term_info = (object)$term_info;
        
        foreach($this->courseids as $id) {
            list($in_or_equal, $params, $count) = $this->get_students_ids($id);
            
            // Check if there are students in course
            if(empty($count)) {
                continue;
            }
            
            $query = "
                SELECT l.id, l.course, l.userid, COUNT( DISTINCT l.time ) AS num_hits
                    FROM {log} l
                WHERE l.action LIKE 'view%'
                    AND l.course = $id
                    AND l.time > $term_info->start
                    AND l.time < $term_info->end
                    AND l.userid $in_or_equal
                GROUP BY l.userid
                ";            
            $records = $DB->get_records_sql($query, $params);
            
            $tally = 0;            
            foreach($records as $r) {
                $tally += $r->num_hits;
            }
            
            $this->data[$id]->course_hits = number_format($tally);
            $this->data[$id]->course_students = number_format($count);
            // Avoid division by 0
            if(!empty($count)){
                $val = (count($records) / $count) * 100;
                $formatted = sprintf("%01.2f", $val);
                $this->data[$id]->course_student_percent = $formatted . '%';
            }
        }
    }
    
    /**
     * Retrieve the student IDs for a given course
     * 
     * @global type $DB
     * @param type $courseid
     * @return string of comma separated IDs
     */
    private function get_students_ids($courseid) {
        global $DB;
        
        $query = "
            SELECT ra.userid
                FROM {course} c
                JOIN {context} ctx ON ctx.instanceid = c.id
                JOIN {role_assignments} ra ON ra.contextid = ctx.id
                JOIN {role} AS r ON r.id = ra.roleid
            WHERE   c.id = :courseid
                AND ctx.contextlevel = :context
                AND r.shortname = 'student'
            ";
        
        $records = $DB->get_records_sql($query, 
                array('courseid' => $courseid, 'context' => CONTEXT_COURSE));
        
        $count = count($records);
        
        // If there are 0 students in course, nothing to return
        if(empty($count)) {
            return array(false, false, false);
        }
        
        list($sql, $params) = $DB->get_in_or_equal(array_map(function($o) {return $o->userid;}, $records));
        
        return array($sql, $params, $count);
    }
    
    /**
     * Querying on the mdl_log can take a long time.
     * 
     * @return boolean
     */
    public function is_high_load() {
        return true;
    }

    /**
     * This will retrieve the file count and course size (based on overall 
     * filesize) for set of courses.  This only counts course resources
     * 
     * @global type $DB
     */
    private function query_files() {
        global $DB;
        
        list($in_or_equal, $params) = $DB->get_in_or_equal($this->courseids);
        
        // Module context
        $context = CONTEXT_MODULE;
        
        $query = "
            SELECT f.id, SUM( f.filesize ) AS filesize, f.mimetype, c.id AS courseid, COUNT( f.id ) AS filecount
                FROM {files} AS f
                JOIN {context} AS ctx ON ctx.id = f.contextid
                JOIN {course_modules} AS cm ON cm.id = ctx.instanceid AND ctx.contextlevel = $context
                JOIN {course} AS c ON c.id = cm.course
            WHERE   c.id $in_or_equal
                AND f.mimetype IS NOT NULL 
                AND f.component LIKE '%resource'
            GROUP BY c.id
            ";
        
        $records = $DB->get_records_sql($query, $params);            

        // Store counts
        foreach($records as $r) {
            // Store size (only as MB, so it is sortable)
            $course_size = round($r->filesize / 1048576 * 10) / 10;
            // make sure it has proper commas for readablity
            $course_size = number_format($course_size);
            $this->data[$r->courseid]->course_size = $course_size;
            $this->data[$r->courseid]->course_files = $r->filecount;
        }
    }
    
    /**
     * Query to get the available syllabi for a set of courses
     * 
     * @global type $DB
     */
    private function query_syllabus() {
        global $DB;
        
        list($in_or_equal, $params) = $DB->get_in_or_equal($this->courseids);
        
        $query = "
            SELECT s.*
                FROM {ucla_syllabus} AS s
                JOIN {course} AS c ON c.id = s.courseid
            WHERE   c.id $in_or_equal 
            ";
        
        $records = $DB->get_records_sql($query, $params);
        
        foreach($records as $r) {
            $this->data[$r->courseid]->course_syllabus = 'Y';
        }
    }
    
    public function query($params) {
        
        // Save params
        $this->params = $params;
        $this->params['context'] = CONTEXT_COURSE;
        $this->courseids = array();

        // Check if we have any sites in given subjarea/term on our system 
        // and if we do, then run all other queries
        if($this->query_courses()) {
            $this->query_instructors();
            $this->query_forums();
            $this->query_visits($params['term']);
            $this->query_files();
            $this->query_syllabus();
        }
  
        return $this->data;
    }
    
    public function get_parameters() {
        return array('term', 'subjarea');
    }
}
