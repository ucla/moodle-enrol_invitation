
<?php
/**
 * Unit tests for ucla_weeksdisplay.
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/blocks/ucla_weeksdisplay/block_ucla_weeksdisplay.php'); // Include the code to test


/**
 * Non-db writing class to test ucla_session 
 */
class ucla_session_test extends ucla_session {
    
    public $results;
    
    function __construct($session, $today) {
        parent::__construct($session);
        $this->_today = $today;
        $this->results = new stdClass();
    }
    
    function update_today($today) {
        $this->_today = $today;
    }
    
    /// Override the parent class functions in order to prevent writing to DB
    
    protected function update_term($term) {
        $this->results->term = $term;
    }
    
    protected function update_week_display($str) {
        $this->results->display = $str;
    }
    
    protected function update_week() {
        $this->results->week = $this->_current_week;
    }
    
    protected function update_active_terms() {
        $term = $this->_year . $this->_quarter;
        $ta = array($term);
        
        for($i = 0; $i < $this->_lookahead; $i++) {
            $term = $this->next_term($term);
            $ta[] = $term;           
        }
        
        $this->results->terms = implode(',', $ta);
    }
    
    /// Getters
    
    function get_display() {
        return $this->results->display;
    }
    
    function get_term() {
        return $this->results->term;
    }
    
    function get_week() {
        return $this->results->week;
    }
    
    function get_terms() {
        return $this->results->terms;
    }
}

class ucla_weeksdisplay_nondb_test extends UnitTestCase {

    function test_quarters() {
        global $CFG;
        ucla_require_registrar();

        // Test a whole year
        // This list was retrieved from the registrar
        $terms = array(
            'summer11A' => array(
                'term' => '111',
                'session' => '8A',
                'session_start' => '2011-06-20',
                'session_end' => '2011-08-12',
                'instruction_start' => '2011-06-20',
                ),
            'summer11C' => array(
                'term' => '111',
                'session' => '6C',
                'session_start' => '2011-08-01',
                'session_end' => '2011-09-09',
                'instruction_start' => '2011-08-01',
                ),
            'fall11' => array(
                'term' => '11F',
                'session' => 'RG',
                'session_start' => '2011-09-19',
                'session_end' => '2011-12-09',
                'instruction_start' => '2011-09-22',
                ),
            'winter12' => array(
                'term' => '12W',
                'session' => 'RG',
                'session_start' => '2012-01-04',
                'session_end' => '2012-03-23',
                'instruction_start' => '2012-01-09',
                ),
            'spring12' => array(
                'term' => '12S',
                'session' => 'RG',
                'session_start' => '2012-03-28',
                'session_end' => '2012-06-15',
                'instruction_start' => '2012-04-02',
                ),
            'summer12A' => array(
                'term' => '121',
                'session' => '8A',
                'session_start' => '2012-06-25',
                'session_end' => '2012-08-17',
                'instruction_start' => '2012-06-25',
                ),
            'summer12C' => array(
                'term' => '121',
                'session' => '6C',
                'session_start' => '2012-08-06',
                'session_end' => '2012-09-14',
                'instruction_start' => '2012-08-06',
                ),
            );
        
        
        // Test summer 11A
        $query = registrar_query::run_registrar_query('ucla_getterms', array($terms['summer11A']['term']), true);
        $session = new ucla_session_test($query, $terms['summer11A']['session_start']);
        
        ////////////////////////////////////////////////////////////////////////
        // At start of session A
        $session->update();
        // For this particular summer, instruction starts at same time as session begins
        $this->assertEqual($session->get_display(), 'Summer 2011 - Session A, Week 1');
        $this->assertEqual('1', $session->get_week());
        $this->assertEqual($session->get_terms(), '111,11F');
        
        ////////////////////////////////////////////////////////////////////////
        // At start of instruction in session A
        $session->update_today($terms['summer11A']['instruction_start']);
        $session->update();
        
        $this->assertEqual($session->get_display(), 'Summer 2011 - Session A, Week 1');
        $this->assertEqual('1', $session->get_week());
        
        ////////////////////////////////////////////////////////////////////////
        // At start of session C
        $sessionc = new ucla_session_test($query, $terms['summer11C']['session_start']);
        $sessionc->update();
        
        $this->assertEqual($sessionc->get_display(), 'Summer 2011 - Session A, Week 7 | Summer 2011 - Session C, Week 1');
        $this->assertEqual('7', $sessionc->get_week());
        $this->assertEqual($session->get_terms(), '111,11F');
        
        ////////////////////////////////////////////////////////////////////////
        // At session C instruction start
        $sessionc->update_today($terms['summer11C']['instruction_start']);
        $sessionc->update();
        
        $this->assertEqual($sessionc->get_display(), 'Summer 2011 - Session A, Week 7 | Summer 2011 - Session C, Week 1');
        $this->assertEqual('7', $sessionc->get_week());
        
        ////////////////////////////////////////////////////////////////////////
        // At end of session A, still in Session C
        $session->update_today($terms['summer11A']['session_end']);
        $session->update();
        $this->assertEqual($session->get_display(), 'Summer 2011 - Session A, Week 8 | Summer 2011 - Session C, Week 2');
        $this->assertEqual('8', $session->get_week());
        $this->assertEqual($session->get_terms(), '111,11F');
        
        ////////////////////////////////////////////////////////////////////////
        // At end of session C (should display term 11F
        $sessionc->update_today($terms['summer11C']['session_end']);
        $sessionc->update();
        $this->assertEqual($sessionc->get_display(), 'Summer 2011 - Session C, Week 6');
        $this->assertEqual('7', $sessionc->get_week());
        $this->assertEqual($sessionc->get_term(), $terms['fall11']['term']);
        
        // Cleanup
        unset($session, $sessionc);
        
        ////////////////////////////////////////////////////////////////////////
        // At start of Fall 11 session
        $query = registrar_query::run_registrar_query('ucla_getterms', array($terms['fall11']['term']), true);
        $session = new ucla_session_test($query, $terms['fall11']['session_start']);
        $session->update();
        
        $this->assertEqual($session->get_display(), 'Fall 2011');
        $this->assertEqual('-1', $session->get_week());
        $this->assertEqual($session->get_terms(), '11F,12W');
        
        ////////////////////////////////////////////////////////////////////////
        // At start of instruction
        $session->update_today($terms['fall11']['instruction_start']);
        $session->update();
        $this->assertEqual($session->get_display(), 'Fall 2011 - Week 0');
        $this->assertEqual('0', $session->get_week());
        
        ////////////////////////////////////////////////////////////////////////
        // Test for fall start of week 1
        $session->update_today('2011-09-26');
        $session->update();
        $this->assertEqual($session->get_display(), 'Fall 2011 - Week 1');
        $this->assertEqual('1', $session->get_week());
        
        ////////////////////////////////////////////////////////////////////////
        // Test for fall, start of finals week
        $session->update_today('2011-12-05');
        $session->update();
        $this->assertEqual($session->get_display(), 'Fall 2011 - Finals week');
        $this->assertEqual('11', $session->get_week());
        
        ////////////////////////////////////////////////////////////////////////
        // Test for end of fall 11 session -- should still display 'finals week'
        $session->update_today($terms['fall11']['session_end']);
        $session->update();
        $this->assertEqual($session->get_display(), 'Fall 2011 - Finals week');
        $this->assertEqual('11', $session->get_week());
        $this->assertEqual($session->get_terms(), '11F,12W');
        
        ////////////////////////////////////////////////////////////////////////
        // Test for day after end of fall 11 session
        // In this case, the display string should be the same, but the term
        // should have been updated to 12W
        $session->update_today('2011-12-10');
        $session->update();
        $this->assertEqual($session->get_display(), 'Fall 2011 - Finals week');
        $this->assertEqual('11', $session->get_week());
        $this->assertEqual($session->get_term(), '12W');
        
        unset($session);
        
        ////////////////////////////////////////////////////////////////////////
        // Test for second day after end of fall 11 session
        // In this case, we should get the new 'Winter 2012' display
        $query = registrar_query::run_registrar_query('ucla_getterms', array($terms['winter12']['term']), true);
        $session = new ucla_session_test($query, '2011-12-11');
        $session->update();
        
        $this->assertEqual($session->get_display(), 'Winter 2012');
        $this->assertEqual('-1', $session->get_week());
        $this->assertEqual($session->get_terms(), '12W,12S');

        ////////////////////////////////////////////////////////////////////////
        // Test for start of winter 12 session
        $session->update_today($terms['winter12']['session_start']);
        $session->update();
        $this->assertEqual($session->get_display(), 'Winter 2012');
        $this->assertEqual('-1', $session->get_week());
        $this->assertEqual($session->get_terms(), '12W,12S');
        
        ////////////////////////////////////////////////////////////////////////
        // Test for start of winter 12 instruction
        $session->update_today($terms['winter12']['instruction_start']);
        $session->update();
        $this->assertEqual($session->get_display(), 'Winter 2012 - Week 1');
        $this->assertEqual('1', $session->get_week());
        $this->assertEqual($session->get_terms(), '12W,12S');
        
        ////////////////////////////////////////////////////////////////////////
        // Test for start of winter 12 finals week
        $session->update_today('2012-03-19');
        $session->update();
        $this->assertEqual($session->get_display(), 'Winter 2012 - Finals week');
        $this->assertEqual('11', $session->get_week());
        $this->assertEqual($session->get_terms(), '12W,12S');
        
        ////////////////////////////////////////////////////////////////////////
        // Test for start of winter 12 session end
        $session->update_today($terms['winter12']['session_end']);
        $session->update();
        
        $this->assertEqual($session->get_display(), 'Winter 2012 - Finals week');
        $this->assertEqual('11', $session->get_week());
        $this->assertEqual($session->get_terms(), '12W,12S');
        
        ////////////////////////////////////////////////////////////////////////
        // Test for day after winter 12 session end
        // The term should have been updated to spring 12
        $session->update_today('2012-03-24');
        $session->update();
        
        $this->assertEqual($session->get_display(), 'Winter 2012 - Finals week');
        $this->assertEqual('11', $session->get_week());
        $this->assertEqual($session->get_term(), '12S');
        
        
        unset($session);
        
        ////////////////////////////////////////////////////////////////////////
        // Test for second day after end of winter 12 session
        // In this case, we should get the new 'Spring 2012' display
        $query = registrar_query::run_registrar_query('ucla_getterms', array($terms['spring12']['term']), true);
        $session = new ucla_session_test($query, '2012-03-25');
        $session->update();
        
        $this->assertEqual($session->get_display(), 'Spring 2012');
        $this->assertEqual('-1', $session->get_week());
        $this->assertEqual($session->get_terms(), '12S,121');
        
        ////////////////////////////////////////////////////////////////////////
        // Test for start of spring 12 session 
        $session->update_today($terms['spring12']['session_start']);
        $session->update();
        $this->assertEqual($session->get_display(), 'Spring 2012');
        $this->assertEqual('-1', $session->get_week());
        $this->assertEqual($session->get_terms(), '12S,121');
        
        ////////////////////////////////////////////////////////////////////////
        // Test for start of spring 12 instruction start 
        $session->update_today($terms['spring12']['instruction_start']);
        $session->update();
        $this->assertEqual($session->get_display(), 'Spring 2012 - Week 1');
        $this->assertEqual('1', $session->get_week());
        $this->assertEqual($session->get_terms(), '12S,121');
        
        ////////////////////////////////////////////////////////////////////////
        // Test for start of spring 12 finals week 
        $session->update_today('2012-06-11');
        $session->update();
        $this->assertEqual($session->get_display(), 'Spring 2012 - Finals week');
        $this->assertEqual('11', $session->get_week());
        $this->assertEqual($session->get_terms(), '12S,121');
        
        ////////////////////////////////////////////////////////////////////////
        // Test for spring 12 session end
        $session->update_today($terms['spring12']['session_end']);
        $session->update();
        $this->assertEqual($session->get_display(), 'Spring 2012 - Finals week');
        $this->assertEqual('11', $session->get_week());
        $this->assertEqual($session->get_terms(), '12S,121');
        
        ////////////////////////////////////////////////////////////////////////
        // Test for spring 12 day after session end
        $session->update_today('2012-06-16');
        $session->update();
        $this->assertEqual($session->get_display(), 'Spring 2012 - Finals week');
        $this->assertEqual($session->get_term(), '121');
        
        unset($session);
        
        ////////////////////////////////////////////////////////////////////////
        // Test for second day after end of spring 12 session
        // In this case, we should get the new 'Summer 2012' display
        $query = registrar_query::run_registrar_query('ucla_getterms', array($terms['summer12A']['term']), true);
        $session = new ucla_session_test($query, '2012-06-17');
        $session->update();
        
        $this->assertEqual($session->get_display(), 'Summer 2012');
        $this->assertEqual('-1', $session->get_week());
        $this->assertEqual($session->get_terms(), '121,12F');
        
        ////////////////////////////////////////////////////////////////////////
        // Test for start of summer 12 session A
        // In this case, instruction begins when the session begins
        $session->update_today($terms['summer12A']['session_start']);
        $session->update();
        $this->assertEqual($session->get_display(), 'Summer 2012 - Session A, Week 1');
        $this->assertEqual('1', $session->get_week());
        $this->assertEqual($session->get_terms(), '121,12F');
        
        ////////////////////////////////////////////////////////////////////////
        // Test for summer 12 session A instruction start
        $session->update_today($terms['summer12A']['instruction_start']);
        $session->update();
        $this->assertEqual($session->get_display(), 'Summer 2012 - Session A, Week 1');
        $this->assertEqual('1', $session->get_week());
        $this->assertEqual($session->get_terms(), '121,12F');
        
        ////////////////////////////////////////////////////////////////////////
        // Test for summer 12 session C session start
        $session->update_today($terms['summer12C']['session_start']);
        $session->update();
        $this->assertEqual($session->get_display(), 'Summer 2012 - Session A, Week 7 | Summer 2012 - Session C, Week 1');
        $this->assertEqual('7', $session->get_week());
        
        unset($session);
        
        ////////////////////////////////////////////////////////////////////////
        // Test for summer 12 session A day after session ends
        $query = registrar_query::run_registrar_query('ucla_getterms', array($terms['summer12C']['term']), true);
        $session = new ucla_session_test($query, '2012-08-18');
        $session->update();
        $this->assertEqual($session->get_display(), 'Summer 2012 - Session C, Week 2');
        $this->assertEqual('2', $session->get_week());

        ////////////////////////////////////////////////////////////////////////
        // Test for summer 12 session A day after session ends
        $session->update_today('2012-08-19');
        $session->update();
        $this->assertEqual($session->get_display(), 'Summer 2012 - Session C, Week 2');
        $this->assertEqual('2', $session->get_week());
        
        ////////////////////////////////////////////////////////////////////////
        // Test for summer 12 session C  session ends
        $session->update_today($terms['summer12C']['session_end']);
        $session->update();
        $this->assertEqual($session->get_display(), 'Summer 2012 - Session C, Week 6');
        $this->assertEqual('6', $session->get_week());
        
        ////////////////////////////////////////////////////////////////////////
        // Test for summer 12 session C  day after session ends
        $session->update_today('2012-09-15');
        $session->update();
        $this->assertEqual($session->get_term(), '12F');
        
        unset($session);
    }

}


//EOF
