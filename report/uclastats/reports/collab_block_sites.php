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
     *  Query to get the number of collab blocks and their block names
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;
        
        $params['contextlevel'] = CONTEXT_COURSE;

        $sql = "SELECT bi.blockname,COUNT(bi.id) as count
                FROM {course} c
                JOIN {context} ctx ON (
                    ctx.contextlevel = :contextlevel AND
                    ctx.instanceid = c.id)
                JOIN {block_instances} bi ON (
                    bi.parentcontextid = ctx.id
                )
                JOIN {block} b ON (
                    bi.blockname = b.name
                )
                LEFT JOIN {ucla_siteindicator} AS si ON (c.id = si.courseid)
                LEFT JOIN {ucla_request_classes} AS urc ON (c.id=urc.courseid)
                WHERE   urc.id IS NULL AND
                        si.type!='test'
                GROUP BY b.id
                ORDER BY b.name";
        $results = $DB->get_records_sql($sql, $params);
        foreach ($results as &$result) {
           $result->blockname=get_string('pluginname', 'block_' . $result->blockname);
        }
        array_alphasort($results, 'blockname');
        return $results;
    }
}
