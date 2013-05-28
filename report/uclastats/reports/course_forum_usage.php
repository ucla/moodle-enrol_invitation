<?php
/**
 * Report to get forum usage by average number of posters
 * and average number of threads for course sites
 *
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class course_forum_usage extends uclastats_base {
    /**
     * Instead of counting results, return a summarized result.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {
         if (!empty($results)) {
            $result = array_pop($results);
            $result = (object) $result;
           
            return get_string('forum_usage_cached_results',
                    'report_uclastats', $result);
        }
        return get_string('nocachedresults', 'report_uclastats');
    }
    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term');
    }

    /**
     * Query to get forum usage by average number of posters
     * and average number of threads for collab sites
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        $ret_val = array();
        
        //average number of posters
        $sql = 
        "SELECT COUNT(DISTINCT p.userid) / COUNT(DISTINCT f.id)"
        . $this->from_filtered_courses() .
        "
        JOIN {forum} f ON (
          c.id = f.course
        )
        LEFT JOIN {forum_discussions} d ON (
          d.forum = f.id
        )
        LEFT JOIN {forum_posts} p ON (
          p.discussion = d.id
        )";
        
        $avg_num_posters = $DB->get_field_sql($sql,$params);
        $ret_val['avg_num_posters'] = number_format(is_null($avg_num_posters) ? 
                                      0  : $avg_num_posters, 2);
        //average number of threads
        $sql = 
        "SELECT COUNT(DISTINCT d.id) / COUNT(DISTINCT f.id)"
        . $this->from_filtered_courses() .
        "
        JOIN {forum} f ON (
            c.id = f.course
        )
        LEFT JOIN {forum_discussions} d ON (
            d.forum = f.id
        )";

        $avg_num_threads = $DB->get_field_sql($sql,$params);
        $ret_val['avg_num_threads'] = number_format(is_null($avg_num_threads) ?
                                      0 : $avg_num_threads, 2);


        return array($ret_val);

  }
}

