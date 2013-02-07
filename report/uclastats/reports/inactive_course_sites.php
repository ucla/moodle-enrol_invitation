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
     * Instead of counting results, but return actual count.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {

        $sum = 0;

        if (!empty($results)) {


            foreach ($results as $record) {

                $sum += $record['inactive_course_count'];
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

    private function get_term_info($term) {
        // We need to query the registrar
        ucla_require_registrar();

        $results = registrar_query::run_registrar_query('ucla_getterms', array($term), true);

        if (empty($results)) {
            return null;
        }

        $ret_val = array();

        // Get ther term start and term end, if it's a summer session,
        // then get start and end of entire summer
        foreach ($results as $r) {
            if ($r['session'] == 'RG') {
                $ret_val['start'] = strtotime($r['session_start']);
                $ret_val['end'] = strtotime($r['session_end']);
                break;
            } else if ($r['session'] == '8A') {
                $ret_val['start_a'] = strtotime($r['session_start']);
            } else if ($r['session'] == '6C') {
                $ret_val['start_c'] = strtotime($r['session_start']);
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

        if (is_summer_term($params['term'])) { //if it is a summer sessions
            $sql = "SELECT count(DISTINCT urc.id) AS inactive_course_count, urd.fullname as division
            FROM {ucla_request_classes} AS urc
            JOIN {ucla_reg_classinfo} urci ON (
                urci.term=urc.term AND
                urci.srs=urc.srs
            )
            JOIN {ucla_reg_division} urd ON (
                urci.division=urd.code
            ) 
            WHERE urc.term = :term  AND
            urc.hostcourse=1 AND
            (urci.session IN ('6A', '8A', '1A') AND
            urc.courseid NOT IN (
                SELECT l.course
                FROM {log} l 
                WHERE l.userid > 1 AND 
                l.time > (:first_week_of_a))
            )
            OR
            (urci.session IN ('6C') AND
            urc.courseid NOT IN (
                SELECT l.course
                FROM {log} l 
                WHERE l.userid > 1 AND 
                l.time > (:first_week_of_c))
            )
            GROUP BY urci.division";

            return $DB->get_records_sql($sql, array('term' => $params['term'],
                        'first_week_of_a' => strtotime('+1 week', $term_info['start_a']),
                        'first_week_of_c' => strtotime('+1 week', $term_info['start_c'])));
        } else {

            $sql = "SELECT COUNT(c.id) as inactive_course_count
                    FROM {course} c
                    WHERE 
                    c.shortname LIKE :shortname AND 
                    c.id NOT IN (
                         SELECT l.course
                         FROM {log} l 
                         WHERE l.userid > 1 AND 
                         l.time > :first_week_of_term
                    )";


            return $DB->get_records_sql($sql, array('shortname' => $params['term'] . "-%", 'first_week_of_term' => strtotime('+1 week', $term_info['start'])));
        }
    }

}

