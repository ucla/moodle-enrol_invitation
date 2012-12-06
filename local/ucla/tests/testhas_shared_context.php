<?php
/**
 * Unit tests for has_shared_context.
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
global $CFG;
// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/local/ucla/lib.php'); // Include the code to test

class has_shared_context_test extends advanced_testcase {
    public $role_assignments_columns = array('userid', 'contextid');    
    
    /**
     * Test when two users do share a single context 
     */
    function test_single_shared_context() {
        $data['role_assignments'][] = $this->role_assignments_columns; 
        $data['role_assignments'][] =  array(1, 1);
        $data['role_assignments'][] =  array(1, 2);
        $data['role_assignments'][] =  array(2, 2);
        $data['role_assignments'][] =  array(3, 2);
        //$this->load_test_data('role_assignments', 
        //        $this->role_assignments_columns, $data);
        $dataset = $this->createArrayDataSet($data);
        $this->loadDataSet($dataset); 
        
        $result = has_shared_context(1, 2);
        $this->assertTrue((bool)$result);    
        
    }

    /**
     * Test when two users do share multiple contexts 
     */
    function test_multiple_shared_context() {
        $data['role_assignments'][] = $this->role_assignments_columns; 
        $data['role_assignments'][] =  array(1, 1);
        $data['role_assignments'][] =  array(1, 2);
        $data['role_assignments'][] =  array(1, 3);
        $data['role_assignments'][] =  array(2, 1);
        $data['role_assignments'][] =  array(2, 2);
        $data['role_assignments'][] =  array(2, 4);
        
        $dataset = $this->createArrayDataSet($data);
        $this->loadDataSet($dataset);    
        $result = has_shared_context(1, 2);
        $this->assertTrue((bool)$result);        
    }    
    
    /**
     * Test when two users do not share a context 
     */
    function test_no_shared_context() {
        $data['role_assignments'][] = $this->role_assignments_columns; 
        $data['role_assignments'][] =  array(1, 1);
        $data['role_assignments'][] =  array(1, 2);
        $data['role_assignments'][] =  array(2, 3);
        
        $dataset = $this->createArrayDataSet($data);
        $this->loadDataSet($dataset);    
        $result = has_shared_context(1, 2);
        $this->assertFalse((bool)$result);
    }    
    
    protected function setUp() {
        $this->resetAfterTest(true);
    } 
}