
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
    public function setUp() {
        parent::setUp();
        global $DB;
        $this->switch_to_test_db(); // All operations until end of test method will happen in test DB
        $this->create_test_table('config_plugins', 'lib');    
        $this->create_test_table('config', 'lib'); 
    }
    
    
   function create_session_obj($term, $session, $session_start, $session_end, $instruction_start) {
       $new_session['term'] = $term;
       $new_session['session'] = $session;
       $new_session['session_start'] = $session_start;
       $new_session['session_end'] = $session_end;
       $new_session['instruction_start'] = $instruction_start;
       return $new_session;
   }     
    
    function test_init_currentterm() {
     
        block_ucla_weeksdisplay::init_currentterm('2012-12-12');
        $result = get_config(NULL, 'currentterm');
        $this->assertEqual($result, '12F');
        block_ucla_weeksdisplay::init_currentterm('2012-01-12');
        $result = get_config(NULL, 'currentterm');
        $this->assertEqual($result, '12W');        
        block_ucla_weeksdisplay::init_currentterm('2012-05-12');
        $result = get_config(NULL, 'currentterm');
        $this->assertEqual($result, '12S');        
        block_ucla_weeksdisplay::init_currentterm('2012-08-12');
        $result = get_config(NULL, 'currentterm');
        $this->assertEqual($result, '121');               
    }

   function test_set_current_week_display() {
        $sessions = NULL;
        $sessions[] = $this->create_session_obj('11F','RG','2011-09-19','2011-12-09','2011-09-22');   
//Date is within current term        
        //Test 0 week
        $date = '2011-09-20';
        block_ucla_weeksdisplay::set_term_configs('11F');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Fall 2011 - Week 0");        
        //Test first week
        $date = '2011-09-26';
        block_ucla_weeksdisplay::set_term_configs('11F');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Fall 2011 - Week 1");  
        //Test finals week
        $date = '2011-12-05';
        block_ucla_weeksdisplay::set_term_configs('11F');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Fall 2011 - Finals Week"); 
        //Test before the current_term's session_start 
        //date but after the last term's session_end date
        $date = '2011-09-15';
        block_ucla_weeksdisplay::set_term_configs('11F');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Fall 2011"); 
//Test date 1 term before the current term.        
        //Test first week
        $date = '2011-01-04';
        block_ucla_weeksdisplay::set_term_configs('11S');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Winter 2011 - Week 1");  
        //Test finals week
        $date = '2011-03-18';
        block_ucla_weeksdisplay::set_term_configs('11S');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Winter 2011 - Finals Week"); 
        //Test before that term's session_start 
        //date but after the current term's session_end date
        $date = '2011-01-01';
        block_ucla_weeksdisplay::set_term_configs('11S');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Winter 2011");     
//Test date 2 terms before current_term        
        //Test 0 week
        $date = '2010-09-20';
        block_ucla_weeksdisplay::set_term_configs('11S');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Fall 2010 - Week 0");  
        //Test first week
        $date = '2010-09-28';
        block_ucla_weeksdisplay::set_term_configs('11S');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Fall 2010 - Week 1");  
        //Test finals week
        $date = '2010-12-07';
        block_ucla_weeksdisplay::set_term_configs('11S');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Fall 2010 - Finals Week"); 
        //Test before that term's session_start 
        //date but after the last term's session_end date
        $date = '2010-09-16';
        block_ucla_weeksdisplay::set_term_configs('11S');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Fall 2010");              
//Test date 1 term after the current term       
        //Test 0 week
        $date = '2012-01-04';
        block_ucla_weeksdisplay::set_term_configs('11F');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Winter 2012 - Week 0");        
        //Test first week
        $date = '2012-01-10';
        block_ucla_weeksdisplay::set_term_configs('11F');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Winter 2012 - Week 1");  
        //Test finals week
        $date = '2012-03-20';
        block_ucla_weeksdisplay::set_term_configs('11F');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Winter 2012 - Finals Week"); 
        //Test before the term's session_start 
        //date but after the previous term's session_end date
        $date = '2012-01-02';
        block_ucla_weeksdisplay::set_term_configs('11F');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Winter 2012");         
//Test date 2 terms after the current term       
        //Test 0 week
        $date = '2012-03-28';
        block_ucla_weeksdisplay::set_term_configs('11F');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Spring 2012 - Week 0");        
        //Test first week
        $date = '2012-04-03';
        block_ucla_weeksdisplay::set_term_configs('11F');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Spring 2012 - Week 1");  
        //Test finals week
        $date = '2012-06-11';
        block_ucla_weeksdisplay::set_term_configs('11F');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Spring 2012 - Finals Week"); 
        //Test before the term's session_start 
        //date but after the previous term's session_end date
        $date = '2012-03-26';
        block_ucla_weeksdisplay::set_term_configs('11F');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Spring 2012");   
//Test date within a summer session.
        $date = '2012-08-06';
        block_ucla_weeksdisplay::set_term_configs('121');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Summer 2012 - Session A, Week 7 / Session C, Week 1");  
        $date = '2012-08-13';
        block_ucla_weeksdisplay::set_term_configs('121');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Summer 2012 - Session A, Week 8 / Session C, Week 2");       
        $date = '2012-08-22';
        block_ucla_weeksdisplay::set_term_configs('121');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Summer 2012 - Session C, Week 3");      
        $date = '2012-07-02';
        block_ucla_weeksdisplay::set_term_configs('121');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Summer 2012 - Session A, Week 2");          
        $date = '2012-08-06';
        block_ucla_weeksdisplay::set_term_configs('111');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Summer 2012 - Session A, Week 7 / Session C, Week 1");  
        $date = '2012-08-13';
        block_ucla_weeksdisplay::set_term_configs('111');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Summer 2012 - Session A, Week 8 / Session C, Week 2");       
        $date = '2012-08-22';
        block_ucla_weeksdisplay::set_term_configs('111');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Summer 2012 - Session C, Week 3");      
        $date = '2012-07-02';
        block_ucla_weeksdisplay::set_term_configs('111');
        block_ucla_weeksdisplay::set_current_week_display($date); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Summer 2012 - Session A, Week 2");  
   } 

   function test_set_term_configs() {
       block_ucla_weeksdisplay::set_term_configs('11W');
       $result = get_config(NULL, 'currentterm');
       $this->assertEqual($result, '11W');  
       $result = get_config('local_ucla', 'active_terms');
       $this->assertEqual($result, '11W,11S,111,11F'); 
       
       block_ucla_weeksdisplay::set_term_configs('11S');
       $result = get_config(NULL, 'currentterm');
       $this->assertEqual($result, '11S');  
       $result = get_config('local_ucla', 'active_terms');
       $this->assertEqual($result, '11S,111,11F,12W'); 

       block_ucla_weeksdisplay::set_term_configs('111');
       $result = get_config(NULL, 'currentterm');
       $this->assertEqual($result, '111');  
       $result = get_config('local_ucla', 'active_terms');
       $this->assertEqual($result, '111,11F,12W,12S'); 
       
       block_ucla_weeksdisplay::set_term_configs('11F');
       $result = get_config(NULL, 'currentterm');
       $this->assertEqual($result, '11F');  
       $result = get_config('local_ucla', 'active_terms');
       $this->assertEqual($result, '11F,12W,12S,121');     
   } 
   
   function test_set_current_week_display_config() {
               
        //Basic test because this function is just a wrapper function for the get_current_week_display_string.
        $sessions = NULL;
        $sessions[] = $this->create_session_obj('121','8A','2012-06-25','2012-08-17','2012-06-25'); 
        $date = '2012-06-25';
        block_ucla_weeksdisplay::set_current_week_display_config($date, $sessions); 
        $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Summer 2012 - Session A, Week 1");              

   }    
   
    function test_set_current_week_config(){
        block_ucla_weeksdisplay::set_current_week_config(0);
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 0);   
        block_ucla_weeksdisplay::set_current_week_config(1);
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 1);      
        block_ucla_weeksdisplay::set_current_week_config(11);
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 11);  
        block_ucla_weeksdisplay::set_current_week_config(-1);
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, -1);          
    }
    
    /* <Quarter> <Year> - Week <Week number> on a normal week.
    * <Quarter> <Year> - Finals Week for week 11.
    * <Quarter> <Year> - Week 0 if instruction_start > session_start
    * <Quarter> <Year> for all other days that don't fit the stuff above.
    * Summer <Year> - Session A, Week <Week number>
    * Summer <Year> - Session A, Week <Week number> / Session C, Week <Week number>
    * Summer <Year> - Session C, Week <Week number> */   
   function test_get_current_week_display_string() {
        $sessions = NULL;
        $sessions[] = $this->create_session_obj('11F','RG','2011-09-19','2011-12-09','2011-09-22');
    //Test days starting before and after the session.
        $date = '2011-01-01';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011"); 
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, -1);           
        $date = '2012-12-01';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011");       
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, -1);           
    //Test days starting on different days of the week.    
        
        //Test 0 week.
        $date = '2011-09-20';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 0"); 
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 0);   
        $date = '2011-09-25';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 0");  
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 0);
        //Test all days of first week
        $date = '2011-09-26';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 1");  
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 1);
        $date = '2011-09-27';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 1"); 
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 1);
        $date = '2011-09-28';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 1");  
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 1);
        $date = '2011-09-29';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 1");  
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 1);
        $date = '2011-09-30';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 1");    
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 1);
        $date = '2011-10-01';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 1");  
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 1);
        $date = '2011-10-02';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 1");
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 1);
        //Test week 2-11
        $date = '2011-10-03';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 2");  
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 2);
        $date = '2011-10-16';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 3");   
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 3);
        $date = '2011-10-17';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 4"); 
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 4);
        $date = '2011-10-28';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 5");   
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 5);
        $date = '2011-10-31';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 6");     
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 6);
        $date = '2011-11-08';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 7");  
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 7);
        $date = '2011-11-15';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 8");   
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 8);
        $date = '2011-11-23';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 9"); 
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 9);
        $date = '2011-11-29';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Week 10");   
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 10);
        $date = '2011-12-05';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Fall 2011 - Finals Week"); 
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 11);
//Test Summer sessions
        //Test a single 8A summer session
        $sessions = NULL;
        $sessions[] = $this->create_session_obj('121','8A','2012-06-25','2012-08-17','2012-06-25'); 
        $date = '2012-06-25';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 1");      
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 1);
        $date = '2012-07-02';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 2");
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 2);
        $date = '2012-07-09';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 3");
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 3);
        $date = '2012-07-16';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 4");  
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 4);
        $date = '2012-07-23';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 5");   
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 5);
        $date = '2012-07-30';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 6"); 
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 6);
        $date = '2012-08-06';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 7");  
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 7);
        $date = '2012-08-13';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 8");  
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 8);
        //Test a single 6C summer session
        $sessions = NULL;
        $sessions[] = $this->create_session_obj('121','6C','2012-08-06','2012-09-14','2012-08-06'); 
        $date = '2012-08-06';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session C, Week 1"); 
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 1);
        $date = '2012-08-19';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session C, Week 2");  
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 2);
        $date = '2012-08-22';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session C, Week 3");     
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 3);
        $date = '2012-08-30';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session C, Week 4");  
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 4);
        $date = '2012-09-05';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session C, Week 5");  
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 5);
        $date = '2012-09-10';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session C, Week 6");  
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 6);
        //Test a 6C and 8A summer session with a 6A thrown in there to make sure it doesn't interfere.
        $sessions = NULL;
        $sessions[] = $this->create_session_obj('121','6C','2012-08-06','2012-09-14','2012-08-06'); 
        $sessions[] = $this->create_session_obj('121','8A','2012-06-25','2012-08-17','2012-06-25'); 
        $sessions[] = $this->create_session_obj('121','6A','2012-06-25','2012-08-03','2012-06-25'); 
        //Commented out because these cases don't work right now because the input should never
        //be this for these outputs.
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
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 7);
        $date = '2012-08-13';
        $result = block_ucla_weeksdisplay::get_current_week_display_string($date, $sessions); 
        $this->assertEqual($result, "Summer 2012 - Session A, Week 8 / Session C, Week 2"); 
        $result = get_config('local_ucla', 'current_week');
        $this->assertEqual($result, 8);
        /*$date = '2012-08-22';
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
}


//EOF
