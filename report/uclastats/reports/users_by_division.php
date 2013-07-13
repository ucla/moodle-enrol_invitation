<?php

/**
 * Counts the number of hits, users,  
 * ratio of (hits/users) for a division by term, 
 * and number of users of the entire system by term
 * 
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class users_by_division extends uclastats_base {

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
     * Query for users by division
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        $params['contextlevel'] = CONTEXT_COURSE;

        // Hits by division per term
        // Users by division per term
        // left join to also count no hits
        $sql = "SELECT  DISTINCT urd.fullname as division,
                        COUNT(DISTINCT l.id) AS hits,
                        COUNT(DISTINCT ra.userid) AS total_users"
                . $this->from_filtered_courses() . "
                JOIN {ucla_reg_division} urd ON (
                    urci.division = urd.code
                )
                JOIN {context} ctx ON (
                    urc.courseid = ctx.instanceid AND
                    ctx.contextlevel = :contextlevel
                )
                JOIN {role_assignments} ra ON (
                    ra.contextid = ctx.id
                )
                LEFT JOIN {log} l ON (
                    l.course = urc.courseid AND
                    l.userid = ra.userid
                )
                GROUP BY urci.division
                ORDER BY urd.fullname";
        $ret = $DB->get_records_sql($sql, $params);

        foreach ($ret as &$record) {
            $record->ratio_hits_users = (
                    number_format(($record->total_users == 0) ? 0 :
                                    ($record->hits / $record->total_users), 2));
        }

        // Get stats for users of the entire system.
        $sql = "SELECT  'SYSTEM' AS division,
                        COUNT(DISTINCT l.id) AS hits,
                        COUNT(DISTINCT ra.userid) AS total_users"
                . $this->from_filtered_courses() . "
                JOIN {context} ctx ON (
                    ctx.instanceid = c.id AND
                    ctx.contextlevel = :contextlevel
                )
                JOIN {role_assignments} ra on (
                    ra.contextid = ctx.id
                )
                LEFT JOIN {log} l ON (
                    l.course = urc.courseid AND
                    l.userid = ra.userid
                )";
        $system = $DB->get_record_sql($sql, $params);

        $system->ratio_hits_users = (
                    number_format(($system->total_users == 0) ? 0 :
                                    ($system->hits / $system->total_users), 2));

        $ret['SYSTEM'] = $system;

        return $ret;
    }

}
