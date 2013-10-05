<?php
/**
 * Report to get the total, active, and inactive count of course sites for a
 * given term by division.
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class course_num_sites extends uclastats_base {

    /**
     * Instead of counting results, return a summarized result.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {
        if (!empty($results)) {
            $total = $active = 0;
            foreach ($results as $record) {
                if (isset($record['total_count'])) {
                    $total += $record['total_count'];
                    $active += $record['active_count'];
                }
            }

            $stats = new stdClass();
            $stats->total = $total;
            $stats->active = $active;
            $stats->inactive = $total - $active;
            return get_string('num_sites_cached_results', 'report_uclastats', $stats);
        }
        return get_string('nocachedresults', 'report_uclastats');
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
     * Query for total, active, and inactive count of courses for given term by division.
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

        // get guest role, so that we can filter out that id
        $guest_role = get_guest_role();

        // total sites
        $sql = "SELECT urd.fullname AS division, COUNT(distinct urc.courseid) AS total_count"
                . $this->from_filtered_courses() .
                "
                JOIN mdl_ucla_reg_division urd ON (
                urci.division = urd.code
                ) 
                GROUP BY urci.division";

        $total_sites = $DB->get_records_sql($sql, $params);


        if (is_summer_term($params['term'])) { 
            // if it is a summer sessions
            $sql = "SELECT  urd.fullname as division,
                            count(DISTINCT urc.id) AS inactive_count"
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

            $params['first_week_of_a'] = strtotime('+1 week', $term_info['start_a']);
            $params['first_week_of_c'] = strtotime('+1 week', $term_info['start_c']);
            $params['end_a'] = $term_info['end_a'];
            $params['end_c'] = $term_info['end_c'];
            $params['guestida'] = $guest_role->id;
            $params['guestidc'] = $guest_role->id;
        } else {
            $sql = "SELECT  urd.fullname AS division,
                            COUNT(DISTINCT urc.id) AS inactive_count"
                    . $this->from_filtered_courses() .
                    "
                    JOIN {ucla_reg_division} urd ON (
                        urci.division = urd.code
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

            $params['first_week_of_term'] = strtotime('+1 week', $term_info['start']);
            $params['end'] = $term_info['end'];
            $params['guestid'] = $guest_role->id;
        }

        $inactive_sites = $DB->get_records_sql($sql, $params);

        foreach ($total_sites as $key => &$record) {
            $inactive_count = 0;
            if (isset($inactive_sites[$key])) {
                $inactive_count = $inactive_sites[$key]->inactive_count;
            }
            $record->active_count = $record->total_count - $inactive_count;
            $record->inactive_count = $inactive_count;
        }

        return $total_sites;
    }

}

