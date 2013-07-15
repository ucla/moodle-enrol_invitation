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
     * For given course, check if 80% of the students has viewed the course.
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
                            ct.contextlevel=50
                        )
                JOIN    {role_assignments} ra ON (ct.id=ra.contextid)
                JOIN    {role} r ON (ra.roleid=r.id)
                WHERE   c.id=? AND
                        l.time>? AND
                        l.time<? AND
                        ra.userid=l.userid AND
                        r.shortname='student' AND
                        l.course=c.id AND
                        c.visible=1";
        $students = $DB->get_records_sql($sql, array($course->id, $start, $end));

        if ((count($students)/count($enrolledstudents)) >= 0.80) {
            return true;
        }

        return false;
    }

    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term');
    }

    /**
     * Returns an array of userids enrolled as a student for given course.
     * 
     * @param object $course
     *
     * @return int
     */
    private function get_enrolled_students($course) {
        global $DB;

        $sql = "SELECT  DISTINCT ra.userid
                FROM    {course} c
                JOIN    {context} ct ON (
                            ct.instanceid=c.id AND
                            ct.contextlevel=50
                        )
                JOIN    {role_assignments} ra ON (ct.id=ra.contextid)
                JOIN    {role} r ON (ra.roleid=r.id)
                WHERE   r.shortname='student' AND
                        c.id=:courseid";
        return $DB->get_records_sql($sql, array('courseid' => $course->id));
    }

    /**
     * Since this query joins on the log table, it will take a long time.
     * 
     * @return boolean
     */
    public function is_high_load() {
        return TRUE;
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

            foreach ($rs as $course) {
                $points = 0;
                $division = $course->division;
                unset($course->division);

                // Initialize array for a given division.
                if (!isset($retval[$division])) {
                    // We want the result columns to display in a certain order.
                    $retval[$division] = array('division' => $division,
                        'numactive' => 0, 'numinactive' => 0, 'totalcourses' => 0);
                }

                $enrolledstudents = $this->get_enrolled_students($course);

                $isactive = FALSE;

                // If there are no students, then can skip everything.
                if (!empty($enrolledstudents)) {
                    if ($this->check_majority_viewed($course, $enrolledstudents,
                            $terminfo['start'], $terminfo['end'])) {
                         $isactive = TRUE;
                    }
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
