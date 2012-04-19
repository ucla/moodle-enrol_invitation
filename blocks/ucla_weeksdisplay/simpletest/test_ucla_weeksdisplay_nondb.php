
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
       $new_session['term'] = $term;
       $new_session['session'] = $session;
       $new_session['session_start'] = $session_start;
       $new_session['session_end'] = $session_end;
       $new_session['instruction_start'] = $instruction_start;
       return $new_session;
   } 
   
    /* <Quarter> <Year> - Week <Week number> on a normal week.
    * <Quarter> <Year> - Finals Week for week 11.
    * <Quarter> <Year> - Week 0 if instruction_start > session_start
    * <Quarter> <Year> for all other days that don't fit the stuff above.
    * Summer <Year> - Session A, Week <Week number>
    * Summer <Year> - Session A, Week <Week number> / Session C, Week <Week number>
    * Summer <Year> - Session C, Week <Week number> */   
   function test_get_current_week_display_string(){
       $sessions = NULL;
        $sessions[] = $this->create_session_obj('11F','RG','2011-09-19','2011-12-09','2011-09-22');
    //Test days starting before and after the session.
        $date = '2011-01-01';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011"); 
        $date = '2012-12-01';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011");         
    //Test days starting on different days of the week.    
        
        //Test 0 week.
        $date = '2011-09-20';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 0"); 
        $date = '2011-09-25';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 0");         
        //Test all days of first week
        $date = '2011-09-26';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 1");      
        $date = '2011-09-27';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 1");      
        $date = '2011-09-28';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 1");      
        $date = '2011-09-29';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 1");      
        $date = '2011-09-30';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 1");      
        $date = '2011-10-01';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 1");      
        $date = '2011-10-02';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 1");      
        //Test week 2-11
        $date = '2011-10-03';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 2");      
        $date = '2011-10-16';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 3");      
        $date = '2011-10-17';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 4");      
        $date = '2011-10-28';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 5");      
        $date = '2011-10-31';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 6");      
        $date = '2011-11-08';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 7");      
        $date = '2011-11-15';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 8");      
        $date = '2011-11-23';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 9");      
        $date = '2011-11-29';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 10");               
        $date = '2011-12-05';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Finals Week"); 
//Test Summer sessions
        //Test a single 8A summer session
        $sessions = NULL;
        $sessions[] = $this->create_session_obj('121','8A','2012-06-25','2012-08-17','2012-06-25'); 
        $date = '2012-06-25';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 1");              
        $date = '2012-07-02';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 2");      
        $date = '2012-07-09';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 3");      
        $date = '2012-07-16';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 4");      
        $date = '2012-07-23';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 5");      
        $date = '2012-07-30';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 6");              
        $date = '2012-08-06';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 7");  
        $date = '2012-08-13';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 8");          
        //Test a single 6C summer session
        $sessions = NULL;
        $sessions[] = $this->create_session_obj('121','6C','2012-08-06','2012-09-14','2012-08-06'); 
        $date = '2012-08-06';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session C, Week 1");              
        $date = '2012-08-19';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session C, Week 2");      
        $date = '2012-08-22';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session C, Week 3");      
        $date = '2012-08-30';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session C, Week 4");      
        $date = '2012-09-05';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session C, Week 5");      
        $date = '2012-09-10';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session C, Week 6");           
        //Test a 6C and 8A summer session with a 6A thrown in there to make sure it doesn't interfere.
        $sessions = NULL;
        $sessions[] = $this->create_session_obj('121','6C','2012-08-06','2012-09-14','2012-08-06'); 
        $sessions[] = $this->create_session_obj('121','8A','2012-06-25','2012-08-17','2012-06-25'); 
        $sessions[] = $this->create_session_obj('121','6A','2012-06-25','2012-08-03','2012-06-25'); 
        /*$date = '2012-06-25';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 1");              
        $date = '2012-07-02';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 2");      
        $date = '2012-07-09';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 3");      
        $date = '2012-07-16';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 4");      
        $date = '2012-07-23';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 5");      
        $date = '2012-07-30';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 6");     */         
        $date = '2012-08-06';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 7 / Session C, Week 1");  
        $date = '2012-08-13';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 8 / Session C, Week 2");       
        $date = '2012-08-22';/*
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session C, Week 3");      
        $date = '2012-08-30';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session C, Week 4");      
        $date = '2012-09-05';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session C, Week 5");      
        $date = '2012-09-09';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session C, Week 6");   */      
   }
  
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
        $session = $this->create_session_obj('10W','RG','2011-09-19','2011-12-09','2011-09-22');
        //Test 0 week.
        $date = '2011-09-20';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 0); 
        $date = '2011-09-25';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 0);         
        //Test all days of first week
        $date = '2011-09-26';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 1);      
        $date = '2011-09-27';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 1);      
        $date = '2011-09-28';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 1);      
        $date = '2011-09-29';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 1);      
        $date = '2011-09-30';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 1);      
        $date = '2011-10-01';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 1);      
        $date = '2011-10-02';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 1);      
        //Test week 2-11
        $date = '2011-10-03';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 2);      
        $date = '2011-10-16';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 3);      
        $date = '2011-10-17';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 4);      
        $date = '2011-10-28';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 5);      
        $date = '2011-10-31';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 6);      
        $date = '2011-11-08';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 7);      
        $date = '2011-11-15';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 8);      
        $date = '2011-11-23';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 9);      
        $date = '2011-11-29';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 10);               
        $date = '2011-12-05';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 11); 
//Test Summer sessions
        $session = $this->create_session_obj('10W','RG','2012-06-25','2012-08-03','2012-06-25'); 
        $date = '2012-06-25';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 1);              
        $date = '2012-07-02';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 2);      
        $date = '2012-07-09';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 3);      
        $date = '2012-07-16';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 4);      
        $date = '2012-07-23';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 5);      
        $date = '2012-07-30';
        $result = block_ucla_weeksdisplay::get_week($date, $session);
        $this->assertEqual($result, 6);              
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
        $this->assertEqual($result, '12W');           
        $result = block_ucla_weeksdisplay::get_next_term('99F');
        $this->assertEqual($result, '00W'); 
        $result = block_ucla_weeksdisplay::get_next_term('11W');
        $this->assertEqual($result, '11S');  
        $result = block_ucla_weeksdisplay::get_next_term('111');
        $this->assertEqual($result, '11F');  
        $result = block_ucla_weeksdisplay::get_next_term('11S');
        $this->assertEqual($result, '111');  
    } 
    
    function test_get_prev_term(){
        //Test all changes that can happen in one year, and the millenium case.
        $result = block_ucla_weeksdisplay::get_prev_term('11F');
        $this->assertEqual($result, '111');           
        $result = block_ucla_weeksdisplay::get_prev_term('00W');
        $this->assertEqual($result, '99F'); 
        $result = block_ucla_weeksdisplay::get_prev_term('11W');
        $this->assertEqual($result, '10F');  
        $result = block_ucla_weeksdisplay::get_prev_term('111');
        $this->assertEqual($result, '11S');  
        $result = block_ucla_weeksdisplay::get_prev_term('11S');
        $this->assertEqual($result, '11W');  
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
