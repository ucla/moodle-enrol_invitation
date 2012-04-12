
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
class ucla_weeksdisplay_db_test extends UnitTestCase {

    function test_placeholder(){
        
    }
   function create_session_obj($term, $session, $session_start, $session_end, $instruction_start){
       $session[0] = $term;
       $session[3] = $session;
       $session[5] = $session_start;
       $session[6] = $session_end;
       $session[7] = $instruction_start;
   } 
    
   function set_current_week_display($current_term){
        //Run the query and parse out the regular sessions.
        $query_result = registrar_query::run_registrar_query(
                'ucla_getterms', array($current_term));
        $regular_sessions = find_regular_sessions($query_result);
        
        //Compare the session start date with the system date
        $system_date = date('c');
        $is_date_in_sessions = 
                find_date_in_sessions($system_date,$regular_sessions);
        $return_string = NULL;
        //If the date is in a previous term
        if($is_date_in_sessions == -1) {
            
            $prev_term = get_prev_term($current_term);
            while(1){
                //error handling?
                $query_result = registrar_query::run_registrar_query(
                        'ucla_getterms', array($prev_term));

                $is_date_in_sessions = 
                        is_date_in_session($system_date,$query_result);
                if($is_date_in_sessions == 0){
                    $return_string =
                    get_current_week_display_string($system_date, $query_result);
                    break;
                } else if($is_date_in_sessions == 1) {
                    //If the date is between terms, return the string for the next term
                    $query_result = registrar_query::run_registrar_query(
                            'ucla_getterms', array(get_next_term($prev_term)));                      
                    $CFG->currentterm = get_next_term($prev_term);
                    return get_current_week_display_string($system_date, $query_result);
                    break;                            
                }
                
                $prev_term = get_prev_term($prev_term);
            }
            
        }  else if($is_date_in_sessions == 1) {
            //If the date is in a future term
            $next_term = get_next_term($current_term);
            while(1){
                //error checking for if registrar query reaches forever.
                $query_result = registrar_query::run_registrar_query(
                        'ucla_getterms', array($next_term));
               
                $is_date_in_sessions = 
                        is_date_in_session($system_date,$query_result);
                if($is_date_in_sessions >= 0){
                    $CFG->currentterm = $next_term;
                    return get_current_week_display_string($system_date, $query_result);   
                } 
                
                $next_term = get_next_term($next_term);
            }
            
        } else { //($is_date_in_session == 0) 
            return get_current_week_display_string($system_date, $query_result);
        }
    }
   
    function init_currentterm(){
        global $CFG;
        $date = date('c'); //returns string of format 2004-02-12T15:19:21+00:00
        $year = substr($date, 2, 2); 
        
        //Figure out what quarter it is based on the month.
        $month = intval(substr($date, 5, 2));
        if($month <= 0 || $month > 12) {
            debugging("Invalid system date month: ".$month);
        } else if($month <= 3){
            $CFG->currentterm = $year."W";
        } else if($month <= 6) {
            $CFG->currentterm = $year."S";   
        } else if($month <= 9) {
            $CFG->currentterm = $year."1";
        } else {//if($month <= 12) 
            $CFG->currentterm = $year."F";
        }        
    }
    
      
 
}


//EOF
