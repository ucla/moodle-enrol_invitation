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
    private $_stub = null;

    /**
     * Provides test cases to use as parameters and results.
     *
     * @return array
     */
    public function providerTestCase() {
        /* Test cases:
         *  - int
         *  - string
         *  - array
         *  - object
         *  - multi-dimensional array
         */
        $test_cases = array();
        $test_cases[0]['param'] = 1;  // int
        $test_cases[0]['result'] = 1;
        $test_cases[1]['param'] = 'test'; // string
        $test_cases[1]['result'] = 'test';
        $test_cases[2]['param'] = array(1, 2, '3');   // array
        $test_cases[2]['result'] = array(1, 2, '3');
        $object = new stdClass();
        $object->name = 'test';
        $object->value = $test_cases;
        $test_cases[3]['param'] = $object;  // object + multi-dimensional array
        $test_cases[3]['result'] = $object;     

        return $test_cases;
    }

    /**
     * Creates mockup of uclastats_base. Making abstract query method return
     * its parameters as a result.
     */
    public function setUp() {
        // user system admin as use
        $admin = get_admin();

        $this->_stub = $this->getMockForAbstractClass('uclastats_base',
                array($admin->id));
        $this->_stub->expects($this->any())
             ->method('query')
             ->will($this->returnArgument(0));

        $this->resetAfterTest(true);
    }

    /**
     * Test get_results without and without resultid.
     *
     * @dataProvider providerTestCase
     */
    public function testGetResults($param, $expected_result) {
        // run test to get results cached
        $this->_stub->run($param);

        // call get_results to get all results and cached result id
        $results = $this->_stub->get_results();
        $this->assertEquals(1, count($results)); // should only have one result

        // get resultid of first element
        $result = array_pop($results);
        $resultid = $result->id;
        $result = $this->_stub->get_results($resultid);
        $this->assertEquals($param, $result->params);
        $this->assertEquals($expected_result, $result->results);
    }

    /**
     * Tests the run method of the base class. Makes sure that results match
     * the parameter.
     *
     * @dataProvider providerTestCase
     */
    public function testRun($param, $expected_result) {
        $results = $this->_stub->run($param);
        $this->assertEquals($expected_result, $results);
    }


}
