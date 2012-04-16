
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

   /*
    * Helper function to create session objects for other tests.
    */
   function create_session_obj($term, $session, $session_start, $session_end, $instruction_start){
       $new_session[0] = $term;
       $new_session[3] = $session;
       $new_session[5] = $session_start;
       $new_session[6] = $session_end;
       $new_session[7] = $instruction_start;
       return $new_session;
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
                return ucla_term_to_text($regular_sessions[0], 'A').', Week '
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

   /**
    * Returns the week of the session that the date is in.
    * @param date string that starts with the format YYYY-MM-DD
    * @param session a session object returned by the get_terms registrar query
    * @return an int representing the week that the date is in.
    * -1 if the date is not within the current week.
    */    
    function test_get_week(){
        $session = $this->create_session_obj('10W','RG','2012-02-01','2012-03-01','2012-02-01');
    //Test days starting before and after the session.
        $date = '2012-01-01';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, -1); 
        $date = '2012-12-01';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, -1);         
    //Test days starting on different days of the week.    
        
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
    function test_find_date_in_sessions(){
        
        $FebtoMarch = $this->create_session_obj('10W','RG','2012-02-01','2012-03-01','2012-02-01');
        $FebtoJune = $this->create_session_obj('10W','RG','2012-02-01','2012-06-01','2012-02-01');
        $MaytoJuly = $this->create_session_obj('10W','RG','2012-05-01','2012-07-01','2012-02-01');
        
    //Cases involving a single session.
        $sessions = NULL;
        $sessions[] = $FebtoMarch;
        //Before the session date.
        $date = '2012-01-01';
        $result = block_ucla_weeksdisplay::find_date_in_sessions($date, $sessions);
        $this->assertEqual($result, -1);  
        //Within the session date.
        $date = '2012-02-02';
        $result = block_ucla_weeksdisplay::find_date_in_sessions($date, $sessions);
        $this->assertEqual($result, array($FebtoMarch));          
        //After the session date.
        $date = '2012-03-02';
        $result = block_ucla_weeksdisplay::find_date_in_sessions($date, $sessions);
        $this->assertEqual($result, 1);          
    //Cases involving multiple sessions.
        $sessions = NULL;
        $sessions[] = $FebtoJune;
        $sessions[] = $MaytoJuly;
        //A date before both sessions
        $date = '2012-01-01';
        $result = block_ucla_weeksdisplay::find_date_in_sessions($date, $sessions);
        $this->assertEqual($result, -1);           
        //A date within both sessions.
        $date = '2012-05-13';
        $result = block_ucla_weeksdisplay::find_date_in_sessions($date, $sessions);
        $this->assertEqual($result, array($FebtoJune, $MaytoJuly));   
        //A date after both sessions.
        $date = '2012-10-13';
        $result = block_ucla_weeksdisplay::find_date_in_sessions($date, $sessions);
        $this->assertEqual($result, 1);     
        //A date inbetween two sessions.
        $sessions = NULL;
        $sessions[] = $FebtoMarch;
        $sessions[] = $MaytoJuly;     
        $date = '2012-04-13';
        $result = block_ucla_weeksdisplay::find_date_in_sessions($date, $sessions);
        $this->assertEqual($result, array($MaytoJuly));          
        
    }
       

    function test_find_regular_sessions(){  
        $sessions = NULL;
        $sessions[] = $this->create_session_obj('10W','RG','2012-01-01','2012-02-01','2012-01-01');
        $sessions[] = $this->create_session_obj('10W','6A','2013-01-01','2013-02-01','2013-01-01');
        $sessions[] = $this->create_session_obj('10W','8A','2013-01-01','2013-02-01','2013-01-01');
        $sessions[] = $this->create_session_obj('10W','6C','2013-01-01','2013-02-01','2013-01-01');
        $sessions[] = $this->create_session_obj('10W','99A','2013-01-01','2013-02-01','2013-01-01');
        
        $result = block_ucla_weeksdisplay::find_regular_sessions($sessions);
        $answer = NULL; //Removes IDE warning.
        $answer[] = $this->create_session_obj('10W','RG','2012-01-01','2012-02-01','2012-01-01');
        $answer[] = $this->create_session_obj('10W','8A','2013-01-01','2013-02-01','2013-01-01');
        $answer[] = $this->create_session_obj('10W','6C','2013-01-01','2013-02-01','2013-01-01');

        $this->assertEqual($result, $answer);        
    }          

    function test_get_next_term(){
        //Test all changes that can happen in one year, and the millenium case.
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
        //Test all changes that can happen in one year, and the millenium case.
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
        $this->assertEqual( ($result < 0), true);
        $session1 = $this->create_session_obj('10W','RG','2012-01-01','2012-02-01','2012-01-01');
        $session2 = $this->create_session_obj('10W','RG','2012-01-01','2012-02-01','2012-01-01');
        $result = block_ucla_weeksdisplay::cmp_sessions($session1, $session2);
        $this->assertEqual(($result == 0), true);        
        $session1 = $this->create_session_obj('10W','RG','2013-01-01','2013-02-01','2013-01-01');
        $session2 = $this->create_session_obj('10W','RG','2012-01-01','2012-02-01','2012-01-01');
        $result = block_ucla_weeksdisplay::cmp_sessions($session1, $session2);
        $this->assertEqual(($result > 1), true);                
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
        $result = block_ucla_weeksdisplay::cmp_dates('2012-12-31', '2012-01-01');
        $this->assertEqual($result, 365);      
        $result = block_ucla_weeksdisplay::cmp_dates('2013-12-31', '2013-01-01');
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
