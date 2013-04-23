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
            return $ret_val['viewcount'];
            
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
     * Query to get activity of collab sites
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        $sql = "SELECT c.shortname as course_title, COUNT(l.id) AS viewcount
                FROM {log} AS l
                JOIN {course} AS c ON (
                    l.course = c.id
                )
                LEFT JOIN {ucla_request_classes} AS urc ON (
                    urc.courseid = c.id
                )
                WHERE urc.id IS NULL AND
                      l.action = 'view' AND
                      c.id != 1
                GROUP BY c.id
                ORDER BY viewcount DESC
                LIMIT 10";
        
        return $DB->get_records_sql($sql);
    }
}
