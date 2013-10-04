<?php

/**
 * Report to get the total downloads for a given term
 * 
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class total_downloads extends uclastats_base {

    /**
     * Instead of counting results, return actual count.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {

        if (!empty($results)) {
            $result = array_pop($results);
            if (isset($result['count'])) {
                return $result['count'];
            }
        }
        
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
     * Query for total downloads for given term
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;
        
        $params['contextlevel'] = CONTEXT_MODULE;
        
        $sql =  "SELECT COUNT(DISTINCT l.id) as count"
                 . $this->from_filtered_courses() .
                "JOIN {log} AS l ON (
                    l.course = c.id
                 )
                 JOIN {course_modules} AS cm ON (
                    cm.course = c.id
                 )
                 JOIN {modules} AS m ON (
                    cm.module = m.id
                 )
                 JOIN {context} ctx ON (
                    cm.id = ctx.instanceid AND
                    ctx.contextlevel = :contextlevel
                 )
                 JOIN {files} f ON (
                     f.contextid = ctx.id
                 )
                 WHERE f.component = 'mod_resource' AND
                 l.time >= :start AND
                 l.time < :end AND
                 m.name ='resource' AND
                 l.cmid = cm.id AND
                 f.filename != '.' AND
                 l.module = 'resource' AND
                 l.action = 'view'";
       
        $term_info = $this->get_term_info($params['term']);
        $params['start'] = $term_info['start'];
        $params['end'] = $term_info['end'];            

        return $DB->get_records_sql($sql, $params);
      
    }

}
