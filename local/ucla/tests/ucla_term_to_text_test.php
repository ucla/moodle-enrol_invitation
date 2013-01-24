<?php
/**
 * Unit tests for ucla_term_to_text.
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
global $CFG;

// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/local/ucla/lib.php'); // Include the code to test
 
class ucla_term_to_text_test extends basic_testcase {
    function test_valid_inputs() {
        $result = ucla_term_to_text('11F');
        $this->assertEquals($result, 'Fall 2011');
        $result = ucla_term_to_text('09W');
        $this->assertEquals($result, 'Winter 2009');
        $result = ucla_term_to_text('13S');
        $this->assertEquals($result, 'Spring 2013');    
        $result = ucla_term_to_text('121');
        $this->assertEquals($result, 'Summer 2012');  
        // pass in session         
        $result = ucla_term_to_text('121', 'A');
        $this->assertEquals($result, 'Summer 2012 - Session A');  
    }
}

//EOF