<?php

/**
 * Report to get the total, active, and inactive count of collab sites for a
 * given term.
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class collab_num_sites extends uclastats_base {

    /**
     * Instead of counting results, return a summarized result.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {
        if (!empty($results)) {
            $result = array_pop($results);
            $stats = new stdClass();
            $stats->total = $stats->active = $stats->inactive = 0;
            if (isset($result['total_count'])) {
                $stats->total = $result['total_count'];
                $stats->active = $result['active_count'];
                $stats->inactive = $result['inactive_count'];
            }
            return get_string('num_sites_cached_results', 'report_uclastats', $stats);
        }
        return get_string('nocachedresults', 'report_uclastats');
    }

    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array();
    }

    /**
     * Query for total, active, and inactive count of collab sites for given term.
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        // get guest role, so that we can filter out that id
        $guest_role = get_guest_role();

        $sql = "SELECT COUNT(DISTINCT c.id)
                FROM {log} AS l
                JOIN {course} AS c ON (
                    l.course = c.id
                )
                LEFT JOIN {ucla_request_classes} AS urc ON (
                    urc.courseid = c.id
                ) WHERE urc.id IS NULL";

        $ret_val['total_count'] = $DB->get_field_sql($sql);

        $sql = "SELECT COUNT(DISTINCT c.id)
                FROM {course} c
                    LEFT JOIN {ucla_siteindicator} AS si ON (c.id = si.courseid)
                WHERE c.id NOT IN (
                    SELECT courseid
                    FROM {ucla_request_classes} 
                )
                AND c.id NOT IN (
                    SELECT course
                    FROM {log} l
                    WHERE userid != :guestid AND
                    time > :six_months_ago
                )";

        $ret_val['inactive_count'] = $DB->get_field_sql($sql,
                array('six_months_ago' => strtotime('-6 month', strtotime('now')),
                      'guestid' => $guest_role->id));

        $ret_val['active_count'] = (string) ($ret_val['total_count'] -
                $ret_val['inactive_count']);

        return array($ret_val);
    }

}