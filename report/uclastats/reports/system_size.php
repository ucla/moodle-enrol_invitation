<?php

/**
 * Returns number of files over 1 MB, size of file system, and size of database
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class system_size extends uclastats_base {

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

        global $CFG;
        
        $ret_val = array();

        // Get count of distinct files over 1MB.
        $sql = "SELECT COUNT(DISTINCT contenthash) 
                FROM {files} 
                WHERE filesize > 1048576";        
        $ret_val['file_count'] = $DB->get_field_sql($sql);
        
        // Get size of database in bytes.
        $sql = "SELECT Sum(data_length + index_length) 
                FROM   information_schema.tables 
                WHERE table_schema = 'moodle'";        
        $ret_val['database_size'] = display_size($DB->get_field_sql($sql));
        
        return array($ret_val);
    }

}
