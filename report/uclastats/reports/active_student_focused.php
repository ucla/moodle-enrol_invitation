<?php
/**
 * Report to get the number of active and inactive course sites by division.
 *
 * Criteria for an active course is defined in:
 *
 * Course activity (Student focused)
 *
 * A course is active if it has at least 80% of its enrolled students viewed a
 * course module or the syllabus at least once during the term.
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
     * Stored cached copy of the roleid for student.
     * 
     * @var int
     */
    private $studentroleid = null;

    /**
     * For given course, check if 80% of the students has any log entries for
     * any course modules or syllabus views for the course.
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

        $params = array('contextlevel' => CONTEXT_COURSE,
                'courseid' => $course->id, 'starttime' => $start, 'endtime' => $end);

        // Filter out log activity from unused forums.
        $forums = $this->get_empty_default_forums($course);
        if (!empty($forums)) {
            list($notinsql, $inparams) = $DB->get_in_or_equal($forums,
                    SQL_PARAMS_NAMED, false);
            $params = array_merge($params, $inparams);
        }

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
                        l.course=c.id AND
                        l.module!='course' AND
                        l.module!='user'";

        // If we need to filter out unused forums, then add SQL.
        if (!empty($insql)) {
            $sql .= " l.cmid $notinsql";
        }

        $students = $DB->get_records_sql($sql, $params);

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
     * Display number of inactive courses.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {
        if (isset($results['courselisting'])) {
            return count($results['courselisting']);
        }
        return '';
    }

    /**
     * Returns the course module ids for the default Announcements and
     * Discussions forums that do not have any posts.
     *
     * @param object $course
     *
     * return int
     */
    private function get_empty_default_forums($course) {
        global $DB;

        $sql = "SELECT  cm.id
                FROM    {forum} f
                JOIN    {modules} m ON (m.name='forum')
                JOIN    {course_modules} cm ON (cm.instance=f.id AND
                            cm.course=f.course AND cm.module=m.id)
                LEFT JOIN    {forum_discussions} fd ON (fd.forum=f.id)
                WHERE   f.course=:courseid AND
                        ((f.type='news' AND f.name='Announcements') OR
                        (f.type='general' AND f.name='Discussion forum')) AND
                        fd.id IS NULL";
        $results = $DB->get_fieldset_sql($sql, array('courseid' => $course->id));

        return $results;
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

        if (empty($this->studentroleid)) {
            $this->studentroleid = $DB->get_field('role', 'id',
                    array('shortname' => 'student'));
        }

        $enrolled = get_role_users($this->studentroleid,
                context_course::instance($course->id), false, 'u.id', null, false);

        // Return an array of userids.
        foreach ($enrolled as $user) {
            $retval[] = $user->id;
        }

        return $retval;
    }

    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term');
    }

    /**
     * Display two results tables. One, for the inactive courses by division,
     * and, two, a list of the inactive courses for spot checking.
     *
     * @param uclastats_result $uclastats_result
     * @return string
     */
    protected function get_results_table(uclastats_result $uclastats_result) {
        $retval = '';

        $results = $uclastats_result->results;
        $courselisting = $results['courselisting'];
        unset($results['courselisting']);

        // Aggregated results.
        $resultstable = new html_table();
        $resultstable->id = 'uclastats-results-table';
        $resultstable->attributes = array('class' => 'results-table ' .
            get_class($this));

        $resultstable->head = $uclastats_result->get_header();
        $resultstable->data = $results;

        $retval = html_writer::table($resultstable);

        $retval .= html_writer::tag('h3', get_string('inactivecourselisting', 'report_uclastats'));

        // Course listing.
        $listingtable = new html_table();
        $listingtable->id = 'uclastats-courselisting-table';
        $listingtable->attributes = array('class' => 'results-table ' .
            get_class($this));

        $listingtable->head = array(get_string('division', 'report_uclastats'),
                get_string('course_shortname', 'report_uclastats'));
        foreach ($courselisting as $courseid => $course) {
            $courselisting[$courseid]['shortname'] = html_writer::link(
                    new moodle_url('/course/view.php',
                            array('id' => $courseid)), $course['shortname'],
                    array('target' => '_blank'));
        }
        $listingtable->data = $courselisting;

        $retval .= html_writer::table($listingtable);

        return $retval;
    }

    /**
     * Write out the aggregated results and the list of inactive courses.
     *
     * @param MoodleExcelWorksheet $worksheet
     * @param MoodleExcelFormat $boldformat
     * @param uclastats_result $uclastats_result
     * @param int $row      Row to start writing.
     *
     * @return int          Return row we stopped writing.
     */
    protected function get_results_xls(MoodleExcelWorksheet $worksheet,
            MoodleExcelFormat $boldformat, uclastats_result $uclastats_result, $row) {

        $results = $uclastats_result->results;
        $courselisting = $results['courselisting'];
        unset($results['courselisting']);

        // Display aggregated results.
        $col = 0;
        $header = $uclastats_result->get_header();
        foreach ($header as $name) {
            $worksheet->write_string($row, $col, $name, $boldformat);
            ++$col;
        }

        // now go through result set
        foreach ($results as $result) {
            ++$row; $col = 0;
            foreach ($result as $value) {
                // values might have HTML in them
                $value = clean_param($value, PARAM_NOTAGS);
                if (is_numeric($value)) {
                    $worksheet->write_number($row, $col, $value);
                } else {
                    $worksheet->write_string($row, $col, $value);
                }
                ++$col;
            }
        }

        $row += 2; $col = 0;
        $worksheet->write_string($row, $col,
                get_string('inactivecourselisting', 'report_uclastats'), $boldformat);
        $row++;

        // Display course listings table header
        $header = array(get_string('division', 'report_uclastats'),
                get_string('course_shortname', 'report_uclastats'));
        foreach ($header as $name) {
            $worksheet->write_string($row, $col, $name, $boldformat);
            ++$col;
        }

        // Now go through courselisting set.
        foreach ($courselisting as $course) {
            ++$row; $col = 0;
            foreach ($course as $value) {
                // values might have HTML in them
                $value = clean_param($value, PARAM_NOTAGS);
                if (is_numeric($value)) {
                    $worksheet->write_number($row, $col, $value);
                } else {
                    $worksheet->write_string($row, $col, $value);
                }
                ++$col;
            }
        }

        return $row;
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
        $courselisting = array();

        // Find start/end dates for term.
        $terminfo = $this->get_term_info($params['term']);

        // Get list of courseids for a given term by division.
        $sql = "SELECT  c.*,
                        urd.fullname AS division " .
                $this->from_filtered_courses(true) . "
                JOIN    {ucla_reg_division} urd ON (
                        urci.division=urd.code
                        )
                WHERE   1
                ORDER BY urd.fullname, urci.subj_area";
        $rs = $DB->get_recordset_sql($sql, $params);

        if ($rs->valid()) {
            
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
                }

                // Update totals for divsion.
                if ($isactive) {
                    // Course is active if it is above a certain threshold.
                    ++$retval[$division]['numactive'];
                } else {
                    $courselisting[$course->id] = array('division' => $division,
                        'shortname' => $course->shortname);
                    ++$retval[$division]['numinactive'];
                }
                ++$retval[$division]['totalcourses'];
            }        
        
            // Order result by division.
            ksort($retval);
            $retval['courselisting'] = $courselisting;
        }
        
        return $retval;
    }
}
