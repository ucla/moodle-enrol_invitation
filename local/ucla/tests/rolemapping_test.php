<?php
/**
 * Unit tests for role mapping functions.
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
// Make sure the code being tested is accessible.
global $CFG;
require_once($CFG->dirroot . '/local/ucla/lib.php'); // Include the code to test

class rolemapping_test extends advanced_testcase {        
    private static $created_roles = array();
    
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
                    $this->assertEquals($role_entry->id, $result, 
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
                    
                    $this->assertEquals($default_result, $undefined_result);                    
                }
            }
        }
    }        
    
    /**
     * Make sure that get_pseudorole always returns editingteacher if passing in
     * anyone with a role code of 01
     * 
     * @dataProvider role_combo_provider
     */
    function test_get_pseudorole_instructor($role_combo) {
        $params[] = array('primary' => array('01'));
        $params[] = array('secondary' => array('01'));    
        $params[] = array('primary' => array('01'),
            'secondary' => array('01'));
        
        foreach ($params as $param) {
            $pseudorole = get_pseudorole($param, $role_combo); 
            $this->assertEquals('editingteacher', $pseudorole);
        }        
    }

    /**
     * Make sure that get_pseudorole always returns supervising_instructor if 
     * passing in anyone with a role code of 03
     * 
     * @dataProvider role_combo_provider
     */
    function test_get_pseudorole_supervising_instructor($role_combo) {
        $params[] = array('primary' => array('03'));
        $params[] = array('secondary' => array('03'));    
        $params[] = array('primary' => array('03'),
            'secondary' => array('03'));
        
        foreach ($params as $param) {
            $pseudorole = get_pseudorole($param, $role_combo); 
            $this->assertEquals('supervising_instructor', $pseudorole);
        }              
    }
    
    /**
     * Test get_pseudorole to see if the following conditions for the 02 role
     * work:
     *  - Anyone with 02 on a course with an 01 is a ta
     *  - If someone is an 02 in the primary section, and there is an 03, they 
     *    are a ta_instructor (assumes no 01, because of first condition)
     *  - All other 02 cases, default to ta
     * 
     * @dataProvider role_combo_provider
     */
    function test_get_pseudorole_ta($role_combo) {
        $params['primary'] = array('primary' => array('02'));
        $params['secondary'] = array('secondary' => array('02'));    
        $params['both'] = array('primary' => array('02'),
            'secondary' => array('02'));
        
        // Anyone with 02 on a course with an 01 is a ta
        if (in_array('01', $role_combo['primary']) || 
                in_array('01', $role_combo['secondary'])) {
            foreach ($params as $param) {
                $pseudorole = get_pseudorole($param, $role_combo); 
                $this->assertEquals('ta', $pseudorole);                
            }
            return; // exit out from further testing
        }
        
        // If someone is an 02 in the primary section, and there is an 03, they 
        //  are a ta_instructor (assumes no 01, because of first condition)
        if (in_array('03', $role_combo['primary']) || 
                in_array('03', $role_combo['secondary'])) {
            $pseudorole = get_pseudorole($params['primary'], $role_combo); 
            $this->assertEquals('ta_instructor', $pseudorole);    
            $pseudorole = get_pseudorole($params['secondary'], $role_combo); 
            $this->assertEquals('ta', $pseudorole);    
            $pseudorole = get_pseudorole($params['both'], $role_combo); 
            $this->assertEquals('ta_instructor', $pseudorole);                
            return; // exit out from further testing
        }        
        
        //All other 02 cases, default to ta
        foreach ($params as $param) {
            $pseudorole = get_pseudorole($param, $role_combo); 
            $this->assertEquals('ta', $pseudorole);
        }              
    }
    
    function test_get_student_pseudorole() {
        $should_return_waitlisted = array('W', 'H', 'P');
        foreach ($should_return_waitlisted as $code) {
            $result = get_student_pseudorole($code);
            $this->assertEquals('waitlisted', $result);
        }
        
        $should_return_student = array('E', 'A');
        foreach ($should_return_student as $code) {
            $result = get_student_pseudorole($code);
            $this->assertEquals('student', $result);
        }

        $should_return_false = array('D', 'C');
        foreach ($should_return_false as $code) {
            $result = get_student_pseudorole($code);
            $this->assertFalse($result);
        }
    }
    
//    /**
//     * Test the function role_mapping(). 
//     */
//    function test_role_mapping() {
//        // test course with student instructor
//        $expected = get_moodlerole('editingteacher');        
//        $actual = role_mapping('22', array('03'));        
//        $this->assertEquals($expected, $actual);
//    }
    
    /**
     * Add role used by UCLA
     */    
    protected function setUp() {
        global $DB;
        $roles[] = array('name' => 'Instructor',
                         'shortname' => 'editinginstructor');
        $roles[] = array('name' => 'Supervising Instructor',
                         'shortname' => 'supervising_instructor');
        $roles[] = array('name' => 'TA Instructor',
                         'shortname' => 'ta_instructor');
        $roles[] = array('name' => 'Teaching Assistant (admin)',
                         'shortname' => 'ta_admin');
        $roles[] = array('name' => 'Teaching Assistant',
                         'shortname' => 'ta');
        // student is already defined by default
//        $roles[] = array('name' => 'Student',
//                         'shortname' => 'student');

        foreach ($roles as $role) {
            $roleid = create_role($role['name'], $role['shortname'], '');
            static::$created_roles[$roleid] = $role;
        }
        
        // very important step to include if modifying db
        $this->resetAfterTest(true) ;
    }

    /**
     * Remove roles that were added by this test
     */
    protected function tearDown() {
        global $DB;
        foreach (static::$created_roles as $roleid => $role) {
            delete_role($roleid);
            unset(static::$created_roles[$roleid]);
            $DB->get_manager()->reset_sequence(new xmldb_table('role'));
        }
    }    
    
    /*********  HELPER FUNCTIONS FOR UNIT TESTING  ********/
    
    /**
     * Provides a multitude of role combinations for primary and secondary 
     * sections with alll possible mixes of 01, 02, and 03.
     */
    public function role_combo_provider() {
        $ret_val = array();
        
        $role_codes = array('01', '02', '03');
        
        // get all the role combos (also include empty sets
        $primary_role_combos = $this->powerSet($role_codes, 0);
        $secondary_role_combos = $primary_role_combos;
        
        $index = 0;
        foreach ($primary_role_combos as $primary) {            
            foreach ($secondary_role_combos as $secondary) {
                if (empty($primary) && empty($secondary)) continue;                                
                $ret_val[$index][0]['primary'] = $primary;
                $ret_val[$index][0]['secondary'] = $secondary;
                ++$index;
            }
        }
        
        return $ret_val;
    }

    /** 
     * Returns the power set of a one dimensional array, a 2-D array. 
     * [a,b,c] -> [ [a], [b], [c], [a, b], [a, c], [b, c], [a, b, c] ]
     * 
     * @source http://stackoverflow.com/a/6092999/6001
     */ 
    function powerSet($in, $minLength = 1) { 
        $count = count($in); 
        $members = pow(2, $count); 
        $return = array(); 
        for ($i = 0; $i < $members; $i++) { 
            $b = sprintf("%0".$count."b",$i); 
            $out = array(); 
            for ($j = 0; $j < $count; $j++) { 
                if ($b{$j} == '1') $out[] = $in[$j]; 
            } 
            if (count($out) >= $minLength) { 
                $return[] = $out; 
            } 
        } 
        return $return; 
    } 
}
