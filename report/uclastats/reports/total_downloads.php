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

    private function get_term_info($term) {
        // We need to query the registrar
        ucla_require_registrar();

        $results = registrar_query::run_registrar_query('ucla_getterms', array($term), true);

        if (empty($results)) {
            return null;
        }

        $ret_val = array();

        // Get the term start and term end
        // if it's summer, summer spans from beginning of session A 
        // to end of session C
        
        foreach ($results as $r) {
            if ($r['session'] == 'RG') {
                $ret_val['start'] = strtotime($r['session_start']);
                $ret_val['end'] = strtotime($r['session_end']);
                break;
            } else if ($r['session'] == '1A') {
                $ret_val['start'] = strtotime($r['session_start']);
            } else if ($r['session'] == '6C') {
                $ret_val['end'] = strtotime($r['session_end']);
            }
        }

        return $ret_val;
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
        
        $sql =  "SELECT COUNT(DISTINCT l.id) as count
                 FROM mdl_log AS l
                 JOIN mdl_course c ON (
                    l.course = c.id
                 )
                 JOIN mdl_course_modules AS cm ON (
                    cm.course = c.id
                 )
                 JOIN mdl_modules AS m ON (
                    cm.module=m.id
                 )
                 JOIN mdl_context ctx ON (
                    cm.id = ctx.instanceid AND
                    ctx.contextlevel= :contextlevel
                 )
                 JOIN mdl_files f ON (
                     f.contextid = ctx.id
                 )
                 JOIN mdl_ucla_request_classes urc ON (
                    c.id = urc.courseid
                 )  
                 WHERE urc.term = :term AND
                 f.component = 'mod_resource' AND
                 l.time >= :start AND
                 l.time < :end AND
                 m.name ='resource' AND
                 l.cmid = cm.id AND
                 f.filename != '.' AND
                 l.module = 'resource' AND
                 l.action = 'view'";
       
        $term_info = $this->get_term_info($params['term']);
        $params = array_merge($term_info,$params);

        return $DB->get_records_sql($sql, $params);
      
    }

}
