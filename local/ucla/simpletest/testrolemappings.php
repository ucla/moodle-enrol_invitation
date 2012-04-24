<?php
/**
 * Unit tests for role mapping functions.
 * 
 * @todo Write database tests for 'get_moodlerole'
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/local/ucla/lib.php'); // Include the code to test

class rolemappings_test extends UnitTestCase {    

    /**
     * Make sure that get_moodlerole is returning the appropiate data from 
     * local/ucla/rolemappings.php.
     */
    function test_get_moodlerole() {
        global $CFG, $DB;
        require($CFG->dirroot . '/local/ucla/rolemappings.php');
        
        foreach ($role as $pseudorole => $results) {
            foreach ($results as $subject_area => $moodle_role) {
                // find the moodle role id for given moodle role
                $role_entry = $DB->get_record('role', array('shortname' => $moodle_role));                
                if (empty($role_entry)) {
                    $this->assertTrue(false, sprintf('No moodle role "%s" not found', $moodle_role));
                } else {
                    $result = get_moodlerole($pseudorole, $subject_area);                   
                    $this->assertEqual($role_entry->id, $result, 
                            sprintf('Failed for pseudorole: %s|subject_area: %s|moodle_role:%s. Expecting: %d. Actual: %d', 
                                $pseudorole, $subject_area, $moodle_role,
                                $role_entry->id, $result));                    
                }
            }
        }
    }
    
    /**
     * Call get_moodlerole with a subject area not defined in the config file
     * to make sure that it returns the default value.
     */
    function test_get_moodlerole_with_default() {
        global $CFG, $DB;
        require($CFG->dirroot . '/local/ucla/rolemappings.php');
        
        foreach ($role as $pseudorole => $results) {
            foreach ($results as $subject_area => $moodle_role) {
                // only test *SYSTEM* subject areas
                if ($subject_area != '*SYSTEM*') {
                    continue;
                }
                
                // find the moodle role id for given moodle role
                $role_entry = $DB->get_record('role', array('shortname' => $moodle_role));                
                if (empty($role_entry)) {
                    $this->assertTrue(false, sprintf('No moodle role "%s" not found', $moodle_role));
                } else {
                    $default_result = get_moodlerole($pseudorole, $subject_area);    
                    // now get result for a non-defined subject area
                    $undefined_result = get_moodlerole($pseudorole, 'NON-EXISTENT SUBJECT AREA');                    
                    
                    $this->assertEqual($default_result, $undefined_result);                    
                }
            }
        }
    }    
    
    /**
     * For a given specific profcode and a set of profcodes, the function should 
     * return the given psudo role.
     * 
     * role InstSet     Pseudo Role
     * 01   any         instructor
     * 02   01,02       ta
     * 02   01,02,03    ta
     * 02   02,03       ta_instructor
     * 03   any	        supervising_instructor
     * 22   any	        editinginstructor 
     */
    function test_get_pseudorole() {
        // testing: 01   any         instructor
        $result = get_pseudorole('01', array());
        $this->assertEqual($result, 'editingteacher');
        $result = get_pseudorole('01', array('01'));
        $this->assertEqual($result, 'editingteacher');        
        $result = get_pseudorole('01', array('01','02'));
        $this->assertEqual($result, 'editingteacher');        
        $result = get_pseudorole('01', array('01','02','03'));
        $this->assertEqual($result, 'editingteacher');       
        $result = get_pseudorole('01', array('01', '03'));
        $this->assertEqual($result, 'editingteacher');     
        $result = get_pseudorole('01', array('02'));
        $this->assertEqual($result, 'editingteacher');                
        $result = get_pseudorole('01', array('02','03'));
        $this->assertEqual($result, 'editingteacher');      
        $result = get_pseudorole('01', array('03'));
        $this->assertEqual($result, 'editingteacher');        

        /* testing:
         * 02   01,02       ta
         * 02   01,02,03    ta
         * 02   02,03       ta_instructor
         */
        $result = get_pseudorole('02', array());
        $this->assertEqual($result, 'ta');
        $result = get_pseudorole('02', array('01'));
        $this->assertEqual($result, 'ta');        
        $result = get_pseudorole('02', array('01','02'));
        $this->assertEqual($result, 'ta');        
        $result = get_pseudorole('02', array('01','02','03'));
        $this->assertEqual($result, 'ta');      
        $result = get_pseudorole('02', array('01', '03'));
        $this->assertEqual($result, 'ta');      
        $result = get_pseudorole('02', array('02'));
        $this->assertEqual($result, 'ta');                
        $result = get_pseudorole('02', array('02','03'));
        $this->assertEqual($result, 'ta_instructor');      
        $result = get_pseudorole('02', array('03'));
        $this->assertEqual($result, 'ta_instructor');          

        // testing: 03   any	        supervising_instructor
        $result = get_pseudorole('03', array());
        $this->assertEqual($result, 'supervising_instructor');
        $result = get_pseudorole('03', array('01'));
        $this->assertEqual($result, 'supervising_instructor');        
        $result = get_pseudorole('03', array('01','02'));
        $this->assertEqual($result, 'supervising_instructor');        
        $result = get_pseudorole('03', array('01','02','03'));
        $this->assertEqual($result, 'supervising_instructor');         
        $result = get_pseudorole('03', array('01','03'));
        $this->assertEqual($result, 'supervising_instructor');    
        $result = get_pseudorole('03', array('02'));
        $this->assertEqual($result, 'supervising_instructor');                
        $result = get_pseudorole('03', array('02','03'));
        $this->assertEqual($result, 'supervising_instructor');      
        $result = get_pseudorole('03', array('03'));
        $this->assertEqual($result, 'supervising_instructor');          

         // testing: 22   any	        editingteacher 
        $result = get_pseudorole('22', array());
        $this->assertEqual($result, 'editingteacher');
        $result = get_pseudorole('22', array('01'));
        $this->assertEqual($result, 'editingteacher');        
        $result = get_pseudorole('22', array('01','02'));
        $this->assertEqual($result, 'editingteacher');        
        $result = get_pseudorole('22', array('01','02','03'));
        $this->assertEqual($result, 'editingteacher');      
        $result = get_pseudorole('22', array('01','03'));
        $this->assertEqual($result, 'editingteacher');      
        $result = get_pseudorole('22', array('02'));
        $this->assertEqual($result, 'editingteacher');                
        $result = get_pseudorole('22', array('02','03'));
        $this->assertEqual($result, 'editingteacher');      
        $result = get_pseudorole('22', array('03'));
        $this->assertEqual($result, 'editingteacher');                 
    }
    
    function test_get_student_pseudorole() {
        $should_return_waitlisted = array('W', 'H', 'P');
        foreach ($should_return_waitlisted as $code) {
            $result = get_student_pseudorole($code);
            $this->assertEqual($result, 'waitlisted');
        }
        
        $should_return_student = array('E', 'A');
        foreach ($should_return_student as $code) {
            $result = get_student_pseudorole($code);
            $this->assertEqual($result, 'student');
        }

        $should_return_false = array('D', 'C');
        foreach ($should_return_false as $code) {
            $result = get_student_pseudorole($code);
            $this->assertFalse($result);
        }
    }
    
    /**
     * Test the function role_mapping(). 
     */
    function test_role_mapping() {
        // test course with student instructor
        $expected = get_moodlerole('editingteacher');        
        $actual = role_mapping('22', array('03'));        
        $this->assertEqual($expected, $actual);
    }
}
?>
