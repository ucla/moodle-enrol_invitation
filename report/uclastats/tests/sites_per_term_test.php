<?php
/**
 * Unit tests for UCLA stats console base class.
 *
 * @package    report
 * @category   uclastats
 * @copyright  UC Regents
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/report/uclastats/reports/sites_per_term.php');

class sites_per_term_test extends advanced_testcase {
    protected $courses;
    protected $reportname = 'sites_per_term';   // report to test

    /**
     * Creates a mix of cross-listed and non-crosslisted courses. Will index
     * courses made by term and then shortname.
     * 
     * @global object $DB
     */
    protected function createCourses() {
        global $DB;
        $course_to_create = array();
        unset($this->courses);
        // non-crosslisted
        $course_to_create['12S']['12S-MATH135-1'][] =
            array('srs' => '262508200', 'setid' => 1, 'department' => 'MATH',
                  'course' => '135-1', 'hostcourse' => 1, 'action' => 'built');
        // crosslisted
        $course_to_create['12S']['12S-NREASTM20-1'][] =
            array('srs' => '285061200', 'setid' => 2, 'department' => 'NR EAST',
                  'course' => 'M20-1', 'hostcourse' => 1, 'action' => 'built');
        $course_to_create['12S']['12S-NREASTM20-1'][] =
            array('srs' => '257060200', 'setid' => 2, 'department' => 'ASIAN',
                  'course' => 'M20-1', 'hostcourse' => 0, 'action' => 'built');
        $course_to_create['12S']['12S-NREASTM20-1'][] =
            array('srs' => '227060200', 'setid' => 2, 'department' => 'I E STD',
                  'course' => 'M20-1', 'hostcourse' => 0, 'action' => 'built');
        $course_to_create['12S']['12S-NREASTM20-1'][] =
            array('srs' => '271060200', 'setid' => 2, 'department' => 'SEASIAN',
                  'course' => 'M20-1', 'hostcourse' => 0, 'action' => 'built');
        $course_to_create['12S']['12S-NREASTM20-1'][] =
            array('srs' => '334060200', 'setid' => 2, 'department' => 'SLAVIC',
                  'course' => 'M20-1', 'hostcourse' => 0, 'action' => 'built');

        foreach ($course_to_create as $term => $courselist) {
            foreach ($courselist as $shortname => $courses) {
                // create shell course
                $created_course = $this->getDataGenerator()->create_course(
                        array('shortname' => $shortname));
                // create ucla_request_classes entries
                foreach ($courses as $course) {
                    $course['courseid'] = $created_course->id;
                    $course['term'] = $term;
                    $insertid = $DB->insert_record('ucla_request_classes', $course);
                    // save record so we know what was created for tests
                    $this->courses[$term][$shortname][] = $insertid;
                }
            }
        }
    }

    /**
     * Setups report object to test with.
     */
    protected function createReport() {
        // user system admin as use
        $admin = get_admin();
        return new $this->reportname($admin);
    }

    /**
     * Creates mockup of uclastats_base. Making abstract query method return
     * its parameters as a result.
     */
    public function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Test get_results without resultid to make sure results are saved.
     */
    public function testGetResults() {
        $report = $this->createReport();
        $this->createCourses();

        // run test for different term. Make sure that results are stored for
        // each run
        $report->run(array('term' => '12S'));
        $report->run(array('term' => '12F'));

        // call get_results to get all results
        $results = $report->get_results();
        $this->assertEquals(2, $results->count());

        $report->run(array('term' => '12S'));
        // call get_results to get all results
        $results = $report->get_results();
        $this->assertEquals(3, $results->count());
    }

    /**
     * Test query to make sure it is returning the expected results
     */
    public function testQuery() {
        $report = $this->createReport();
        $this->createCourses();

        foreach ($this->courses as $term => $courselist) {
            $resultid = $report->run(array('term' => $term));
            $result = $report->get_results($resultid);
            $results_array = $result->results;
            $site_count = reset($results_array);
            $this->assertEquals(count($courselist), $site_count['site_count']);
        }
    }
}
