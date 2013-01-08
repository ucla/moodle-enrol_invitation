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
require_once($CFG->dirroot . '/report/uclastats/locallib.php');

class uclastats_base_test extends advanced_testcase {

    /**
     * Private method to create fake instance of abstract class uclastats_base.
     *
     * @param array $results    An array of result arrays
     * @return object           Returns mock object to use in test.
     */
    private function createMockObject($results) {
        // user system admin as use
        $admin = get_admin();
        $stub = $this->getMockForAbstractClass('uclastats_base',
                array($admin->id), 'uclastats_base_mock');

        // stub abstract method to return what is expected
        $stub->expects($this->any())
             ->method('query')
             ->will($this->returnValue($results));

        return $stub;
    }

    /**
     * Provides test cases to use as parameters and results.
     *
     * @return array
     */
    public function providerTestCase() {
        /* Test cases:
         *  - 1 parameter, 1 result
         *  - 2 parameters, 2 results
         *  - empty parameter/result
         */
        $test_cases = array();
        // 1 parameter, 1 result
        $test_cases[0]['param'] = array('param1' => 1);
        $test_cases[0]['result'] = array(array('result1' => 1));
        // 2 parameters, 2 results
        $test_cases[1]['param'] = array('param1' => 1, 'param2' => 'test');
        $test_cases[1]['result'] = array(
            array('result1' => 1, 'result2' => 'test'),
            array('result1' => 2, 'result2' => 'something'));
        // empty parameter/result
        $test_cases[2]['param'] = array();
        $test_cases[2]['result'] = array();

        return $test_cases;
    }

    /**
     * Creates mockup of uclastats_base. Making abstract query method return
     * its parameters as a result.
     */
    public function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Test get_results without and without resultid.
     *
     * @dataProvider providerTestCase
     */
    public function testGetResults($param, $expected_result) {
        // run test to get results cached
        $stub = $this->createMockObject($expected_result);
        $resultid = $stub->run($param);

        // call get_results to get all results and cached result id
        $results = $stub->get_results();
        $this->assertEquals(1, count($results)); // should only have one result

        // get resultid of first element
        $result = $results->current();
        $pop_resultid = $result->id;
        $this->assertEquals($resultid, $pop_resultid);
        
        $result = $stub->get_results($resultid);
        $this->assertEquals($param, $result->params);
        $this->assertEquals($expected_result, $result->results);
    }

    /**
     * Test run and display_result to make sure that output is generated.
     *
     * @dataProvider providerTestCase
     */
    public function testRunAndDisplay($param, $expected_result) {
        // run test to get results cached
        $stub = $this->createMockObject($expected_result);
        $resultid = $stub->run($param);
        $html_output = $stub->display_result($resultid);

        // if there are no results, make sure message regarding so is printed
        if (empty($expected_result)) {
            $this->assertContains(get_string('noresults', 'admin'), $html_output);
        } else {
            // make sure that data was properly set by making sure that result
            // table is generated
            $this->assertContains('results-table uclastats_base_mock', $html_output);
        }
    }
}
