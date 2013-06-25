<?php
/**
 * Report to get activity of course sites from greatest to least
 *
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class most_active_course_sites extends uclastats_base {
    /**
     * Instead of counting results, but return greatest view count.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {
        
        if (!empty($results)) {
            
            //get greatest view count
            $ret_val = array_shift($results);
            return $ret_val['course_title'];
            
        }
        
        //otherwise default to base implementation
        return parent::format_cached_results($results);
    
    }
    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term');
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
     * Query to get the activity of course sites
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        // make sure that term parameter exists
        if (!isset($params['term']) ||
                !ucla_validator('term', $params['term'])) {
            throw new moodle_exception('invalidterm', 'report_uclastats');
        }

        $sql = "SELECT  c.id,
                        c.shortname AS course_title,
                        COUNT(l.id) AS viewcount"
                . $this->from_filtered_courses() .
                "
                JOIN {log} AS l ON (
                 c.id = l.course AND
                 l.action = 'view'
                )
                GROUP BY c.id
                ORDER BY viewcount DESC
                LIMIT 10";
        $results = $DB->get_records_sql($sql, $params);

        foreach ($results as &$course) {
            // Create link to course.
            $course->course_title = html_writer::link(
                    new moodle_url('/course/view.php',
                            array('id' => $course->id)),
                                  $course->course_title,
                            array('target' => '_blank'));

            // Remove id since we don't need it anymore.
            unset($course->id);
        }

        return $results;
    }
}
