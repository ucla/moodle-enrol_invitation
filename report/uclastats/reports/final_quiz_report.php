<?php

/**
 * Report to get the number of quizzes taken during 10th week and Finals week by division
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
                if (isset($record['last_week_count'])) {
                    $sum += $record['last_week_count'];
                    $sum += $record['final_count'];
                }
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

    protected function get_term_info($term) {
        // We need to query the registrar
        ucla_require_registrar();

        $results = registrar_query::run_registrar_query('ucla_getterms', array($term), true);

        if (empty($results)) {
            return null;
        }

        $ret_val = array();

        //Get the end of each session
        //Also compute the time ranges for start/end of last week and start/end of finals week

        $summer_session_a = array('6A', '8A', '9A', '1A');

        foreach ($results as $r) {

            $session = $r['session'];
            
            //upper bound end of finals week is Saturday 12:00 A.M. 
            $term_end = strtotime('+1 day',strtotime($r['session_end']));

            if ($session == 'RG') {
                
                //For regular
                //10th week starts on Sat after 9th's week Friday until the Friday before Final's week Monday.
                //Finals week starts on Sat after 10th's week Friday until end of Friday for Final's week.
                $ret_val['last_week_start'] = strtotime('-2 week', $term_end);
                $ret_val['last_week_end'] = strtotime('-1 week', $term_end);
                
                //note that end of last week end is equivalent to beginning of finals
                //also note that parameters in database API cannot be reused in same query
                //that's why we have last_week_end and finals_start even though they are the same
                //https://tracker.moodle.org/browse/MDL-25243
                
                $ret_val['finals_start'] = strtotime('-1 week', $term_end);
                $ret_val['finals_end'] = $term_end;
                
            } else if (in_array($session, $summer_session_a) || $session == '6C') {

                if ($session == '6C') {
                    $session_lowercase = 'c';
                } else {
                    $session_lowercase = strtolower($session);
                }
                //For summer:
                //Last week begins Sat after previous week's Friday and ends before the Friday.
                //Finals is the last day of class.
                
                //For summer sessions, last week of each respective session is considered last_week
                $ret_val['last_week_start_' . $session_lowercase] = strtotime('-1 week', $term_end);

                $ret_val['last_week_end_' . $session_lowercase] = strtotime('-1 day', $term_end);

                //Last day should be the only one counted for "Finals week".
                //note that end of last week end is equivalent to beginning of finals
                $ret_val['finals_start_' . $session_lowercase] = strtotime('-1 day', $term_end);

                $ret_val['finals_end_' . $session_lowercase] = $term_end;
            }
        }

        return $ret_val;
    }

    /**
     * Query to get the number of quizzes taken during 10th week and Finals week by division
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
            
            //these variable will be used to generate certain repeated segments of query
            $last_session = 'c';//this is intentionally c instead of 6c
            //to adhere to moodle coding style keep variable names lowercase
            //must also keep actual represenation when comparing values for dictionary
            $summer_session = array('6a' => "'6A'",
                '8a' => "'8A'",
                '9a' => "'9A'",
                '1a' => "'1A'",
                $last_session => "'6C'");
            
            $sql = "SELECT urd.fullname as division,
                           COUNT(DISTINCT (CASE WHEN (";
            //filter last week quizzes
            /*(urci.session = '6A' AND q.timeclose < :last_week_end_6a) OR
              ...
              (urci.session = '6C' AND q.timeclose < :last_week_end_c) */

            foreach ($summer_session as $key => $val) {

                $sql .= ' (urci.session = ' . $val . ' AND q.timeclose < :last_week_end_' . $key . ') ';

                if ($key != $last_session) {
                    $sql .= ' OR ';
                }
            }

            $sql .= ") then q.id end)) as last_week_count,
                     COUNT(DISTINCT (CASE WHEN (";
            
            //filter final quizzes
            /*(urci.session = '6A' AND q.timeclose >= :finals_start_6a) OR
              ...
              (urci.session = '6C' AND q.timeclose >= :finals_start_c) */
          
            foreach ($summer_session as $key => $val) {

                $sql .= ' (urci.session = ' . $val . ' AND q.timeclose >= :finals_start_' . $key . ') ';

                if ($key != $last_session) {
                    $sql .= ' OR ';
                }
            }
            $sql .= ") then q.id end)) as final_count"
                    . $this->from_filtered_courses() .
                    "
                    JOIN {ucla_reg_division} urd ON (
                        urci.division = urd.code
                    )
                    JOIN {quiz} q ON (
                        c.id = q.course 
                    )
                    JOIN {quiz_attempts} qa ON (
                        q.id = qa.quiz
                    )
                    WHERE (";
            
            //check that quizzes are within range of last_week to end of term
            /*(urci.session = '6A' AND
            q.timeopen >= :last_week_start_6a AND
            q.timeclose < :finals_end_6a
            ...
            ) OR (
            urci.session IN ('6C') AND
            q.timeopen >= :last_week_start_c AND
            q.timeclose < :finals_end_c)*/

           foreach ($summer_session as $key => $val) {

               $sql .= ' (urci.session = ' . $val . ' AND q.timeopen >= :last_week_start_' . $key . ' AND '
                    . 'q.timeclose < :finals_end_' . $key . ' ) ';

               if ($key != $last_session) {
                   $sql .= ' OR ';
               }

           }
  
        $sql .= ")
                 GROUP BY urci.division
                 ORDER BY urd.fullname";
        } else {

            $sql = "SELECT urd.fullname as division,
                           COUNT(
                               DISTINCT (CASE WHEN q.timeclose < :last_week_end then q.id end)
                           ) as last_week_count,
                           COUNT(
                               DISTINCT (CASE WHEN q.timeclose >= :finals_start then q.id end)

                           ) as final_count "
                    . $this->from_filtered_courses() .
                    "
                    JOIN {ucla_reg_division} urd ON (
                        urci.division = urd.code
                    )
                    JOIN {quiz} q ON(
                        urc.courseid = q.course
                    )
                    JOIN {quiz_attempts} qa ON (
                        q.id = qa.quiz
                    )                
                    WHERE q.timeopen >= :last_week_start AND
                          q.timeclose < :finals_end
                    GROUP BY urci.division
                    ORDER BY urd.fullname";
        }//end regular term sql statement

        return $DB->get_records_sql($sql, array_merge($params, $term_info));
    }

}

