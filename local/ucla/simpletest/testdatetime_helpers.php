<?php
/**
 * Unit tests for datetimehelpers.php.
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/local/ucla/datetimehelpers.php'); // Include the code to test
 
class datetimehelpers_test extends UnitTestCase {
    /**
     * Test distance_of_time_in_words when using seconds
     */
    function test_distance_of_time_in_words_seconds() {
        $start_time = time();
        
        // less than 5 seconds
        $result = distance_of_time_in_words($start_time, $start_time + 4, true);
        $this->assertEqual($result, get_string('less_than_x_seconds', 'local_ucla', 5));        
        
        // less than 20 seconds
        $result = distance_of_time_in_words($start_time, $start_time + 19, true);
        $this->assertEqual($result, get_string('less_than_x_seconds', 'local_ucla', 20));        

        // exactly 1 minute
        $result = distance_of_time_in_words($start_time, $start_time + 60, true);
        $this->assertEqual($result, get_string('a_minute', 'local_ucla'));        
        $result = distance_of_time_in_words($start_time, $start_time + 60);
        $this->assertEqual($result, get_string('a_minute', 'local_ucla'));         
    }
    
    /**
     * Test distance_of_time_in_words minutes (without seconds)
     */
    function test_distance_of_time_in_words_minutes() {    
        $start_time = time();

        // less than a minute
        $result = distance_of_time_in_words($start_time, $start_time + 5);
        $this->assertEqual($result, get_string('less_minute', 'local_ucla'));         
        
        // a minute
        $result = distance_of_time_in_words($start_time, $start_time + 59);
        $this->assertEqual($result, get_string('a_minute', 'local_ucla'));         
        
        // x_minutes
        $result = distance_of_time_in_words($start_time, $start_time + 60*44);
        $this->assertEqual($result, get_string('x_minutes', 'local_ucla', 44));                 
    }
    
    /**
     * Test distance_of_time_in_words hours 
     */
    function test_distance_of_time_in_words_hours() {
        $start_time = time();
                
        // about_hour
        $result = distance_of_time_in_words($start_time, $start_time + 60*46);
        $this->assertEqual($result, get_string('about_hour', 'local_ucla')); 
        $result = distance_of_time_in_words($start_time, $start_time + 60*89);
        $this->assertEqual($result, get_string('about_hour', 'local_ucla'));                 
        
        // about_x_hour
        $result = distance_of_time_in_words($start_time, $start_time + 60*60*6);
        $this->assertEqual($result, get_string('about_x_hours', 'local_ucla', 6));                        
        $result = distance_of_time_in_words($start_time, $start_time + 60*60*23);
        $this->assertEqual($result, get_string('about_x_hours', 'local_ucla', 23));                                
    }

    /**
     * Test distance_of_time_in_words days 
     */
    function test_distance_of_time_in_words_days() {
        $start_time = time();
                
        // a_day (less than 2 days)
        $result = distance_of_time_in_words($start_time, $start_time + 60*60*24);
        $this->assertEqual($result, get_string('a_day', 'local_ucla')); 
        $result = distance_of_time_in_words($start_time, $start_time + 60*60*47);
        $this->assertEqual($result, get_string('a_day', 'local_ucla'));                 
        
        // x_days
        $result = distance_of_time_in_words($start_time, $start_time + 60*60*24*2);
        $this->assertEqual($result, get_string('x_days', 'local_ucla', 2));         
        $result = distance_of_time_in_words($start_time, $start_time + 60*60*24*30);
        $this->assertEqual($result, get_string('x_days', 'local_ucla', 30)); 
    }    
}
