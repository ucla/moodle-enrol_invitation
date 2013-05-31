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
     * Since we are querying the mdl_log table a lot, we need to give a warning.
     * 
     * @return boolean  Returns true
     */
    public function is_high_load() {
        return true;
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
        
        if(is_summer_term($params['term'])) {
            $term_info['start_a'] = $term_info['start_1a'];
            $term_info['end_a'] = $term_info['end_1a'];
        }

        // get guest role, so that we can filter out that id
        $guest_role = get_guest_role();

        if (is_summer_term($params['term'])) { //if it is a summer sessions

            $sql = "SELECT urd.fullname as division, count(DISTINCT urc.id) AS count"
            . $this->from_filtered_courses() .
            "
            JOIN {ucla_reg_division} urd ON (
                urci.division=urd.code
            ) 
            WHERE 
            ((urci.session IN ('6A', '8A', '1A') AND
            urc.courseid NOT IN (
                SELECT l.course
                FROM {log} l 
                WHERE l.userid != :guestida AND
                l.time > :first_week_of_a AND
                l.time <= :end_a)
            )
            OR
            (urci.session IN ('6C') AND
            urc.courseid NOT IN (
                SELECT l.course
                FROM {log} l 
                WHERE l.userid != :guestidc AND
                l.time > :first_week_of_c AND
                l.time <= :end_c)
            ))
            GROUP BY urci.division
            ORDER BY urd.fullname";

            return $DB->get_records_sql($sql,
                    array('term' => $params['term'],
                          'first_week_of_a' => strtotime('+1 week', $term_info['start_a']),
                          'first_week_of_c' => strtotime('+1 week', $term_info['start_c']),
                          'end_a' => $term_info['end_a'],
                          'end_c' => $term_info['end_c'],
                          'guestida' => $guest_role->id,
                          'guestidc' => $guest_role->id));
        } else {
            $sql = "SELECT urd.fullname as division, count(DISTINCT urc.id) AS count"
                    . $this->from_filtered_courses() .
                    "
                    JOIN {ucla_reg_division} urd ON (
                        urci.division=urd.code
                    )
                    WHERE 
                    urc.courseid  NOT IN (
                         SELECT l.course
                         FROM {log} l 
                         WHERE l.userid != :guestid AND
                         l.time > :first_week_of_term AND
                         l.time <= :end
                    )
                    GROUP BY urci.division
                    ORDER BY urd.fullname";

            return $DB->get_records_sql($sql, 
                    array('term' => $params['term'],
                          'first_week_of_term' => strtotime('+1 week', $term_info['start']),
                          'end' => $term_info['end'],
                          'guestid' => $guest_role->id));
        }
    }

}

