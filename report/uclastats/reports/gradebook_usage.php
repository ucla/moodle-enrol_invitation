<?php
/**
 * Report to get the number of courses using the Gradebook.
 *
 * @package    report_uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class gradebook_usage extends uclastats_base {
    /**
     * Instead of counting results, but return actual count.
     *
     * @param array $results
     * @return int
     */
    public function format_cached_results($results) {
        return $results['SYSTEM']['usedgradebook'];
    }

    /**
     * Returns list of courses for a given term.
     *
     * @param string $term
     *
     * @return array
     */
    private function get_courses($term) {
        global $DB;

        $sql = "SELECT  DISTINCT c.id,
                        rd.code,
                        rd.fullname" .
                $this->from_filtered_courses(true)
                ."
                JOIN    {ucla_reg_division} rd ON rd.code=urci.division
                WHERE   1
                ORDER BY    rd.fullname";
        $results = $DB->get_records_sql($sql, array('term' => $term));

        return $results;
    }
    /**
     * Returns list of courses for a given term that have exported grades.
     *
     * @param string $term
     *
     * @return array
     */
    private function get_exported_grades($term) {
        global $DB;

        $sql = "SELECT  DISTINCT c.id,
                        urci.division " .
                $this->from_filtered_courses(true)
                ."
                JOIN    {log} l ON l.course=c.id
                WHERE   l.module='grade' AND
                        l.action LIKE 'export%'";
        $results = $DB->get_records_sql($sql, array('term' => $term));

        return $results;
    }

    /**
     * Returns list of courses for a given term that have graded grade items.
     *
     * @param string $term
     *
     * @return array
     */
    private function get_graded_items($term) {
        global $DB;

        $sql = "SELECT  DISTINCT c.id,
                        urci.division " .
                $this->from_filtered_courses(true)
                ."
                JOIN    {grade_items} gi ON gi.courseid=c.id
                JOIN    {grade_grades} gg ON gi.id=gg.itemid
                WHERE   gg.rawgrade IS NOT NULL";
        $results = $DB->get_records_sql($sql, array('term' => $term));

        return $results;
    }

    /**
     * Returns list of courses for a given term that have overridden grades or
     * grade items.
     *
     * @param string $term
     *
     * @return array
     */
    private function get_overridden_grades($term) {
        global $DB;

        $sql = "SELECT  DISTINCT c.id,
                        urci.division " .
                $this->from_filtered_courses(true)
                ."
                JOIN    {grade_items} gi ON gi.courseid=c.id
                JOIN    {grade_grades} gg ON gi.id=gg.itemid
                WHERE   gg.overridden!=0";
        $results = $DB->get_records_sql($sql, array('term' => $term));

        return $results;
    }

    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term');
    }

    /**
     * Query to get the courses using the Moodle gradebook for a given term
     * broken down by division.
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        $results = array();

        // Make sure that term parameter exists.
        if (!isset($params['term']) ||
                !ucla_validator('term', $params['term'])) {
            throw new moodle_exception('invalidterm', 'report_uclastats');
        }

        // Get list of all courses.
        $allcourses = $this->get_courses($params['term']);
        if (empty($allcourses)) {
            return $results;    // Running on a site with no courses built.
        }

        // Get courses that match the following gradebook "usage" scenario.

        // Scenario 1: Courses that have graded grade items.
        $grades = $this->get_graded_items($params['term']);

        // Scenario 2: Courses that have overridden grades.
        $overridden = $this->get_overridden_grades($params['term']);

        // Scenario 3: Courses that have had their grades exported.
        $exported = $this->get_exported_grades($params['term']);

        $usedcourses = array_merge($grades, $overridden, $exported);
        if (empty($usedcourses)) {
            return $results;
        }

        // Built results array. Each array row should have courseid and
        // division, so built array of results indexed by division code. Create
        // array of courseids for used and total. Then we will do an
        // array_unique on each division's used/total columns and replace the
        // value with the array size.

        // Get total counts.
        foreach ($allcourses as $course) {
            if (!isset($results[$course->code])) {
                $results[$course->code]['division']
                        = ucla_format_name($course->fullname, true);

                $results[$course->code]['gradeditems'] = array();
                $results[$course->code]['overriddengrades'] = array();
                $results[$course->code]['exportedgrades'] = array();
                $results[$course->code]['usedgradebook'] = array();
            }
            $results[$course->code]['totalcourses'][] = $course->id;
        }

        // Then use those division totals to count stats counts.
        foreach ($grades as $course) {
            $results[$course->division]['gradeditems'][] = $course->id;
        }
        foreach ($overridden as $course) {
            $results[$course->division]['overriddengrades'][] = $course->id;
        }
        foreach ($exported as $course) {
            $results[$course->division]['exportedgrades'][] = $course->id;
        }
        foreach ($usedcourses as $course) {
            $results[$course->division]['usedgradebook'][] = $course->id;
        }

        // Now unique and sum of counts.
        $numgradeditems = $numoverriddengrades = $numexportedgrades =
                $numusedgradebook = $numtotalcourse = 0;

        foreach ($results as &$result) {
            $count = array_unique($result['gradeditems']);
            $result['gradeditems'] = count($count);

            $count = array_unique($result['overriddengrades']);
            $result['overriddengrades'] = count($count);

            $count = array_unique($result['exportedgrades']);
            $result['exportedgrades'] = count($count);

            $count = array_unique($result['usedgradebook']);
            $result['usedgradebook'] = count($count);

            $count = array_unique($result['totalcourses']);
            $result['totalcourses'] = count($count);

            $numgradeditems += $result['gradeditems'];
            $numoverriddengrades += $result['overriddengrades'];
            $numexportedgrades += $result['exportedgrades'];
            $numusedgradebook += $result['usedgradebook'];
            $numtotalcourse += $result['totalcourses'];
        }

        // Last row should be system totals.
        $results['SYSTEM']['division'] = 'SYSTEM TOTALS';
        $results['SYSTEM']['gradeditems'] = $numgradeditems;
        $results['SYSTEM']['overriddengrades'] = $numoverriddengrades;
        $results['SYSTEM']['exportedgrades'] = $numexportedgrades;
        $results['SYSTEM']['usedgradebook'] = $numusedgradebook;
        $results['SYSTEM']['totalcourses'] = $numtotalcourse;

        return $results;
    }
}
