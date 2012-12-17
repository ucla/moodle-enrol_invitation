<?php
/**
 * Unit tests for term related functions.
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

global $CFG;

// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/local/ucla/lib.php'); // Include the code to test

class term_test extends basic_testcase {
    // Added an optional parameter to sort descending (most recent first)
    function test_decreasing_sort() {
        // Test terms decreasing sort
        $test_cases[] = array(
            '11F',  // Newest term on record
            '111',
            '11S',
            '11W'
        );

        // Test mixed terms decreasing sort
        $test_cases[] = array(
            '12F',  // Newest term on record
            '121',
            '12S',
            '12W',
            '11F'
        );

        foreach ($test_cases as $ordered_list) {
            $tmp_list = $ordered_list;
            shuffle($tmp_list);

            // maybe once in a blue moon this will fail?
            $this->assertNotEquals($ordered_list, $tmp_list);

            $tmp_list = terms_arr_sort($tmp_list, true);
            $this->assertEquals($ordered_list, array_values($tmp_list));
        }
    }

    function test_fills() {
        // Argument
        $a = array(
            '11F',
            '12F',
            '13F'
        );

        // Final
        $f = array(
            '11F', '12W', '12S', '121',
            '12F', '13W', '13S', '131',
            '13F'
        );

        // Result
        $r = terms_range('11F', '13F');
        $this->assertEquals($r, $f);

        $r = terms_arr_fill($a);
        $this->assertEquals($r, $f);
    }

    function test_sorts() {
        // Test year sort
        $test_cases[] = array(
            '02F',
            '03F',
            '09F',
            '11F',
            '13F',
        );

        // Test terms sort
        $test_cases[] = array(
            '11W',
            '11S',
            '111',
            '11F',
        );

        // Test mixed sort
        $test_cases[] = array(
            '11W',
            '11S',
            '111',
            '11F',
            '12W',
            '12S',
            '121',
            '12F',
        );

        // Test pre-y2k terms
        $test_cases[] = array(
            '65F',  // oldest term on record at Registrar
            '81F',
            '99S',
            '00W',
            '641'
        );

        foreach ($test_cases as $ordered_list) {
            $tmp_list = $ordered_list;
            shuffle($tmp_list);

            // maybe once in a blue moon this will fail?
            $this->assertNotEquals($ordered_list, $tmp_list);

            $tmp_list = terms_arr_sort($tmp_list);
            $this->assertEquals($ordered_list, array_values($tmp_list));
        }
    }

    /**
     * Test function term_get_next
     */
    function test_term_get_next() {
        // input => expected output
        $test_cases = array('99F' => '00W', '13W' => '13S', '13S' => '131',
            '131' => '13F');

        foreach ($test_cases as $input => $expected_output) {
            $this->assertEquals($expected_output, term_get_next($input));
        }

        // also test null/invalid case
        $this->assertEquals(null, term_get_next(null));
        $this->assertEquals(null, term_get_next('123'));
    }

    /**
     * Test function term_get_prev
     */
    function test_term_get_prev() {
        // input => expected output
        $test_cases = array('00W' => '99F', '13S' => '13W', '131' => '13S',
            '13F' => '131');

        foreach ($test_cases as $input => $expected_output) {
            $this->assertEquals($expected_output, term_get_prev($input));
        }

        // also test null/invalid case
        $this->assertEquals(null, term_get_prev(null));
        $this->assertEquals(null, term_get_prev('123'));
    }

    function test_termrolefilter() {
        // Previous term, before cutoff week
        $r = term_role_can_view('11F', 'student', '12W', 1, 2);
        $this->assertTrue((bool)($r !== false));

        // Previous term, after cutoff week
        $r = term_role_can_view('11F', 'student', '12W', 3, 2);
        $this->assertTrue((bool)($r === false));

        // Future term, after cutoff week
        $r = term_role_can_view('121', 'student', '12W', 3, 2);
        $this->assertTrue((bool)($r !== false));

        // Previous term, after cutoff week, with powerful role
        $r = term_role_can_view('11F', 'editinginstructor', '12W', 3, 2);
        $this->assertTrue((bool)($r !== false));
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