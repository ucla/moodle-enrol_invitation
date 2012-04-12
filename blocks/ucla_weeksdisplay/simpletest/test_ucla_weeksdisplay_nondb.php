
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
class ucla_weeksdisplay_nondb_test extends UnitTestCase {

   function create_session_obj($term, $session, $session_start, $session_end, $instruction_start){
       $session[0] = $term;
       $session[3] = $session;
       $session[5] = $session_start;
       $session[6] = $session_end;
       $session[7] = $instruction_start;
   } 
   
   /**
    * Returns the current_week_display string associated with the date and sessions.
    * 
    * @param date string that starts with the format YYYY-MM-DD that has to be 
    * either within the session start/end dates. 
    * Exception: A date before the sessions startdate 
    * and after the previous session's end date can be passed into this function
    * as well, in which case it will just display <Quarter> <Year>.
    * @param sessions an array of session objects 
    *       returned by ucla_getterms registrar query.
    *       The only sessions that will be parsed are the 'RG', '8A', '6C' ones.
    *       This function assumes that there will be either only one session
    *       in the array, or a 8A session followed by a 6C session.
    * @return the current_week_display string with format:
    * <Quarter> <Year> - Week <Week number> on a normal week.
    * <Quarter> <Year> - Finals Week for week 11.
    * <Quarter> <Year> - Week 0 if instruction_start > session_start
    * <Quarter> <Year> for all other days that don't fit the stuff above.
    * Summer <Year> - Session A, Week <Week number>
    * Summer <Year> - Session A, Week <Week number> / Session C, Week <Week number>
    * Summer <Year> - Session C, Week <Week number> 
    * for the various summer sessions.
    */      
    function get_current_week_display_string($date, $sessions){
        
        //Ensure that non RG/8A/6C functions got in.
        $regular_sessions = find_regular_sessions($sessions);          
        usort($regular_sessions, 'cmp_sessions');
        
        //Handles special case where sessions overlap.
        if($regular_sessions[0][3] == '8A' && $regular_sessions[1][3] == '6C') {
            $week_number0 = get_week($date, $regular_sessions[0]);          
            $week_number1 = get_week($date, $regular_sessions[1]);     
                return ucla_term_to_text($session[0], 'A').', Week '
                       . $week_number0 . '/ Session C, Week ' . $week_number1;                                  
        } else {
            $week_number = get_week($date, $regular_sessions[0]);
            
            if($week_number == 11){       
                return ucla_term_to_text($regular_sessions[0]).' - Finals Week';                      
            }            
            if($week_number >= 0){       
                return ucla_term_to_text($regular_sessions[0]).' - Week '
                        . $week_number;                      
            } else { //If the date is before this term's start date.
                return ucla_term_to_text($regular_sessions[0]);
            }
        }  
    }


    function get_week($date, $session){
        $ses_start_date = $session[5];
        $ses_end_date = $session[6];
        
        $unix_date = strtotime($date);
        $unix_ses_start_date = strtotime($session[5]);
        
        $date_vs_ses_start = find_earlier_date($date, $ses_start_date);
        $date_vs_ses_end = find_earlier_date($date, $ses_end_date);       
        $date_vs_instr_start 
            = find_earlier_date($date, $instruction_start_date);
         
        //If the date is in Week 0.
        if($date_vs_ses_start >= 0 && $date_vs_instr_start < 0) {
            return 0;
        } else if($date_vs_instr_start >= 0 && $date_vs_ses_end <= 0) {
            // If the date is in Week 1 - Finals Week    
            //TODO: Summer sessions: ses start == instr start?
            
            //Week 1 always starts the first monday after session start date.
            //TODO: overflow stuff, documentation
            $monday_of_first_week = date('z', $unix_ses_start_date);
            // <editor-fold defaultstate='collapsed' desc='Find monday of first week'>
            switch (get_dayofweek($ses_start_date)) {
                case 'Mon':
                    $first_day_of_first_week += 7;
                    break;
                case 'Tue':
                    $first_day_of_first_week += 6;
                    break;
                case 'Wed':
                    $first_day_of_first_week += 5;
                    break;
                case 'Thu':
                    $first_day_of_first_week += 4;
                    break;
                case 'Fri':
                    $first_day_of_first_week += 3;
                    break;
                case 'Sat':
                    $first_day_of_first_week += 2;
                    break;
                case 'Sun':
                    $first_day_of_first_week += 1;
                    break;
            }// </editor-fold>
            
            //Find the number of weeks elapsed from the first day to the current day.
            return ((date('z', $unix_date) - $monday_of_first_week) % 7) + 1;                    
        } else {
            return -1;
        }                
    }
    
   /**
    * Returns whether or not the date is within the dession.
    * @param date string that starts with the format YYYY-MM-DD
    * @param sessions an array of session objects returned by ucla_getterms
    * registrar query
    * @return 1 if date comes after all session's session_end date.
    *         an array of sessions that the date is within
    *            if date is between the instruction start and session end dates.
    *           if 
    *         -1 if date1 comes before all session's instruction start date.
    */  
    function find_date_in_sessions($date, $sessions){
        $regular_sessions = find_regular_sessions($sessions);
        //Sort the sessions from earliest to latest.
        usort($regular_sessions, 'cmp_sessions');
        
        $return_sessions = NULL;
        
        for($i = 0; $i < count($regular_sessions); $i++) {
            $session = $regular_sessions[i];
            
            $session_start_date = $session[5];
            $session_end_date = $session[6];

            $date_vs_start = find_earlier_date($date, $session_start_date);
            $date_vs_end = find_earlier_date($date, $session_end_date);

            if($date_vs_start <= -1 && $i == 0) {
                //If the date comes before the start of the earliest session
                return -1;
            } else if($date_vs_start <= -1) {
                //If the date comes before the start of a session (this implicitly
                //also means the date comes after the end of the session before this)
                $return_sessions[] = $sessions[$i-1];
                break;
            } else if($date_vs_start >= 0 && $date_vs_end <= 0) {
                //If the date comes after start of session and before end of session     
                $return_sessions[] = $sessions[$i];
            } else if($date_vs_end == 1 && $i == count($regular_sessions) - 1){
                //If the date comes after the end of the last session
                return 1;
            }
        }
        
        return $return_sessions;
    }


    function test_find_regular_sessions(){  
        $sessions[] = $this->create_session_obj('10W','RG','2012-01-01','2012-02-01','2012-01-01');
        $sessions[] = $this->create_session_obj('10W','6A','2013-01-01','2013-02-01','2013-01-01');
        $sessions[] = $this->create_session_obj('10W','8A','2013-01-01','2013-02-01','2013-01-01');
        $sessions[] = $this->create_session_obj('10W','6C','2013-01-01','2013-02-01','2013-01-01');
        $sessions[] = $this->create_session_obj('10W','99A','2013-01-01','2013-02-01','2013-01-01');
        
        $result = block_ucla_weeksdisplay::find_regular_sessions($sessions);
        
        $answer[] = $this->create_session_obj('10W','RG','2012-01-01','2012-02-01','2012-01-01');
        $sessions[] = $this->create_session_obj('10W','8A','2013-01-01','2013-02-01','2013-01-01');
        $sessions[] = $this->create_session_obj('10W','6C','2013-01-01','2013-02-01','2013-01-01');
        $result = block_ucla_weeksdisplay::find_regular_sessions($sessions);
        $this->assertEqual($result, $answer);        
        
    }          
    
    function test_get_next_term(){
        $result = block_ucla_weeksdisplay::get_next_term('11F');
        $this->assertEqual($result, '11W');           
        $result = block_ucla_weeksdisplay::get_next_term('99W');
        $this->assertEqual($result, '00S'); 
        $result = block_ucla_weeksdisplay::get_next_term('11W');
        $this->assertEqual($result, '12S');  
        $result = block_ucla_weeksdisplay::get_next_term('111');
        $this->assertEqual($result, '11F');  
        $result = block_ucla_weeksdisplay::get_next_term('11S');
        $this->assertEqual($result, '111');  
    } 
    
    function test_get_prev_term(){
        $result = block_ucla_weeksdisplay::get_prev_term('11F');
        $this->assertEqual($result, '111');           
        $result = block_ucla_weeksdisplay::get_prev_term('00S');
        $this->assertEqual($result, '99W'); 
        $result = block_ucla_weeksdisplay::get_prev_term('11W');
        $this->assertEqual($result, '11F');  
        $result = block_ucla_weeksdisplay::get_prev_term('111');
        $this->assertEqual($result, '11S');  
        $result = block_ucla_weeksdisplay::get_prev_term('11S');
        $this->assertEqual($result, '10W');  
    }
      
    //Since this function just uses test_cmp_dates, it won't be as rigorously
    //tested.
    function test_cmp_sessions(){   
        
        //$term, $session, $session_start, $session_end, $instruction_start
        $session1 = $this->create_session_obj('10W','RG','2012-01-01','2012-02-01','2012-01-01');
        $session2 = $this->create_session_obj('10W','RG','2013-01-01','2013-02-01','2013-01-01');
        $result = block_ucla_weeksdisplay::cmp_sessions($session1, $session2);
        $this->assertEqual($result, -1);
        $session1 = $this->create_session_obj('10W','RG','2012-01-01','2012-02-01','2012-01-01');
        $session2 = $this->create_session_obj('10W','RG','2012-01-01','2012-02-01','2012-01-01');
        $result = block_ucla_weeksdisplay::cmp_sessions($session1, $session2);
        $this->assertEqual($result, 0);        
        $session1 = $this->create_session_obj('10W','RG','2012-01-01','2012-02-01','2012-01-01');
        $session2 = $this->create_session_obj('10W','RG','2012-01-01','2012-02-01','2012-01-01');
        $result = block_ucla_weeksdisplay::cmp_sessions($session1, $session2);
        $this->assertEqual($result, 1);                
    }

    function test_cmp_dates(){   
        $result = block_ucla_weeksdisplay::cmp_dates('2012-04-09', '2012-04-09');
        $this->assertEqual($result, 0);
     //Test Dates where date1 comes before date2
        //Test Dates within the same year and month.
        $result = block_ucla_weeksdisplay::cmp_dates('2012-04-09', '2012-04-10');
        $this->assertEqual($result, -1);   
        $result = block_ucla_weeksdisplay::cmp_dates('2012-04-01', '2012-04-10');
        $this->assertEqual($result, -9);   
        //Test Dates within the same year
        $result = block_ucla_weeksdisplay::cmp_dates('2012-03-01', '2012-04-01');
        $this->assertEqual($result, -31);   
        $result = block_ucla_weeksdisplay::cmp_dates('2012-01-01', '2012-12-31');
        $this->assertEqual($result, -365);      
        $result = block_ucla_weeksdisplay::cmp_dates('2013-01-01', '2013-12-31');
        $this->assertEqual($result, -364);            
        //Test dates from different years.
        $result = block_ucla_weeksdisplay::cmp_dates('2012-12-31', '2013-01-01');
        $this->assertEqual($result, -1);      
        $result = block_ucla_weeksdisplay::cmp_dates('2012-12-31', '2014-01-01');
        $this->assertEqual($result, -366);  
        $result = block_ucla_weeksdisplay::cmp_dates('2012-12-31', '2015-01-01');
        $this->assertEqual($result, -731);     
     //Test Dates where date1 comes after date2
        //Test Dates within the same year and month.
        $result = block_ucla_weeksdisplay::cmp_dates('2012-04-10', '2012-04-09');
        $this->assertEqual($result, 1);   
        $result = block_ucla_weeksdisplay::cmp_dates('2012-04-10', '2012-04-01');
        $this->assertEqual($result, 9);   
        //Test Dates within the same year
        $result = block_ucla_weeksdisplay::cmp_dates('2012-04-01', '2012-03-01');
        $this->assertEqual($result, 31);   
        $result = block_ucla_weeksdisplay::cmp_dates('2012-12-01', '2012-01-31');
        $this->assertEqual($result, 365);      
        $result = block_ucla_weeksdisplay::cmp_dates('2013-12-01', '2013-01-31');
        $this->assertEqual($result, 364);            
        //Test dates from different years.
        $result = block_ucla_weeksdisplay::cmp_dates('2013-01-01', '2012-12-31');
        $this->assertEqual($result, 1);      
        $result = block_ucla_weeksdisplay::cmp_dates('2014-01-01', '2012-12-31');
        $this->assertEqual($result, 366);  
        $result = block_ucla_weeksdisplay::cmp_dates('2015-01-01', '2012-12-31');
        $this->assertEqual($result, 731);         
    }     

    function test_is_leap_year(){
        $result = block_ucla_weeksdisplay::is_leap_year(2000);
        $this->assertEqual($result, true);
        $result = block_ucla_weeksdisplay::is_leap_year(2001);
        $this->assertEqual($result, false);
        $result = block_ucla_weeksdisplay::is_leap_year(2002);
        $this->assertEqual($result, false);
        $result = block_ucla_weeksdisplay::is_leap_year(2003);
        $this->assertEqual($result, false);        
        $result = block_ucla_weeksdisplay::is_leap_year(2004);
        $this->assertEqual($result, true);
    }
        
    function test_find_earlier_date(){   
        $result = block_ucla_weeksdisplay::find_earlier_date('2012-04-09', '2012-04-09');
        $this->assertEqual($result, 0);
    //Test Years
        $result = block_ucla_weeksdisplay::find_earlier_date('2010-04-09', '2012-04-09');
        $this->assertEqual($result, -1);       
        $result = block_ucla_weeksdisplay::find_earlier_date('2012-04-09', '2010-04-09');
        $this->assertEqual($result, 1);   
    //Test Days
        $result = block_ucla_weeksdisplay::find_earlier_date('2012-04-09', '2012-04-08');
        $this->assertEqual($result, 1);   
        $result = block_ucla_weeksdisplay::find_earlier_date('2012-04-08', '2012-04-09');
        $this->assertEqual($result, -1);      
    //Test Months
        $result = block_ucla_weeksdisplay::find_earlier_date('2010-03-09', '2010-04-09');
        $this->assertEqual($result, -1);           
        $result = block_ucla_weeksdisplay::find_earlier_date('2010-04-09', '2010-03-09');
        $this->assertEqual($result, 1);       
        $result = block_ucla_weeksdisplay::find_earlier_date('2010-03-29', '2010-04-09');
        $this->assertEqual($result, -1);           
        $result = block_ucla_weeksdisplay::find_earlier_date('2010-04-09', '2010-03-29');
        $this->assertEqual($result, 1);          
    } 
         
    function test_get_dayofweek(){
        $result = block_ucla_weeksdisplay::get_dayofweek('2012-04-09');
        $this->assertEqual($result, 'Mon');
        $result = block_ucla_weeksdisplay::get_dayofweek('2012-04-10');
        $this->assertEqual($result, 'Tue');          
        $result = block_ucla_weeksdisplay::get_dayofweek('2012-04-11');
        $this->assertEqual($result, 'Wed');
        $result = block_ucla_weeksdisplay::get_dayofweek('2012-04-12');
        $this->assertEqual($result, 'Thu');       
        $result = block_ucla_weeksdisplay::get_dayofweek('2012-04-13');
        $this->assertEqual($result, 'Fri');
        $result = block_ucla_weeksdisplay::get_dayofweek('2012-04-14');
        $this->assertEqual($result, 'Sat');  
        $result = block_ucla_weeksdisplay::get_dayofweek('2012-04-15');
        $this->assertEqual($result, 'Sun');         
    }        
 
}


//EOF
