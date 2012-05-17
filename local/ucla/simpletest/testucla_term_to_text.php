<?php
/**
 * Unit tests for ucla_term_to_text.
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/local/ucla/lib.php'); // Include the code to test
 
class ucla_term_to_text_test extends UnitTestCase {
    function test_valid_inputs() {
        $result = ucla_term_to_text('11F');
        $this->assertEqual($result, 'Fall 2011');
        $result = ucla_term_to_text('09W');
        $this->assertEqual($result, 'Winter 2009');
        $result = ucla_term_to_text('13S');
        $this->assertEqual($result, 'Spring 2013');    
        $result = ucla_term_to_text('121');
        $this->assertEqual($result, 'Summer 2012');  
        // pass in session         
        $result = ucla_term_to_text('121', 'A');
        $this->assertEqual($result, 'Summer 2012 - Session A');  
    }
}

//EOF