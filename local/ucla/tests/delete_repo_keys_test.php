<?php
/**
 * Unit tests for eventslib.php/delete_repo_keys.
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
// Make sure the code being tested is accessible.
global $CFG;
require_once($CFG->dirroot . '/local/ucla/eventslib.php'); // Include the code to test
 
class delete_repo_keys_test extends advanced_testcase {        
    /**
     * Test user has dropbox enabled
     */
    function test_single_dropbox() {
        global $DB;
        
        $this->create_repo_keys(1, true, false);
                
        $eventdata = new stdClass();
        $eventdata->userid = 1;
        
        $result = delete_repo_keys($eventdata);
        $this->assertTrue($result);        

        // make sure that there are no repo fields for user
        $records = $DB->get_records('user_preferences', array('userid' => 1));
        $this->assertEmpty($records);
    }
    
    /**
     * Test user has boxnet enabled
     */
    function test_single_boxnet() {
        global $DB;
        
        $this->create_repo_keys(1, false, true);

        $eventdata = new stdClass();
        $eventdata->userid = 1;
        
        $result = delete_repo_keys($eventdata);
        $this->assertTrue($result);        

        // make sure that there are no repo fields for user
        $result = $DB->record_exists('user_preferences', array('userid' => 1));
        $this->assertFalse($result);
    }    
    
    /**
     * Test user has dropbox and boxnet enabled
     */
    function test_single_dropbox_and_boxnet() {
        global $DB;
        
        $this->create_repo_keys(1);
        
        $eventdata = new stdClass();
        $eventdata->userid = 1;
        
        $result = delete_repo_keys($eventdata);
        $this->assertTrue($result);        

        // make sure that there are no repo fields for user
        $result = $DB->record_exists('user_preferences', array('userid' => 1));
        $this->assertFalse($result);
    }    
    
    /**
     * Multiple users have dropbox enabled
     */
    function test_multi_dropbox() {
        global $DB;
        
        $this->create_repo_keys(1, true, false);
        $this->create_repo_keys(2, true, false);

        $eventdata = new stdClass();
        $eventdata->userid = 1;
        
        $result = delete_repo_keys($eventdata);
        $this->assertTrue($result);        

        // make sure that there are no dropbox fields for target user
        $result = $DB->record_exists('user_preferences', array('userid' => 1));
        $this->assertFalse($result);
        
        // make sure that there are repo fields for other user
        $result = $DB->record_exists('user_preferences', array('userid' => 2));
        $this->assertTrue($result);       
    }
    
    /**
     * Multiple users has boxnet enabled
     */
    function test_multi_boxnet() {
        global $DB;
        
        $this->create_repo_keys(1, false, true);
        $this->create_repo_keys(2, false, true);
        
        $eventdata = new stdClass();
        $eventdata->userid = 1;
        
        $result = delete_repo_keys($eventdata);
        $this->assertTrue($result);        

        // make sure that there are no repo fields for user
        $result = $DB->record_exists('user_preferences', array('userid' => 1));
        $this->assertFalse($result);
                
        // make sure that there are repo fields for other user
        $result = $DB->record_exists('user_preferences', array('userid' => 2));
        $this->assertTrue($result);  
    }    
    
    /**
     * Multiple users have dropbox and boxnet enabled
     */
    function test_mutil_dropbox_and_boxnet() {
        global $DB;
        
        $this->create_repo_keys(1);
        $this->create_repo_keys(2);
        
        $eventdata = new stdClass();
        $eventdata->userid = 1;
        
        $result = delete_repo_keys($eventdata);
        $this->assertTrue($result);        

        // make sure that there are no repo fields for user
        $result = $DB->record_exists('user_preferences', array('userid' => 1));
        $this->assertFalse($result);
                
        // make sure that there are repo fields for other user
        $result = $DB->record_exists('user_preferences', array('userid' => 2));
        $this->assertTrue($result);        
    }        
    
    /**
     * Tests deleting of user's repo keys when their lastaccess is beyond the
     * timeout period (300 seconds aka 5 minutes) limit.
     */
    function test_user_lastaccess_expired() {
        global $DB;        
        $data_generator = self::getDataGenerator();
        
        // user beyond time limit
        $user1 = $data_generator->create_user(array('lastaccess' => time()-301));
        // user at time limit
        $user2 = $data_generator->create_user(array('lastaccess' => time()-300));
        // user below time limit
        $user3 = $data_generator->create_user(array('lastaccess' => time()-299));
        
        // create their repo keys
        $this->create_repo_keys($user1->id);
        $this->create_repo_keys($user2->id);
        $this->create_repo_keys($user3->id);
        
        // Create other preferences
        $this->create_other_prefs($user1->id);
        $this->create_other_prefs($user3->id);
        
        // make sure keys exist
        $result = $DB->record_exists('user_preferences', array('userid' => $user1->id));
        $this->assertTrue($result);               
        $result = $DB->record_exists('user_preferences', array('userid' => $user2->id));
        $this->assertTrue($result);                       
        $result = $DB->record_exists('user_preferences', array('userid' => $user3->id));
        $this->assertTrue($result);               
        
        // run cron function and see if user's repo keys are deleted
        $result = delete_repo_keys();
        $this->assertTrue($result);
        
        // make sure that $user1 & $user2 have no more repo keys
        $result = $DB->record_exists('user_preferences', 
                array('userid' => $user1->id, 'name' => 'boxnet__auth_token'));
        $this->assertFalse($result);
        $result = $DB->record_exists('user_preferences', 
                array('userid' => $user1->id, 'name' => 'dropbox__access_secret'));
        $this->assertFalse($result);
        $result = $DB->record_exists('user_preferences', 
                array('userid' => $user1->id, 'name' => 'dropbox__request_secret'));
        $this->assertFalse($result);
        $result = $DB->record_exists('user_preferences', 
                array('userid' => $user1->id, 'name' => 'boxnet__auth_token'));
        $this->assertFalse($result);
        // userid=2 should have nothing
        $result = $DB->record_exists('user_preferences', array('userid' => $user2->id));
        $this->assertFalse($result);
        
        // make sure that the other preferences were not deleted
        $result = $DB->record_exists('user_preferences', 
                array('userid' => $user1->id, 'name' => 'noeditingicons'));
        $this->assertTrue($result);        
        $result = $DB->record_exists('user_preferences', 
                array('userid' => $user1->id, 'name' => 'otherpref'));
        $this->assertTrue($result);
        $result = $DB->record_exists('user_preferences', 
                array('userid' => $user3->id, 'name' => 'noeditingicons'));
        $this->assertTrue($result);
        
        // make sure that $user3 still has their keys        
        $result = $DB->record_exists('user_preferences', array('userid' => $user3->id));
        $this->assertTrue($result);         
    }
    
    protected function setUp() {
        $this->resetAfterTest(true);
    }
    
    /**
     * Helper function to create the dropbox/boxnet repo keys in user 
     * preferences table.
     * 
     * @param int $userid
     * @param boolean $dropbox
     * @param boolean $boxnet
     */
    private function create_repo_keys($userid, $dropbox=true, $boxnet=true) {
        // first row are the columns
        $data['user_preferences'][] = array('userid', 'name', 'value');
        // followed by the actual data you want in the db
        if ($dropbox) {
            $data['user_preferences'][] = array($userid, 'dropbox__access_key', 'test123');
            $data['user_preferences'][] = array($userid, 'dropbox__access_secret', 'test123');
            $data['user_preferences'][] = array($userid, 'dropbox__request_secret', 'test123');
        }
        if ($boxnet) {
            $data['user_preferences'][] = array($userid, 'boxnet__auth_token', 'test123');        
        }
        
        $dataset = $this->createArrayDataSet($data);
        $this->loadDataSet($dataset);        
    }
    
    /**
     * Creates other preferences to test that they are not deleted
     * 
     * @param type $userid 
     */
    private function create_other_prefs($userid) {
        $data['user_preferences'][] = array('userid', 'name', 'value');
        $data['user_preferences'][] = array($userid, 'noeditingicons', '0');
        $data['user_preferences'][] = array($userid, 'otherpref', 'yes');
        
        $dataset = $this->createArrayDataSet($data);
        $this->loadDataSet($dataset);
    }
}
