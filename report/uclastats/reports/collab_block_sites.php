<?php

/**
 * Report to get the number of collab blocks and their block names
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class collab_block_sites extends uclastats_base {

    /**
     * Instead of counting results, but return total count of blocks.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {

        $sum = 0;
        if (!empty($results)) {

            foreach ($results as $record) {
                $sum += $record['collab_block_count'];
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
     * Query for number of files over 1 MB
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        $sql = "SELECT bi.blockname,COUNT(bi.id) as collab_block_count
                FROM {course} c
                JOIN {context} ct ON (
                    ct.contextlevel = 50 AND
                    ct.instanceid = c.id)
                JOIN {block_instances} bi ON (
                    bi.parentcontextid = ct.id
                )
                JOIN {block} b ON (
                    bi.blockname = b.name
                )
                LEFT JOIN {ucla_siteindicator} AS si ON ( c.id = si.courseid )
                WHERE c.id NOT IN (
                    SELECT courseid
                    FROM {ucla_request_classes}
                 ) AND
                si.type!='test'
                GROUP BY b.id
                ORDER BY b.name";
        return $DB->get_records_sql($sql, $params);
    }

}
