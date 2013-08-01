<?php
/**
 * Report to get the number of course modules used for collab sites for a given
 * term.
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class collab_modules_used extends uclastats_base {
    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array();
    }

    /**
     * Query for course modules used for by collab sites.
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        $sql = "SELECT  m.name AS module,
                        COUNT(cm.id) AS count
                FROM    {course} AS c
                JOIN    {course_modules} cm ON
                        (cm.course=c.id)
                JOIN    {modules} m ON
                        (m.id=cm.module)
                LEFT JOIN {ucla_siteindicator} AS si ON (c.id = si.courseid)
                LEFT JOIN {ucla_request_classes} AS urc ON (c.id=urc.courseid)
                WHERE   urc.id IS NULL AND
                        si.type!='test'
                GROUP BY m.id
                ORDER BY m.name";
        $results = $DB->get_records_sql($sql, $params);
        foreach ($results as &$result) {
           $result->module=get_string('pluginname', 'mod_' . $result->module);  
        }
        array_alphasort($results, 'module');
        return $results;
    }
}
