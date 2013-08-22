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
 * Unit tests for UCLA stats sites_per_term class.
 *
 * @package    report
 * @category   uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/report/uclastats/reports/sites_per_term.php');

class sites_per_term_test extends advanced_testcase {
    /**
     * Used to store the courses that were created.
     * @var array
     */
    protected $courses = array();

    /**
     * Report object.
     * @var sites_per_term
     */
    protected $report = null;

    /**
     * Creates a mix of cross-listed and non-crosslisted courses.
     */
    protected function create_courses() {
        unset($this->courses);
        
        // Non-crosslisted course.
        $param = array('term' => '12F', 'srs' => '262508200',
                       'subj_area' => 'MATH', 'crsidx' => '0135    ',
                       'secidx' => ' 001  ', 'division' => 'PS');
        $this->courses['12F'][] = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class($param);

        // Crosslisted course.
        $param = array(
            array('term' => '12S', 'srs' => '285061200',
                'subj_area' => 'NR EAST', 'crsidx' => '0020  M ',
                'secidx' => ' 001  ', 'division' => 'HU'),
            array('term' => '12S', 'srs' => '257060200',
                'subj_area' => 'ASIAN', 'crsidx' => '0020  M ',
                'secidx' => ' 001  ', 'division' => 'HU'),
            array('term' => '12S', 'srs' => '334060200',
                'subj_area' => 'SLAVIC', 'crsidx' => '0020  M ',
                'secidx' => ' 001  ', 'division' => 'HU'));

        $this->courses['12S'][] = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class($param);
    }

    /**
     * Creates test courses.
     */
    public function setUp() {
        $this->resetAfterTest(true);
        $this->create_courses();
        $this->report = new sites_per_term(get_admin());
    }

    /**
     * Test get_results without resultid to make sure results are saved.
     */
    public function test_get_results() {
        // Run test for different term. Make sure that results are stored for
        // each run.
        $this->report->run(array('term' => '12S'));
        $this->report->run(array('term' => '12F'));
        $results = $this->report->get_results();
        $this->assertEquals(2, $results->count());

        $this->report->run(array('term' => '12S'));
        $results = $this->report->get_results();
        $this->assertEquals(3, $results->count());
    }

    /**
     * Test query to make sure it is returning the expected results.
     */
    public function test_query() {
        foreach ($this->courses as $term => $courselist) {
            $resultid = $this->report->run(array('term' => $term));
            $result = $this->report->get_results($resultid);
            $resultsarray = $result->results;
            $sitecount = reset($resultsarray);
            
            $this->assertEquals(count($courselist), $sitecount['site_count']);
        }
    }
}
