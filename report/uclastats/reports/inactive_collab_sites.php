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

class inactive_collab_sites extends uclastats_base {

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
        return array();
    }

    /**
     * Query for course modules used for by courses for given term
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        // get guest role, so that we can filter out that id
        $guest_role = get_guest_role();

        $sql = "SELECT  COUNT(c.id) as count
                FROM    {course} c
                LEFT JOIN {ucla_siteindicator} AS si ON (c.id = si.courseid)
                LEFT JOIN {ucla_request_classes} AS urc ON (c.id=urc.courseid)
                WHERE   urc.id IS NULL AND
                        c.id NOT IN (
                            SELECT course
                            FROM {log} l
                            WHERE userid != :guestid AND
                            time > :six_months_ago)";
        return $DB->get_records_sql($sql, 
                array('six_months_ago' => strtotime('-6 month', strtotime('now')),
                      'guestid' => $guest_role->id));
    }

}