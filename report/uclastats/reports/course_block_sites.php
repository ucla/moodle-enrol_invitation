<?php

/**
 * Report to get the number of course blocks and their block names
 * 
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class course_block_sites extends uclastats_base {

    /**
     * Instead of counting results, but return total count of block.
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
        return array('term');
    }

    /**
     * Query get the number of course blocks and their block names
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;
        
        $params['contextlevel'] = CONTEXT_COURSE;

        $sql = "SELECT bi.blockname, COUNT(bi.id) as count"
                . $this->from_filtered_courses() .
                "
                JOIN {context} ctx ON (
                    ctx.contextlevel = :contextlevel AND
                    ctx.instanceid = c.id)
                JOIN {block_instances} bi ON (
                    bi.parentcontextid = ctx.id
                )
                JOIN {block} b ON (
                    bi.blockname = b.name
                )
                GROUP BY b.id
                ORDER BY b.name";
       
        return $DB->get_records_sql($sql, $params);
    }

}
