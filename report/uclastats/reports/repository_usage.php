<?php

/**
 * Report to get total files added to a site that originated in a content repository by repository type
 *
 *
 * @package    report
 * @subpackage uclastats
 * @copyright  UC Regents
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class repository_usage extends uclastats_base {

    /**
     * Instead of counting results, return actual count.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {

        $sum = 0;

        if (!empty($results)) {

            foreach ($results as $record) {
                $sum += $record['repo_count'];
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
     * Query for getting total files added to a site that originated in a content repository by repository type
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        $repo_usage =
                array(
                    "Dropbox" => "LIKE '%dropbox%' OR source LIKE 'Dropbox%'",
                    "Google Docs" => "LIKE '%google%'",
                    "Box" => "LIKE '%box.net%'",
                    "Server Files" => "LIKE '%Server files%'",
                    "My CCLE files" => "LIKE '%My CCLE files%'"
        );

        $sql = "SELECT COUNT(*) as repo_count
                  FROM {files} 
                  WHERE source";

        $records = array();

        foreach ($repo_usage as $repo_name => $clause) {

            $record = $DB->get_records_sql($sql . " " . $clause, $params);

            //create new object such that ordering of attributes is Repository Name | File Count
            $repo = new stdClass();
            $repo->repo_name = $repo_name;
            $repo->repo_count = $record[0]->repo_count;
            $records[] = $repo;
        }

        return $records;
    }

}
