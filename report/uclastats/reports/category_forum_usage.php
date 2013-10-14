<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Displays forum usage, broken down by month and role, for a given category.
 *
 * @package    report_uclastats
 * @copyright  UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/report/uclastats/locallib.php');

/**
 * Class definition.
 * 
 * @copyright  UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category_forum_usage extends uclastats_base {

    /**
     * Display category name, instead of categoryid.
     *
     * @param array $params
     * @return string
     */
    public function format_cached_params($params) {
        $paramlist = array();
        foreach ($params as $name => $value) {
            if ($name == 'category') {
                $displaylist = coursecat::make_categories_list('moodle/course:create');
                $paramlist[] = get_string('category') . ' = ' . $displaylist[$value];
                continue;
            }
            // Other parameters are timestamps.
            $paramlist[] = get_string($name, 'report_uclastats') . ' = ' .
                    date('M j, Y', $value);
        }
        return implode(', ', $paramlist);
    }

    /**
     * Display number of courses for category.
     *
     * @param array $results
     * @return string
     */
    public function format_cached_results($results) {
        if (isset($results['courselisting'])) {
            return count($results['courselisting']);
        }
        return '';
    }

    /**
     * Helper method to override uclastats_result's get_header() method.
     * 
     * @param uclastats_result $uclastats_result
     * @return array
     */
    private function get_header(uclastats_result $uclastats_result) {
        $results = $uclastats_result->results;
        $header = reset($results);

        // Only replace the first result element with get_string() value, for
        // everything else, use the array key.
        $header = array_keys($header);
        $header[0] = get_string('monthyear', 'report_uclastats');

        return $header;
    }

    /**
     * Abstract method to return parameters needed to run report.
     *
     * @return array
     */
    public function get_parameters() {
        return array('category', 'startendmonth');
    }

    /**
     * For role names, use what is in the result set, instead of looking up the
     * result via get_string().
     *
     * Also display category courses.
     *
     * @param uclastats_result $uclastatsresult
     * @return string
     */
    protected function get_results_table(uclastats_result $uclastatsresult) {
        $retval = '';
        
        $results = $uclastatsresult->results;
        $courselisting = $results['courselisting'];
        unset($results['courselisting']);

        // Category stats.

        $retval .= html_writer::tag('h3', get_string('categorylisting', 'report_uclastats'));

        $resultstable = new html_table();
        $resultstable->id = 'uclastats-results-table';
        $resultstable->attributes = array('class' => 'results-table ' .
            get_class($this));

        // Do not use uclastats_result's get_header() method.
        $resultstable->head = $this->get_header($uclastatsresult);
        $resultstable->data = $results;

        $retval .= html_writer::table($resultstable);

        // Course listing.
        $retval .= html_writer::tag('h3', get_string('courselisting', 'report_uclastats'));
        foreach ($courselisting as $coursename => $course) {
            $retval .= html_writer::tag('h4', $coursename);

            if (empty($course)) {
                $retval .= html_writer::tag('p',
                        get_string('noposts', 'report_uclastats'));
                continue;
            }

            $listingtable = new html_table();
            $listingtable->attributes = array('class' => 'results-table ' .
                get_class($this));

            $listingtable->head = $this->get_header($uclastatsresult);
            $listingtable->data = $course;

            $retval .= html_writer::table($listingtable);
            unset($listingtable);
        }

        return $retval;
    }

    /**
     * For role names, use what is in the result set, instead of looking up the
     * result via get_string().
     * 
     * Also display category courses.
     *
     * @param MoodleExcelWorksheet $worksheet
     * @param MoodleExcelFormat $boldformat
     * @param uclastats_result $uclastats_result
     * @param int $row      Row to start writing.
     *
     * @return int          Return row we stopped writing.
     */
    protected function get_results_xls(MoodleExcelWorksheet $worksheet,
            MoodleExcelFormat $boldformat, uclastats_result $uclastats_result, $row) {

        $results = $uclastats_result->results;
        $courselisting = $results['courselisting'];
        unset($results['courselisting']);

        // Display aggregated results.
        $col = 0;
        $worksheet->write_string($row, $col,
                get_string('categorylisting', 'report_uclastats'), $boldformat);
        ++$row;

        // Display table header.
        $header = $this->get_header($uclastats_result);
        foreach ($header as $name) {
            $worksheet->write_string($row, $col, $name, $boldformat);
            ++$col;
        }

        // Now go through result set.
        foreach ($results as $result) {
            ++$row; $col = 0;
            foreach ($result as $value) {
                // values might have HTML in them
                $value = clean_param($value, PARAM_NOTAGS);
                if (is_numeric($value)) {
                    $worksheet->write_number($row, $col, $value);
                } else {
                    $worksheet->write_string($row, $col, $value);
                }
                ++$col;
            }
        }

        $row += 2; $col = 0;
        $worksheet->write_string($row, $col,
                get_string('courselisting', 'report_uclastats'), $boldformat);

        // Display course listings.
        foreach ($courselisting as $coursename => $course) {
            $col = 0;
            $row += 2;
            $worksheet->write_string($row, $col,$coursename, $boldformat);

            if (empty($course)) {
                $worksheet->write_string($row, $col,
                        get_string('noposts', 'report_uclastats'), $boldformat);
                ++$row;
                continue;
            }

            // Display table header.
            $header = $this->get_header($uclastats_result);
            foreach ($header as $name) {
                $worksheet->write_string($row, $col, $name, $boldformat);
                ++$col;
            }

            foreach ($course as $result) {
                ++$row; $col = 0;
                foreach ($result as $value) {
                    // values might have HTML in them
                    $value = clean_param($value, PARAM_NOTAGS);
                    if (is_numeric($value)) {
                        $worksheet->write_number($row, $col, $value);
                    } else {
                        $worksheet->write_string($row, $col, $value);
                    }
                    ++$col;
                }
            }
        }

        return $row;
    }

    /**
     * Query for forum usage by month and role.
     *
     * @throws  moodle_exception
     *
     * @params array $params
     * @return array            Returns an array of results.
     */
    public function query($params) {
        global $DB;
        $retval = array();
        $courselisting = array();

        $sql = "SELECT  fp.id,
                        CONCAT(MONTHNAME(FROM_UNIXTIME(fp.created)), ' ', YEAR(FROM_UNIXTIME(fp.created))) AS monthyear,
                        r.name AS rolename,
                        CONCAT(c.shortname, ': ', c.fullname) AS coursename
                FROM    {course} c
                JOIN    {context} ct ON (ct.instanceid=c.id AND ct.contextlevel=:contextlevel)
                JOIN    {forum_discussions} fd ON (fd.course=c.id)
                JOIN    {forum_posts} fp ON (fp.discussion=fd.id)
                JOIN    {role_assignments} ra ON (fp.userid=ra.userid AND ra.contextid=ct.id)
                JOIN    {role} r ON (ra.roleid=r.id)
                WHERE   c.category=:categoryid AND
                        fp.created>=:startdate AND
                        fp.created<=:enddate
                ORDER BY    fp.created";
        $results = $DB->get_recordset_sql($sql,
                array('categoryid' => $params['category'],
                      'contextlevel' => CONTEXT_COURSE,
                      'startdate' => $params['startdate'],
                      'enddate' => $params['enddate']));

        if (!$results->valid()) {
            return $retval;
        }

        // Get all roles on system.
        $roles = $DB->get_fieldset_select('role', 'name', '1=1');
        sort($roles);
        $roles = array_map('trim', $roles);
        $usedroles = array();

        // Format results with rows by month/year and columns by role.
        foreach ($results as $result) {
            $rolename = trim($result->rolename);
            $coursename = trim($result->coursename);

            // Do stats for the category.
            if (!isset($retval[$result->monthyear])) {
                // Starting a new month, so set all roles.
                $retval[$result->monthyear]['monthyear'] = $result->monthyear;
                foreach ($roles as $role) {
                    $retval[$result->monthyear][$role] = 0;
                }
            }

            // Add up the totals for the roles.
            ++$retval[$result->monthyear][$rolename];
            $usedroles[$rolename] = true;

            // Do stats for each course.
            if (!isset($courselisting[$coursename])) {
                $courselisting[$coursename] = array();
            }
            if (!isset($courselisting[$coursename][$result->monthyear])) {
                // Starting a new month, so set all roles.
                $courselisting[$coursename][$result->monthyear]['monthyear'] = $result->monthyear;
                foreach ($roles as $role) {
                    $courselisting[$coursename][$result->monthyear][$role] = 0;
                }
            }

            // Add up the totals for the roles.
            ++$courselisting[$coursename][$result->monthyear][$rolename];
        }

        $results->close();

        // Now prune all roles that did not have any results.

        // Prune for category.
        foreach ($retval as &$result) {
            foreach ($result as $key => $value) {
                // Skip monthyear.
                if ($key == 'monthyear')    continue;
                
                // Result of $key should be role names, unset ones that had
                // no results.
                if (!array_key_exists($key, $usedroles)) {
                    unset($result[$key]);
                }
            }
        }

        // Prune for courses.
        foreach ($courselisting as $coursename => $course) {
            foreach ($course as $monthyear => $postings) {
                foreach ($postings as $key => $post) {
                    // Skip monthyear.
                    if ($key == 'monthyear')    continue;

                    // Result of $key should be role names, unset ones that had
                    // no results.
                    if (!array_key_exists($key, $usedroles)) {
                        unset($courselisting[$coursename][$monthyear][$key]);
                    }
                }
            }
        }
        ksort($courselisting);
        $retval['courselisting'] = $courselisting;

        return $retval;
    }

}
