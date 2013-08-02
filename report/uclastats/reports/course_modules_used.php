<?php
/**
 * Report to get the number of course modules used for course sites for a given
 * term.
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class course_modules_used extends uclastats_base {
    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term');
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

        $sql = "SELECT  m.name AS module,
               COUNT(cm.id) AS count"
                . $this->from_filtered_courses() .
                "
                JOIN    {course_modules} cm ON
                        (cm.course = c.id)
                JOIN    {modules} m ON
                        (m.id = cm.module)
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
