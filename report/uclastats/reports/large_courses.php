<?php

/**
 * Report to get all courses over the specified file size limit 
 * and a breakdown of number of files and the total size 
 * for each type of mimetype of a corresponding course
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class large_courses extends uclastats_base {

    /**
     * Returns associated help text for given report.
     *
     * @return string
     */
    public function get_help() {
        return html_writer::tag('p', get_string(get_class($this) .
                '_help', 'report_uclastats',
                display_size(get_config('moodlecourse', 'maxbytes'))),
                array('class' => 'report-help'));
    }

    /**
     * Returns an array of form elements used to run report.
     */
    public function get_parameters() {
        return array('term');
    }

    /**
     * Query to get all courses over the specified file size limit
     * and a breakdown of number of files and the total size 
     * for each type of mimetype of a corresponding course
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;
        $params['maxbytes'] = get_config('moodlecourse', 'maxbytes');
        $params['contextlevel'] = CONTEXT_MODULE;

        $mimetypes_array = get_mimetypes_array();
        $types_to_group = array();
        //if a mimetype does not map to a group it will simply be listed as other
        $groups = array("other" => (object) array("size" => 0, "count" => 0));

        //retrieve all mimetypes and create a mimetypes to group map
        //build an associatve array of all groups; 
        //this associative array will be used to sum up file breakdowns for each course

        foreach ($mimetypes_array as $key => $record) {

            $types_to_group[$record["type"]] = array_key_exists("groups", $record) ? $record["groups"] : array("other");
            if (isset($record["groups"]) && !array_key_exists($record["groups"][0], $groups)) {
                $groups[$record["groups"][0]] = (object) array("size" => 0, "count" => 0);
            }
        }

        //this segment of query is used for each query string
        $sql_body =   $this->from_filtered_courses() .
                      "
                      JOIN {ucla_reg_division} urd ON (
                          urci.division = urd.code
                      )
                      JOIN {course_modules} AS cm ON (
                          cm.course = c.id
                      )
                      JOIN {context} ctx ON (
                          cm.id = ctx.instanceid AND
                          ctx.contextlevel = :contextlevel
                      )
                      JOIN {files} f ON (
                           f.contextid = ctx.id
                      ) 
                      WHERE f.filename <> '.' AND
                      f.component = 'mod_resource' ";

        //query to locate all large courses
        $sql = "SELECT c.id,c.shortname as course_title, SUM(f.filesize) AS course_size"
                . $sql_body .
                "GROUP BY c.id 
                    HAVING course_size > :maxbytes
                ORDER BY course_size DESC";

        $query_result = $DB->get_records_sql($sql, $params);

        //query to get all files belonging to large course
        $sql2 = "SELECT DISTINCT f.id, f.mimetype, f.filesize"
                . $sql_body .
                " AND c.id = :course_id";

        //update query result with file information for corresponding courses
        foreach ($query_result as &$course) {
            $params["course_id"] = $course->id;

            //retrieve all related course files
            $course_files = $DB->get_records_sql($sql2, $params);
            $file_break_down = $groups;
            
            foreach ($course_files as $file) {

                $group_name = $types_to_group[$file->mimetype][0]; //use first groupname so we don't count file multiple times

                if (array_key_exists($group_name, $file_break_down)) {
                    $file_break_down[$group_name]->size = $file_break_down[$group_name]->size + $file->filesize;
                } else {
                    $file_break_down[$group_name]->size = $file->filesize;
                }

                $file_break_down[$group_name]->count++;
            }

            //convert numerical byte value to readable string
            foreach ($file_break_down as &$record) {
                $record->size = display_size($record->size);
                //convert record to a readable string containing count and size information
                $record = $record->count . " files  <br/>" . $record->size;
            }

            //merge all file breakdowns into course information
            $course = (object) array_merge((array) $course, $file_break_down);

            //also update course size into readable format
            $course->course_size = display_size($course->course_size);
            
            //create link to shortname
            $course->course_title = html_writer::link(new moodle_url('/course/view.php', array('id' => $course->id)), $course->course_title, array('target' => '_blank'));
    
            //remove id we used originally to look up file information
            unset($course->id);
        }
  
        return $query_result;
    }

}
