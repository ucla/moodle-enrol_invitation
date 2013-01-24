<?php
/**
 * Unit tests for ucla_validator.
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
global $CFG;

// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/local/ucla/lib.php'); // Include the code to test
 
class ucla_validator_test extends basic_testcase {
    function test_valid_inputs() {
        $result = ucla_validator('term','11F');
        $this->assertEquals($result, true);
        $result = ucla_validator('term','11W');
        $this->assertEquals($result, true);         
        $result = ucla_validator('term','11S');
        $this->assertEquals($result, true); 
        $result = ucla_validator('term','111');
        $this->assertEquals($result, true);
        $result = ucla_validator('term','00S');
        $this->assertEquals($result, true);
        $result = ucla_validator('srs','111111111');
        $this->assertEquals($result, true);
        $result = ucla_validator('srs','000000000');
        $this->assertEquals($result, true);
        $result = ucla_validator('uid','000000000');
        $this->assertEquals($result, true);
        $result = ucla_validator('uid','123456789');
        $this->assertEquals($result, true);
    }
    
    function test_invalid_inputs() {
        $result = ucla_validator('srs','00000000');
        $this->assertEquals($result, false);
        $result = ucla_validator('srs','0000000011');
        $this->assertEquals($result, false);
        $result = ucla_validator('uid','00000000');
        $this->assertEquals($result, false);
        $result = ucla_validator('uid','0000000011');
        $this->assertEquals($result, false);
        $result = ucla_validator('term','1111');
        $this->assertEquals($result, false);
        $result = ucla_validator('term','110');
        $this->assertEquals($result, false);
        $result = ucla_validator('term','FF0');
        $this->assertEquals($result, false);
        $result = ucla_validator('term','1F0');
        $this->assertEquals($result, false);
        $result = ucla_validator('term','11FF');
        $this->assertEquals($result, false);
    }
    
    function test_exceptions() {
        try{
            $result = ucla_validator('ter','1F0');
            
        }
        catch (Exception $e){
            $this->assertEquals($e->getMessage(), 'ucla_validator/invalid type');
        }        
    }
 
}


//EOF