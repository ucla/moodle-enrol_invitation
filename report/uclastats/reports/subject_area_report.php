<?php

class subject_area_report extends uclastats_base {
    
    private $data = array();
    private $params;
    
    private function query_courses() {
        global $DB, $CFG;
        
        // Query to get courses in a subject area and the number of students 
        // enrolled in the courses
         $query = "
            SELECT c.id, c.shortname, c.fullname, COUNT( DISTINCT ra.userid ) AS num_students
                FROM {course} c
                JOIN {context} ctx ON ctx.instanceid = c.id
                JOIN {role_assignments} ra ON ra.contextid = ctx.id
                JOIN {role} AS r ON r.id = ra.roleid
                JOIN {ucla_reg_classinfo} AS rci ON c.shortname = concat( :term, '-', rci.subj_area, rci.coursenum, '-', rci.sectnum )
                JOIN {ucla_reg_subjectarea} AS rsa ON rsa.subjarea = rci.subj_area
            WHERE   ctx.contextlevel = 50
                AND r.shortname = 'student'
                AND rsa.subj_area_full = :subjarea
            GROUP BY c.id            
            ";

        $records = $DB->get_records_sql($query, $this->params);

        foreach($records as $r) {
            
            // Course object to be printed
            $course = array(
                'course_id' => html_writer::link($CFG->wwwroot . '/course/view.php?id=' . $r->id, $r->id),
                'course_shortname' => $r->shortname,
                'course_fullname' => $r->fullname,
                'course_instructors' => '',
                'course_students' => $r->num_students,
                'course_hits' => '',
                'course_forums' => '',
            );
            
            // Key by course ID
            $this->data[$r->id] = (object)$course;
        }

        return !empty($records);
    }
    
    private function query_instructors() {
        global $DB;
        
        // Query to get instructor names for courses in a subject area
        $query = "
            SELECT c.id AS id , u.id AS userid, u.firstname, u.lastname, r.shortname AS role
                FROM {course} c
                JOIN {context} ctx ON ctx.instanceid = c.id
                JOIN {role_assignments} ra ON ra.contextid = ctx.id
                JOIN {role} AS r ON r.id = ra.roleid
                JOIN {ucla_reg_classinfo} AS rci ON c.shortname = concat( :term, '-', rci.subj_area, rci.coursenum, '-', rci.sectnum )
                JOIN {ucla_reg_subjectarea} AS rsa ON rsa.subjarea = rci.subj_area
                JOIN {user} AS u ON u.id = ra.userid
            WHERE   ctx.contextlevel = 50
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
            $this->data[$r->id]->course_instructors .= html_writer::tag('div', $content);
        }
        
    }
    
    private function query_forums() {
        global $DB;
        
        // Query to get the discussion topics per forum per course
        $query = "
            SELECT f.id as fid, c.id as id, fd.id AS fdid, f.name AS forum_name, count( DISTINCT fd.name ) AS discussion_count
                FROM {course} c
                JOIN {context} ctx ON ctx.instanceid = c.id
                JOIN {role_assignments} ra ON ra.contextid = ctx.id
                JOIN {role} AS r ON r.id = ra.roleid
                JOIN {ucla_reg_classinfo} AS rci ON c.shortname = concat( :term, '-', rci.subj_area, rci.coursenum, '-', rci.sectnum )
                JOIN {ucla_reg_subjectarea} AS rsa ON rsa.subjarea = rci.subj_area
                JOIN {forum_discussions} AS fd ON fd.course = c.id
                JOIN {forum} AS f ON f.id = fd.forum
            WHERE   ctx.contextlevel = 50
                AND rsa.subj_area_full = :subjarea
            GROUP BY fd.forum
        ";
        
        $records = $DB->get_records_sql($query, $this->params);

        foreach($records as $r) {
            $content = $r->forum_name . ' (' . $r->discussion_count . ')';
            $this->data[$r->id]->course_forums .= html_writer::tag('div', $content);
        }
    }
    
    private function query_visits() {
        global $DB, $CFG;
        
        $courses = array_keys($this->data);
        $term = $this->get_term_info();
        
        foreach($courses as $id) {
            $students = $this->get_students_ids($id);
            
            $query = "
                SELECT l.id, l.course, l.userid, COUNT( DISTINCT l.time ) AS num_hits
                    FROM {log} l
                WHERE l.action LIKE 'view%'
                    AND l.course = $id
                    AND l.time > $term->start
                    AND l.time < $term->end
                    AND l.userid IN( $students )
                GROUP BY l.userid
                ";
            
            $records = $DB->get_records_sql($query);
            
            $content = '';
            $tally = 0;
            
            foreach($records as $r) {
                $tally += $r->num_hits;
                
                $l = html_writer::link($CFG->wwwroot . '/user/view.php?id=' . $r->userid, $r->userid);
                $c = $l . ' (' . $r->num_hits . ')';
                $content .= html_writer::tag('div', $c);
            }
            
            $tc = html_writer::tag('div', 'Total visits: ' . $tally);
            $this->data[$id]->course_hits = $tc . $content;
        }
    }
    
    private function get_students_ids($courseid) {
        global $DB;
        
        $query = "
            SELECT ra.userid
                FROM {course} c
                JOIN {context} ctx ON ctx.instanceid = c.id
                JOIN {role_assignments} ra ON ra.contextid = ctx.id
                JOIN {role} AS r ON r.id = ra.roleid
            WHERE   c.id = $courseid
                AND ctx.contextlevel =50
                AND r.shortname = 'student'
            ";
        
        $records = $DB->get_records_sql($query);
        
        return implode(',', array_map(function($o) {return $o->userid;}, $records));
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
            } else if($r['session'] == '8A') {
                $term['start'] = strtotime($r['session_start']);
            } else if($r['session'] == '6C') {
                $term['end'] = strtotime($r['session_end']);
            }
        }
        
        return (object)$term;
    }
    
    public function query($params) {
        
        // Save params
        $this->params = $params;

        // Check if we have any classes in given subjarea/term
        // and if we do, then run all other queries
        if($this->query_courses()) {
            $this->query_instructors();
            $this->query_forums();
            $this->query_visits();
        }
  
        return $this->data;
    }
    
    public function get_parameters() {
        return array('term', 'subjarea');
    }
}
