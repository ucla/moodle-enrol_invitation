<?php
/**
 * Unit tests for term functionality.
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/local/ucla/lib.php'); // Include the code to test
 
class ucla_term_fn_test extends UnitTestCase {
    function test_sorts() {
        // Test year sort
        $a = array(
            '11F',
            '02F',
            '03F',
            '09F',
            '13F'
        );

        $sorted = terms_arr_sort($a);
        $this->assertEqual($sorted, array(
            '02F', 
            '03F', 
            '09F', 
            '11F', 
            '13F'
        ));

        // Test terms sort
        $a = array(
            '11F',
            '11W',
            '111',
            '11S'
        );

        $sorted = terms_arr_sort($a);
        $this->assertEqual($sorted, array(
            '11W', 
            '11S', 
            '111', 
            '11F'
        ));

        // Test mixed sort
        $a = array(
            '12F',
            '12W',
            '121',
            '12S',
            '11F',
            '11W',
            '111',
            '11S'
        );

        shuffle($a);

        $sorted = terms_arr_sort($a);
        $this->assertEqual($sorted, array(
            '11W', 
            '11S', 
            '111', 
            '11F',
            '12W', 
            '12S', 
            '121', 
            '12F'
        ));
    }

    function test_validator() {
        try {
            $r = term_enum('3232');
        } catch (Exception $e) {
            $this->assertEqual($e->getMessage(), 'error/improperenum');
        }
        
        try {
            $r = term_enum('32K');
        } catch (Exception $e) {
            $this->assertEqual($e->getMessage(), 'error/improperenum');
        }
    }

}

//EOF
