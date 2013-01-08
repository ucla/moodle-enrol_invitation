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

class sites_per_term extends uclastats_base {
    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term');
    }

    /**
     * 
     * @param type $params
     */
    public function query($params) {
        global $DB;

        // make sure that term parameter exists
        if (!isset($params['term']) ||
                !ucla_validator('term', $params['term'])) {
            throw new moodle_exception('invalidterm', 'report_uclastats');
        }

        $sql = "SELECT  COUNT(DISTINCT c.id) AS site_count
                FROM    {course} AS c,
                        {ucla_request_classes} AS urc
                WHERE   urc.courseid=c.id AND
                        urc.term=:term AND
                        urc.hostcourse=1";
        return $DB->get_records_sql($sql, $params);;
    }
}
