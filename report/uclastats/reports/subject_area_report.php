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
        
        // Query to get courses in a subject area and the number of students 
        // enrolled in the courses
         $query = "
            SELECT bci.id, bci.coursetitleshort, bci.coursetitlelong, bci.activitytype, c.id as courseid
                FROM {ucla_browseall_classinfo} AS bci
                JOIN {ucla_reg_subjectarea} AS rsa ON rsa.subjarea = bci.subjarea
                LEFT JOIN {ucla_request_classes} AS rc ON rc.term = bci.term AND rc.srs = bci.srs
                LEFT JOIN {course} AS c ON c.id = rc.courseid
            WHERE   bci.term = :term
                AND rsa.subj_area_full = :subjarea
            ";

        $records = $DB->get_records_sql($query, $this->params);

        foreach($records as $r) {
            
            // Course object to be printed
            $course = array(
                'course_id' => empty($r->courseid) ? '' : html_writer::link($CFG->wwwroot . '/course/view.php?id=' . $r->courseid, $r->courseid),
                'course_shortname' => $r->coursetitleshort,
                'course_fullname' => $r->coursetitlelong,
                'course_instructors' => '',
                'course_students' => 'N/A',
                'course_hits' => 'N/A ',
                'course_student_percent' => 'N/A',
                'course_forums' => '',
                'course_posts' => 0,
                'course_files' => 0,
                'course_size' => '0 KB',
                'course_syllabus' => 'N',
            );
            
            // Key by course ID
            if(empty($r->courseid)) {
                $course['course_size'] = 'N/A';
                $this->data['null' . $r->id] = (object)$course;
            } else {
                $this->data[$r->courseid] = (object)$course;
                $this->courseids[] = $r->courseid;
            }
        }

        return !empty($records);
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
            SELECT c.id AS courseid , u.id AS userid, u.firstname, u.lastname, r.shortname AS role
                FROM {course} c
                JOIN {context} ctx ON ctx.instanceid = c.id
                JOIN {role_assignments} ra ON ra.contextid = ctx.id
                JOIN {role} AS r ON r.id = ra.roleid
                JOIN {ucla_request_classes} AS rc ON rc.courseid = c.id
                JOIN {ucla_reg_classinfo} AS rci ON rci.term = rc.term AND rci.srs = rc.srs
                JOIN {ucla_reg_subjectarea} AS rsa ON rsa.subjarea = rci.subj_area
                JOIN {user} AS u ON u.id = ra.userid
            WHERE   rc.term = :term
                AND ctx.contextlevel = :context
                AND rsa.subj_area_full = :subjarea
                AND r.shortname IN (
                    'editinginstructor', 'supervising_instructor'
                )
            GROUP BY u.id
            ";

        // Get the records
        $records = $DB->get_records_sql($query, $this->params);
        
        foreach($records as $r) {
            $content = $r->lastname . ', ' . $r->firstname . ' (' . $r->role .  ')';
            $this->data[$r->courseid]->course_instructors .= html_writer::tag('div', $content);
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
                FROM {course} c
                JOIN {context} ctx ON ctx.instanceid = c.id
                JOIN {role_assignments} ra ON ra.contextid = ctx.id
                JOIN {role} AS r ON r.id = ra.roleid
                JOIN {ucla_request_classes} AS rc ON rc.courseid = c.id
                JOIN {ucla_reg_classinfo} AS rci ON rci.term = rc.term AND rci.srs = rc.srs                
                JOIN {ucla_reg_subjectarea} AS rsa ON rsa.subjarea = rci.subj_area
                JOIN {forum} AS f ON f.course = c.id
                JOIN {forum_discussions} AS fd ON fd.forum = f.id
                JOIN {forum_posts} AS fp ON fp.discussion = fd.id
            WHERE   rc.term = :term
                AND ctx.contextlevel = :context
                AND rsa.subj_area_full = :subjarea
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
    private function query_visits() {
        global $DB, $CFG;
        
        $term = $this->get_term_info();
        
        foreach($this->courseids as $id) {
            list($in_or_equal, $params, $count) = $this->get_students_ids($id);
            
            $query = "
                SELECT l.id, l.course, l.userid, COUNT( DISTINCT l.time ) AS num_hits
                    FROM {log} l
                WHERE l.action LIKE 'view%'
                    AND l.course = $id
                    AND l.time > $term->start
                    AND l.time < $term->end
                    AND l.userid $in_or_equal
                GROUP BY l.userid
                ";
            
            $records = $DB->get_records_sql($query, $params);
            
            $tally = 0;
            
            foreach($records as $r) {
                $tally += $r->num_hits;
            }
            
            $this->data[$id]->course_hits = $tally;
            $this->data[$id]->course_students = $count;
            // Avoid division by 0
            if(!empty($count)){
                $this->data[$id]->course_student_percent = (count($records) / $count) . ' %';
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
        
        list($sql, $params) = $DB->get_in_or_equal(array_map(function($o) {return $o->userid;}, $records));
        
        return array($sql, $params, $count);
    }
    
    private function get_term_info() {
        // We need to query the registrar
        ucla_require_registrar();
        
        $result = registrar_query::run_registrar_query('ucla_getterms', 
                    array($this->params['term']), true);
        
        $term = array();
        
        // Get ther term start and term end, if it's a summer session,
        // then get start and end of entire summer
        foreach($result as $r) {
            if($r['session'] == 'RG') {
                $term['start'] = strtotime($r['session_start']);
                $term['end'] = strtotime($r['session_end']);
                break;
            } else if($r['session'] == '8A') {
                $term['start'] = strtotime($r['session_start']);
            } else if($r['session'] == '6C') {
                $term['end'] = strtotime($r['session_end']);
            }
        }
        
        return (object)$term;
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
            $this->data[$r->courseid]->course_size = display_size($r->filesize);
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

        // Check if we have any classes in given subjarea/term
        // and if we do, then run all other queries
        if($this->query_courses()) {
            $this->query_instructors();
            $this->query_forums();
            $this->query_visits();
            $this->query_files();
            $this->query_syllabus();
        }
  
        return $this->data;
    }
    
    public function get_parameters() {
        return array('term', 'subjarea');
    }
}
