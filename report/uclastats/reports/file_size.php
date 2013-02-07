<?php

/**
 * Report to get the number of files that exceed 1MB
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class file_size extends uclastats_base {

    /**
     * Instead of counting results, but return actual count.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {
        if (!empty($results)) {
            $result = array_pop($results);
            if (isset($result['file_count'])) {
                return $result['file_count'];
            }
        }
        return 0;
    }

    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array();
    }

    /**
     * Query for number of files over 1 MB
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        $sql = "SELECT COUNT(DISTINCT contenthash) as file_count 
                FROM {files} 
                WHERE filesize > 1048576";
               
        return $DB->get_records_sql($sql, $params);
    }

}
