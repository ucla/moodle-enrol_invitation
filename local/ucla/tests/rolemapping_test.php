<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for role mapping functions.
 *
 * @copyright 2013 UC Regents
 * @package   local_ucla
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();
 
// Make sure the code being tested is accessible.
global $CFG;
require_once($CFG->dirroot . '/local/ucla/lib.php');

class rolemapping_test extends advanced_testcase {
    /**
     * Mapping of role shortname to roleid.
     * @var array
     */
    private $createdroles = array();
    
    /**
     * Make sure that get_moodlerole is returning the appropiate data from 
     * local/ucla/rolemappings.php.
     */
    function test_get_moodlerole() {
        global $CFG, $DB;
        require($CFG->dirroot . '/local/ucla/rolemappings.php');
        
        foreach ($role as $pseudorole => $results) {
            foreach ($results as $subject_area => $moodle_role) {
                // Find the moodle role id for given moodle role.
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
                // Only test *SYSTEM* subject areas.
                if ($subject_area != '*SYSTEM*') {
                    continue;
                }
                
                // Find the moodle role id for given moodle role.
                $role_entry = $DB->get_record('role', array('shortname' => $moodle_role));                
                if (empty($role_entry)) {
                    $this->assertTrue(false, sprintf('No moodle role "%s" not found', $moodle_role));
                } else {
                    $default_result = get_moodlerole($pseudorole, $subject_area);    
                    // Now get result for a non-defined subject area.
                    $undefined_result = get_moodlerole($pseudorole, 'NON-EXISTENT SUBJECT AREA');                    
                    
                    $this->assertEquals($default_result, $undefined_result);                    
                }
            }
        }
    }        
    
    /**
     * Make sure that get_pseudorole always returns editingteacher if passing in
     * anyone with a role code of 01.
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
     * passing in anyone with a role code of 03.
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
        
        // Anyone with 02 on a course with an 01 is a ta.
        if (in_array('01', $role_combo['primary']) || 
                in_array('01', $role_combo['secondary'])) {
            foreach ($params as $param) {
                $pseudorole = get_pseudorole($param, $role_combo); 
                $this->assertEquals('ta', $pseudorole);                
            }
            return; // Exit out from further testing.
        }
        
        // If someone is an 02 in the primary section, and there is an 03, they 
        // are a ta_instructor (assumes no 01, because of first condition).
        if (in_array('03', $role_combo['primary']) || 
                in_array('03', $role_combo['secondary'])) {
            $pseudorole = get_pseudorole($params['primary'], $role_combo); 
            $this->assertEquals('ta_instructor', $pseudorole);    
            $pseudorole = get_pseudorole($params['secondary'], $role_combo); 
            $this->assertEquals('ta', $pseudorole);    
            $pseudorole = get_pseudorole($params['both'], $role_combo); 
            $this->assertEquals('ta_instructor', $pseudorole);                
            return; // Exit out from further testing.
        }        
        
        // All other 02 cases, default to ta.
        foreach ($params as $param) {
            $pseudorole = get_pseudorole($param, $role_combo); 
            $this->assertEquals('ta', $pseudorole);
        }              
    }
    
    /**
     * Make sure that get_pseudorole always returns student_instructor if
     * passing in anyone with a role code of 22.
     * 
     * @dataProvider role_combo_provider
     */
    function test_get_pseudorole_student_instructor($role_combo) {
        $params[] = array('primary' => array('22'));
        $params[] = array('secondary' => array('22'));    
        $params[] = array('primary' => array('22'),
            'secondary' => array('22'));
        
        foreach ($params as $param) {
            $pseudorole = get_pseudorole($param, $role_combo); 
            $this->assertEquals('student_instructor', $pseudorole);
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

    /**
     * Test role mapping with repeated calls to check if cache is working
     * properly.
     *
     * @group totest
     */
    public function test_role_mapping_cache() {
        $profcode = array('primary' => array('01'));
        $othercodes = array('secondary' => array('02'));
        $roleid = role_mapping($profcode, $othercodes);
        $this->assertEquals($this->createdroles['editinginstructor'], $roleid);
        $roleid = role_mapping($profcode, $othercodes);
        $this->assertEquals($this->createdroles['editinginstructor'], $roleid);
    }

    /**
     * Add roles used by UCLA.
     */    
    protected function setUp() {
        $uclagenerator = $this->getDataGenerator()->get_plugin_generator('local_ucla');
        $this->createdroles = $uclagenerator->create_ucla_roles();

        // Very important step to include if modifying db.
        $this->resetAfterTest(true) ;
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
