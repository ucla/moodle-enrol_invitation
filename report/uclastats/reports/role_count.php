<?php
/**
 * Report to get the total count per role for a given term
 *
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class role_count extends uclastats_base {
    /**
     * Instead of counting results, but return actual count.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {
        
        $sum = 0;
        
        if (!empty($results)) {
            
            foreach($results as $record){
               $sum += $record['count'];
            }
          
        }
        return $sum;
    }
    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term');
    }

    /**
     * Query to get the total count per role for a given term
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
        $params['contextlevel'] = CONTEXT_COURSE;

        $sql = "SELECT r.name as role, count(DISTINCT ra.userid) as count"
                    . $this->from_filtered_courses() .
                    "
                    JOIN {context} ctx ON ctx.instanceid = c.id
                    JOIN {role_assignments} ra ON (
                        ra.contextid = ctx.id AND
                        ctx.contextlevel = :contextlevel
                    )
                    JOIN {role} r ON ra.roleid = r.id
                GROUP BY ra.roleid";
        return $DB->get_records_sql($sql, $params);
    }
}
