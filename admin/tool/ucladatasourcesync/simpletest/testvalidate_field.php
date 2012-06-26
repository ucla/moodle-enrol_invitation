<?php
/**
 * Unit tests for validate_field.
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
// Make sure the code being tested is accessible.
require_once(dirname(__FILE__) . '/../lib.php'); // Include the code to test
 
class validate_field_test extends UnitTestCase {
    // date_slashed
    function test_valid_date_slashed() {
        $result = validate_field('date_slashed','06/03/2012');
        $this->assertEqual($result, true);
        $result = validate_field('date_slashed','02/29/2012');
        $this->assertEqual($result, true);
        
    }        
    function test_invalid_date_slashed() {
        $result = validate_field('date_slashed','06/40/2012');
        $this->assertFalse($result, true);
        $result = validate_field('date_slashed','02/29/2011');
        $this->assertFalse($result, true);        
    }     
    
    // date_dashed
    function test_valid_date_dashed() {
        $result = validate_field('date_dashed','2012-06-03');
        $this->assertEqual($result, true);
        $result = validate_field('date_dashed','2012-02-29');
        $this->assertEqual($result, true);
        
    }        
    function test_invalid_date_dashed() {
        $result = validate_field('date_dashed','2012-06-40');
        $this->assertFalse($result, true);
        $result = validate_field('date_dashed','2011-02-29');
        $this->assertFalse($result, true);        
    }     
}


//EOF