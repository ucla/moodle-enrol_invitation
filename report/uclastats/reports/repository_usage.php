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
        return array('term');
    }

    /**
     * Query for getting total files added to a site that originated in a content repository by repository type
     *
     * @param array $params
     * @param return array
     */
    public function query($params) {
        global $DB;

        // Get start and end dates for term.
        $terminfo = $this->get_term_info($params['term']);

        $repo_usage =
                array(
                    "Dropbox" => "(source LIKE '%dropbox%' OR source LIKE 'Dropbox%')",
                    "Google Docs" => "(source LIKE '%google%')",
                    "Box" => "(source LIKE 'Box %')",
                    "Server Files" => "(source LIKE '%Server files%')",
                    "My CCLE files" => "(source LIKE '%CCLE files%')"
        );

        $sql = "SELECT  COUNT(*) as repocount
                FROM    {files}
                WHERE   timecreated >= :start AND
                        timecreated <= :end AND
                        ";

        $records = array();

        foreach ($repo_usage as $repo_name => $clause) {
            $repocount = $DB->get_field_sql($sql . $clause,
                    array('start' => $terminfo['start'],
                        'end' => $terminfo['end']));

            // Create new object such that ordering of attributes is
            // Repository Name | File Count.
            $repo = new stdClass();
            $repo->repo_name = $repo_name;

            $repo->repo_count = $repocount;
            $records[] = $repo;
        }

        return $records;
    }

}
