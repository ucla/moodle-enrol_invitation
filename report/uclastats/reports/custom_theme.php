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

            return count($results);
        }
        return 0;
    }

    /**
     *  In  addition to creating a table for a linked shortname,title of courses with custom themes
     *  also include a header indicating total count 
     * 
     * @global $DB
     * @param  int $resultid
     * @return string
     */
    public function display_result($resultid) {

        global $DB;

        try {

            $result = new uclastats_result($resultid);
        } catch (dml_exception $e) {
            return get_string('nocachedresults', 'report_uclastats');
        }

        //set up header containing total count
        $header_text = html_writer::tag('strong', get_string('theme_count', 'report_uclastats'), array());

        $header = html_writer::tag('p', $header_text . count($result->results), array());

        //create table for a linked shortname,title of courses with custom themes
        $display = parent::display_result($resultid);


        return $header . $display;
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

        $sql = "SELECT DISTINCT c.id,shortname as course_shortname,fullname as course_title ,c.theme 
                FROM mdl_course c, mdl_config config
                WHERE c.theme != ''
                AND config.name = 'theme'
                AND config.value != c.theme";

        $courses = $DB->get_records_sql($sql, $params);
        foreach ($courses as $key => $course) {

            //create link on course shortname
            $course->course_shortname = html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)), $course->course_shortname, null);

            unset($course->id);
        }

        return $courses;
    }

}
