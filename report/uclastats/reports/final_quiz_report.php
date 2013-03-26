<?php

/**
 * Report to get the number of quizzes taken during finals week by division
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class final_quiz_report extends uclastats_base {

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

    private function get_term_info($term) {
        // We need to query the registrar
        ucla_require_registrar();

        $results = registrar_query::run_registrar_query('ucla_getterms', array($term), true);

        if (empty($results)) {
            return null;
        }

        $ret_val = array();

        // Get the term start and term end, if it's a summer session,
        // then get start and end of entire summer

        $summer_session_a = array('6A','8A','9A','1A');
        
        foreach ($results as $r) {
            
            $session = $r['session'];

            if ($session == 'RG') {
                $ret_val['start'] = strtotime($r['session_start']);
                $ret_val['end'] = strtotime($r['session_end']);
                break;
            } else if (in_array($session,$summer_session_a)) {
                $ret_val['start_' . strtolower($session)] = strtotime($r['session_start']);
                $ret_val['end_' . strtolower($session)] = strtotime($r['session_end']);
            } else if ($session == '6C') {
                $ret_val['start_c'] = strtotime($r['session_start']);
                $ret_val['end_c'] = strtotime($r['session_end']);
            }
        }
        
        return $ret_val;
    }

    /**
     * Query to get the number of quizzes taken during finals week by division
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

        if (is_summer_term($params['term'])) { //if it is a summer session
            $sql = "SELECT urd.fullname as division,COUNT(DISTINCT q.id) as count
                    FROM {ucla_request_classes} AS urc
                    JOIN {ucla_reg_classinfo} urci ON (
                        urci.term=urc.term AND
                        urci.srs=urc.srs
                    )
                    JOIN {ucla_reg_division} urd ON (
                        urci.division=urd.code
                    )
                    JOIN {quiz} q ON(
                        urc.courseid = q.course
                    )
                    JOIN {quiz_attempts} qa ON (
                        q.id = qa.quiz
                    )
                    WHERE   urc.term = :term  AND
                            urc.hostcourse=1 AND ((
                             urci.session = '6A' AND
                             q.timeopen >= :finals_start_6a AND
                             q.timeclose < :finals_end_6a
                            ) OR (
                              urci.session = '8A' AND
                              q.timeopen >= :finals_start_8a AND
                              q.timeclose < :finals_end_8a
                            ) OR (
                              urci.session = '9A' AND
                              q.timeopen >= :finals_start_9a AND
                              q.timeclose < :finals_end_9a
                            ) OR (
                              urci.session = '1A' AND
                              q.timeopen >= :finals_start_1a AND
                              q.timeclose < :finals_end_1a
                            ) OR (
                              urci.session IN ('6C') AND
                              q.timeopen >= :finals_start_c AND
                              q.timeclose < :finals_end_c
                            ))
                    GROUP BY urci.division
                    ORDER BY urd.fullname";

            //the registar sets term end to 12:00am of term end day
            //meaning at the start of the last day
            //, but there are finals still in the afternoon of the last day
            //so make sure quizzes are closed before midnight of term end 
            return $DB->get_records_sql($sql, array('term' => $params['term'],
                        'finals_start_6a' => strtotime('-1 week', $term_info['end_6a']),
                        'finals_start_8a' => strtotime('-1 week', $term_info['end_8a']),
                        'finals_start_9a' => strtotime('-1 week', $term_info['end_9a']),
                        'finals_start_1a' => strtotime('-1 week', $term_info['end_1a']),
                        'finals_start_c' => strtotime('-1 week', $term_info['end_c']),
                        'finals_end_6a' => strtotime('+1 day', $term_info['end_6a']),
                        'finals_end_8a' => strtotime('+1 day', $term_info['end_8a']),
                        'finals_end_9a' => strtotime('+1 day', $term_info['end_9a']),
                        'finals_end_1a' => strtotime('+1 day', $term_info['end_1a']),
                        'finals_end_c' => strtotime('+1 day', $term_info['end_c']),
                    ));
        } else {
            $sql = "SELECT urd.fullname as division,COUNT(DISTINCT q.id) as count
                    FROM {ucla_request_classes} AS urc
                    JOIN {ucla_reg_classinfo} urci ON (
                        urci.term = urc.term AND
                        urci.srs = urc.srs
                    )
                    JOIN {ucla_reg_division} urd ON (
                        urci.division=urd.code
                    )
                    JOIN {quiz} q ON(
                        urc.courseid = q.course
                    )
                    JOIN {quiz_attempts} qa ON (
                        q.id = qa.quiz
                    )                
                    WHERE   urc.term = :term  AND
                            urc.hostcourse = 1 AND
                            q.timeopen >= :finals_start AND
                            q.timeclose < :finals_end
                    GROUP BY urci.division
                    ORDER BY urd.fullname";


            return $DB->get_records_sql($sql, array('term' => $params['term'],
                        'finals_start' => strtotime('-1 week', $term_info['end']),
                        'finals_end' => strtotime('+1 day', $term_info['end']),
                    ));
        }
    }

}

