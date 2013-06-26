<?php
/**
 * Report to get activity of collab sites from greatest to least
 *
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class most_active_collab_sites extends uclastats_base {
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
        return array();
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
     * Query to get activity of collab sites
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;
        
        // we do not want to eliminate sites that are not in the registrar
        $sql = "SELECT  c.id,
                        c.shortname as course_title,
                        COUNT(l.id) AS viewcount
                FROM {log} AS l
                JOIN {course} AS c ON (
                    l.course = c.id
                )
                LEFT JOIN {ucla_request_classes} AS urc ON (
                    urc.courseid = c.id
                )
                WHERE urc.id IS NULL AND
                      l.action = 'view' AND
                      c.id != ?
                GROUP BY c.id
                ORDER BY viewcount DESC
                LIMIT 10";
        $results = $DB->get_records_sql($sql, array(SITEID));

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
