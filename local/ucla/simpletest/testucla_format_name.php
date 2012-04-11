<?php
/**
 * Unit tests for the ucla_format_name function in local/ucla/lib.php
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/local/ucla/lib.php'); // Include the code to test
 
class ucla_format_name_test extends UnitTestCase {
    
    function test_space() {
        $result = ucla_format_name('FIRST LAST');
        $this->assertTrue($result, 'First Last'); 
        // should also trim
        $result = ucla_format_name('AN N EA	');
        $this->assertEqual($result, 'An N Ea'); 
    }
    
    function test_hypen() {
        $result = ucla_format_name('FIRST-LAST');
        $this->assertEqual($result, 'First-Last'); 
    }
    
    function test_aprostrophe() {
        $result = ucla_format_name("FIRST'LAST");
        $this->assertEqual($result, "First'Last");    
    }
 
    function test_mc() {
        $result = ucla_format_name("OLD MCDONALD");
        $this->assertEqual($result, "Old McDonald");
        // should also trim
        $result = ucla_format_name("OLD   MCDONALD");
        $this->assertEqual($result, "Old McDonald"); 
    }    
    
    /**
     * Note, when a name has an ampersand it will have spaces around it. Else
     * the second word wouldn't be capitalized. This test case is when we need
     * to format divison/subject area long names.
     */
    function test_ampersand() {        
        $result = ucla_format_name("FIRST & LAST");
        $this->assertEqual($result, "First & Last"); 
        // should also trim
        $result = ucla_format_name("FIRST     &      LAST");
        $this->assertEqual($result, "First & Last");           
    }

    /**
     * Note, when a name has an slash it will have spaces around it. Else
     * the second word wouldn't be capitalized. This test case is when we need
     * to format divison/subject area long names.
     */    
    function test_slash() {
        // function should still have spaces around /
        $result = ucla_format_name("DESIGN / MEDIA ARTS");
        $this->assertEqual($result, "Design / Media Arts"); 
        // should also trim
        $result = ucla_format_name("DESIGN    /    MEDIA ARTS");
        $this->assertEqual($result, "Design / Media Arts");           
    }    
    
    /**
     * When formatting a name, if is something in the format of 
     * "WOMEN'S STUDIES", then the "S" after the apostrophe should not be 
     * capitalized.
     */
    function test_posessive_s() {
        $result = ucla_format_name("WOMEN'S STUDIES");
        $this->assertEqual($result, "Women's Studies");              
    }

    /**
     * Make sure that conjunctions are not capitlized, e.g. "and", "of", "the", 
     * "as", "a". Needed when formatting subject areas.
     */
    function test_conjunctions() {
        $result = ucla_format_name("Conservation Of Archaeological And Ethnographic Materials");
        $this->assertEqual($result, "Conservation of Archaeological and Ethnographic Materials");   

        $result = ucla_format_name("Indigenous Languages OF THE Americas");
        $this->assertEqual($result, "Indigenous Languages of the Americas");          
    }
    
    /**
     * Now test a complex string with every special case in it
     */
    function test_complex_string() {
        $result = ucla_format_name("MCMARY HAD A LITTLE-LAMB & IT'S FLEECE / WAS WHITE AS SNOW");
        $this->assertEqual($result, "McMary Had a Little-Lamb & It's Fleece / Was White as Snow");    
    }
}
?>
