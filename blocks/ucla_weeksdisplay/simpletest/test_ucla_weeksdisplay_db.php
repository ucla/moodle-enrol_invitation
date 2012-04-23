
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
    
    
   function create_session_obj($term, $session, $session_start, $session_end, $instruction_start){
       $new_session['term'] = $term;
       $new_session['session'] = $session;
       $new_session['session_start'] = $session_start;
       $new_session['session_end'] = $session_end;
       $new_session['instruction_start'] = $instruction_start;
       return $new_session;
   }     
    
    function test_init_currentterm(){
     
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

   function test_set_current_week_display(){
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

   function test_set_term_configs(){
       block_ucla_weeksdisplay::set_term_configs('11W');
       $result = get_config(NULL, 'currentterm');
       $this->assertEqual($result, '11W');  
       $result = get_config('local_ucla', 'active_terms');
       $this->assertEqual($result, '11W, 11S, 111, 11F'); 
       $result = get_config('tool_uclacourserequestor', 'terms');
       $this->assertEqual($result, '11W, 11S, 111, 11F'); 
       $result = get_config('tool_uclacoursecreator', 'terms');
       $this->assertEqual($result, '11W, 11S, 111, 11F');  
       block_ucla_weeksdisplay::set_term_configs('11S');
       $result = get_config(NULL, 'currentterm');
       $this->assertEqual($result, '11S');  
       $result = get_config('local_ucla', 'active_terms');
       $this->assertEqual($result, '11S, 111, 11F, 12W'); 
       $result = get_config('tool_uclacourserequestor', 'terms');
       $this->assertEqual($result, '11S, 111, 11F, 12W'); 
       $result = get_config('tool_uclacoursecreator', 'terms');
       $this->assertEqual($result, '11S, 111, 11F, 12W'); 
       block_ucla_weeksdisplay::set_term_configs('111');
       $result = get_config(NULL, 'currentterm');
       $this->assertEqual($result, '111');  
       $result = get_config('local_ucla', 'active_terms');
       $this->assertEqual($result, '111, 11F, 12W, 12S'); 
       $result = get_config('tool_uclacourserequestor', 'terms');
       $this->assertEqual($result, '111, 11F, 12W, 12S'); 
       $result = get_config('tool_uclacoursecreator', 'terms');
       $this->assertEqual($result, '111, 11F, 12W, 12S'); 
       block_ucla_weeksdisplay::set_term_configs('11F');
       $result = get_config(NULL, 'currentterm');
       $this->assertEqual($result, '11F');  
       $result = get_config('local_ucla', 'active_terms');
       $this->assertEqual($result, '11F, 12W, 12S, 121'); 
       $result = get_config('tool_uclacourserequestor', 'terms');
       $this->assertEqual($result, '11F, 12W, 12S, 121'); 
       $result = get_config('tool_uclacoursecreator', 'terms');
       $this->assertEqual($result, '11F, 12W, 12S, 121');        
   } 
   
   function test_set_current_week_display_config(){
               
        //Basic test because this function is just a wrapper function for the get_current_week_display_string.
        $sessions = NULL;
        $sessions[] = $this->create_session_obj('121','8A','2012-06-25','2012-08-17','2012-06-25'); 
        $date = '2012-06-25';
        block_ucla_weeksdisplay::set_current_week_display_config($date, $sessions); 
        $result = $result = get_config('local_ucla', 'current_week_display');
        $this->assertEqual($result, "Summer 2012 - Session A, Week 1");              

   }    
}


//EOF
