
<?php
/**
 * Unit tests for ucla_weeksdisplay.
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/blocks/ucla_weeksdisplay/block_ucla_weeksdisplay.php'); // Include the code to test
/*
 * Tests all functions in ucla weeksdisplay that do not modify the data
 */
class ucla_weeksdisplay_db_test extends UnitTestCaseUsingDatabase {

   function create_session_obj($term, $session, $session_start, $session_end, $instruction_start){
       $session[0] = $term;
       $session[3] = $session;
       $session[5] = $session_start;
       $session[6] = $session_end;
       $session[7] = $instruction_start;
   }     
    
    function test_init_currentterm(){
        global $DB;
        $this->switch_to_test_db(); // All operations until end of test method will happen in test DB
        $this->create_test_table('config_plugins', 'lib');         

        block_ucla_weeksdisplay::init_currentterm('2012-12-12');
        $result = get_config('local_ucla', 'current_term');
        $this->assertEqual($result, '12F');
        block_ucla_weeksdisplay::init_currentterm('2012-01-12');
        $result = get_config('local_ucla', 'current_term');
        $this->assertEqual($result, '12W');        
        block_ucla_weeksdisplay::init_currentterm('2012-05-12');
        $result = get_config('local_ucla', 'current_term');
        $this->assertEqual($result, '12S');        
        block_ucla_weeksdisplay::init_currentterm('2012-08-12');
        $result = get_config('local_ucla', 'current_term');
        $this->assertEqual($result, '121');               
    }

   function test_set_current_week_display(){
       
       set_current_week_display($current_term, $date);
   } 
 
}


//EOF
