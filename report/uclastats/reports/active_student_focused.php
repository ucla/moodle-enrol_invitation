<?php
/**
 * Report to get the number of active and inactive course sites by division.
 *
 * Criteria for an active course is defined in:
 *
 * Course activity (Student focused)
 *
 * A course is active if it matches one of several visitation criteria.
 * 
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class active_student_focused extends uclastats_base {

    /**
     * For given course, check if 80% of the students has any log entries for
     * the course.
     *
     * @param object $course
     * @param int $enrolledstudents Array of students enrolled in course.
     * @param int $start            Start of term, UNIX timestamp.
     * @param int $end              End of term, UNIX timestamp.
     *
     * @return boolean
     */
    private function check_majority_viewed($course, $enrolledstudents, $start, $end) {
        global $DB;

        $sql = "SELECT  DISTINCT ra.userid
                FROM    {course} c
                JOIN    {log} l ON (l.course=c.id)
                JOIN    {context} ct ON (
                            ct.instanceid=c.id AND
                            ct.contextlevel=:contextlevel
                        )
                JOIN    {role_assignments} ra ON (ct.id=ra.contextid)
                JOIN    {role} r ON (ra.roleid=r.id)
                WHERE   c.id=:courseid AND
                        l.time>:starttime AND
                        l.time<:endtime AND
                        ra.userid=l.userid AND
                        r.shortname='student' AND
                        l.course=c.id";
        $students = $DB->get_records_sql($sql,
                array('contextlevel' => CONTEXT_COURSE, 'courseid' => $course->id,
                    'starttime' => $start, 'endtime' => $end));

        // Make sure that each student returned is currently enrolled in the
        // course.
        foreach ($students as $index => $student) {
            if (!in_array($student->userid, $enrolledstudents)) {
                unset($students[$index]);
            }
        }

        if ((count($students)/count($enrolledstudents)) >= 0.80) {
            return true;
        }

        return false;
    }

    /**
     * Returns the number of users enrolled as a student for given course.
     *
     * @param object $course
     *
     * @return array            Array of enrolled userids.
     */
    private function get_enrolled_students($course) {
        global $DB;
        $retval = array();

        $sql = "SELECT  DISTINCT ra.userid AS userid
                FROM    {course} c
                JOIN    {context} ct ON (
                            ct.instanceid=c.id AND
                            ct.contextlevel=:contextlevel
                        )
                JOIN    {role_assignments} ra ON (ct.id=ra.contextid)
                JOIN    {role} r ON (ra.roleid=r.id)
                WHERE   r.shortname='student' AND
                        c.id=:courseid";
        $enrolled =  $DB->get_records_sql($sql, array('contextlevel' => CONTEXT_COURSE,
                'courseid' => $course->id));

        // Return an array of userids.
        foreach ($enrolled as $user) {
            $retval[] = $user->userid;
        }

        return $retval;
    }

//    /**
//     * Get all student log entries for all courses for given term.
//     *
//     * @param object $course
//     * @param int $enrolledstudents Array of students enrolled in course.
//     * @param int $start            Start of term, UNIX timestamp.
//     * @param int $end              End of term, UNIX timestamp.
//     *
//     * @return array                Returns an array indexed by courseid with
//     *                              number of student log entries.
//     */
//    private function get_student_course_logs($term, $start, $end) {
//        global $DB;
//
//        $sql = "SELECT  c.id,
//                        COUNT(DISTINCT l.id) AS count " .
//                $this->from_filtered_courses(true) . "
//                JOIN    {log} l ON (l.course=c.id)
//                JOIN    {context} ct ON (
//                            ct.instanceid=c.id AND
//                            ct.contextlevel=50
//                        )
//                JOIN    {role_assignments} ra ON (ct.id=ra.contextid)
//                JOIN    {role} r ON (ra.roleid=r.id)
//                WHERE   l.time>:start AND
//                        l.time<:end AND
//                        ra.userid=l.userid AND
//                        r.shortname='student' AND
//                        l.course=c.id
//                GROUP BY    c.id";
//        $DB->set_debug(true);
//        $logs = $DB->get_records_sql($sql, array('term' => $term,
//            'start' => $start, 'end' => $end));
//        $DB->set_debug(false);
//
//        return $logs;
//    }

    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term');
    }

    /**
     * Since this query joins on the log table, it will take a long time.
     * 
     * @return boolean
     */
    public function is_high_load() {
        return true;
    }

    /**
     * Query get the number of active/inactive course sites by division.
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        $retval = array();

        // Find start/end dates for term.
        $terminfo = $this->get_term_info($params['term']);
        if(is_summer_term($params['term'])) {
            $terminfo['start'] = $terminfo['start_8a'];
            $terminfo['end'] = $terminfo['end_c'];
        }

        // Get list of courseids for a given term by division.
        $sql = "SELECT  c.*,
                        urd.fullname AS division " .
                $this->from_filtered_courses(true) . "
                JOIN    {ucla_reg_division} urd ON (
                        urci.division=urd.code
                        )
                WHERE   1";
        $rs = $DB->get_recordset_sql($sql, $params);

        if ($rs->valid()) {
//            $studentlogs = $this->get_student_course_logs($params['term'],
//                    $terminfo['start'], $terminfo['end']);
            
            foreach ($rs as $course) {
                $division = ucla_format_name($course->division, true);
                unset($course->division);

                // Initialize array for a given division.
                if (!isset($retval[$division])) {
                    // We want the result columns to display in a certain order.
                    $retval[$division] = array('division' => $division,
                        'numactive' => 0, 'numinactive' => 0, 'totalcourses' => 0);
                }

                $isactive = false;
                $enrolledstudents = $this->get_enrolled_students($course);

                // If there are no students, then can skip everything.
                if (!empty($enrolledstudents)) {
                    if ($this->check_majority_viewed($course, $enrolledstudents,
                            $terminfo['start'], $terminfo['end'])) {
                         $isactive = true;
                    }
//                    if (isset($studentlogs[$course->id]) &&
//                            ($studentlogs[$course->id]->count/$enrolledstudents) >= 0.80) {
//                        $isactive = true;
//                    }
                }

                // Update totals for divsion.
                if ($isactive) {
                    // Course is active if it is above a certain threshold.
                    ++$retval[$division]['numactive'];
                } else {
                    ++$retval[$division]['numinactive'];
                }
                ++$retval[$division]['totalcourses'];
            }        
        
            // Order result by division.
            ksort($retval);
        }
        
        return $retval;
    }
}
