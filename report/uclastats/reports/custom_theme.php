<?php

/**
 * Report to get the number of custom themed courses
 * 
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class custom_theme extends uclastats_base {

    /**
     * Instead of counting results, but return actual count.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {
        if (!empty($results)) {
            $result = array_pop($results);
            if (isset($result['theme_count'])) {
                return $result['theme_count'];
            }
        }
        return 0;
    }

    /**
     *  In addition to showing the count, add a table for a linked shortname,title of courses with custom themes
     * 
     * @global $DB
     * @param  int $resultid
     * @return string
     */
    public function display_result($resultid) {

        global $DB;

        $display = parent::display_result($resultid);

        $sql = "SELECT DISTINCT c.id,shortname,fullname,c.theme 
                FROM mdl_course c, mdl_config config
                WHERE c.theme != '' 
                AND config.name = 'theme' 
                AND config.value != c.theme";

        $table_string = '';
        $courses = $DB->get_records_sql($sql);

        if (count($courses) > 0) {
            $results_table = new html_table();

            $results_table->head = array("Course", "Course title", "Theme");
            $results_table->data = $courses;


            foreach ($courses as $key => $course) {

                //create link on course
                $course->shortname = html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)), $course->shortname, null);

                unset($course->id);
            }

            $table_string = html_writer::table($results_table);
        }

        return $display . $table_string;
    }

    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array();
    }

    /**
     * Query for courses with custom themes
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        $sql = "SELECT COUNT( * ) as theme_count
                FROM mdl_course c, mdl_config config
                WHERE c.theme != ''
                AND config.name = 'theme'
                AND config.value != c.theme";

        return $DB->get_records_sql($sql, $params);
    }

}
