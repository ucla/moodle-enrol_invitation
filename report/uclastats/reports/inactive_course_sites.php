<?php

/**
 * Report to get the total number of course sites for a given term.
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class inactive_course_sites extends uclastats_base {

    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term');
    }
    
    private function get_term_info($term) {
        // We need to query the registrar
        ucla_require_registrar();

        $results = registrar_query::run_registrar_query('ucla_getterms',
                    array($term),true);

        if (empty($results)) {
            return null;
        }

        $ret_val = array();

        // Get ther term start and term end, if it's a summer session,
        // then get start and end of entire summer
        foreach($results as $r) {
            if($r['session'] == 'RG') {
                $ret_val['start'] = strtotime($r['session_start']);
                $ret_val['end'] = strtotime($r['session_end']);
                break;
            } else if($r['session'] == '8A') {
                $ret_val['start'] = strtotime($r['session_start']);
            } else if($r['session'] == '6C') {
                $ret_val['end'] = strtotime($r['session_end']);
            }
        }

        return $ret_val;
    }


    /**
     * Query for course modules used for by courses for given term
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
        
        // get start and end dates for term
        $term_info = $this->get_term_info($params['term']);

        $sql = "SELECT COUNT(c.id) as inactive_course_count
                FROM mdl_course c
                WHERE 
                c.shortname LIKE :shortname AND 
                c.id NOT IN (
                     SELECT l.course
                     FROM mdl_log l 
                     WHERE l.userid > 1 AND 
                     l.time > :first_week_of_term)";
                
        
        return $DB->get_records_sql($sql, array('shortname'=> $params['term'] . "-%", 'first_week_of_term' => strtotime('+1 week',$term_info['start'])));
    }
    
     
   

}

