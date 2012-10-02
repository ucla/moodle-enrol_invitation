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
        
        // first row are the columns
        $data['user_preferences'][] = array('userid', 'name', 'value');
        // followed by the actual data you want in the db
        $data['user_preferences'][] = array('userid' => 1, 'name' => 'dropbox__access_key', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 1, 'name' => 'dropbox__access_secret', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 1, 'name' => 'dropbox__request_secret', 'value' => 'test123');

        $dataset = $this->createArrayDataSet($data);
        $this->loadDataSet($dataset);
        
        $eventdata = new stdClass();
        $eventdata->userid = 1;
        
        $result = delete_repo_keys($eventdata);
        $this->assertTrue($result);        

        // make sure that there are no repo fields for user
        $records = $DB->get_records('user_preferences', array('userid' => 1));
        $this->assertEmpty($records);
        
        $this->resetAfterTest();
    }
    
    /**
     * Test user has boxnet enabled
     */
    function test_single_boxnet() {
        global $DB;
        
        // first row are the columns
        $data['user_preferences'][] = array('userid', 'name', 'value');
        // followed by the actual data you want in the db
        $data['user_preferences'][] = array('userid' => 1, 'name' => 'boxnet__auth_token', 'value' => 'test123');

        $dataset = $this->createArrayDataSet($data);
        $this->loadDataSet($dataset);
        
        $eventdata = new stdClass();
        $eventdata->userid = 1;
        
        $result = delete_repo_keys($eventdata);
        $this->assertTrue($result);        

        // make sure that there are no repo fields for user
        $records = $DB->get_records('user_preferences', array('userid' => 1));
        $this->assertEmpty($records);
        
        $this->resetAfterTest();
    }    
    
    /**
     * Test user has dropbox and boxnet enabled
     */
    function test_single_dropbox_and_boxnet() {
        global $DB;
        
        // first row are the columns
        $data['user_preferences'][] = array('userid', 'name', 'value');
        // followed by the actual data you want in the db
        $data['user_preferences'][] = array('userid' => 1, 'name' => 'dropbox__access_key', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 1, 'name' => 'dropbox__access_secret', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 1, 'name' => 'dropbox__request_secret', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 1, 'name' => 'boxnet__auth_token', 'value' => 'test123');
        
        $dataset = $this->createArrayDataSet($data);
        $this->loadDataSet($dataset);
        
        $eventdata = new stdClass();
        $eventdata->userid = 1;
        
        $result = delete_repo_keys($eventdata);
        $this->assertTrue($result);        

        // make sure that there are no repo fields for user
        $records = $DB->get_records('user_preferences', array('userid' => 1));
        $this->assertEmpty($records);
        
        $this->resetAfterTest();
    }    
    
    /**
     * Multiple users have dropbox enabled
     */
    function test_multi_dropbox() {
        global $DB;
        
        // first row are the columns
        $data['user_preferences'][] = array('userid', 'name', 'value');
        // followed by the actual data you want in the db
        $data['user_preferences'][] = array('userid' => 1, 'name' => 'dropbox__access_key', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 1, 'name' => 'dropbox__access_secret', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 1, 'name' => 'dropbox__request_secret', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 2, 'name' => 'dropbox__access_key', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 2, 'name' => 'dropbox__access_secret', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 2, 'name' => 'dropbox__request_secret', 'value' => 'test123');

        
        $dataset = $this->createArrayDataSet($data);
        $this->loadDataSet($dataset);
        
        $eventdata = new stdClass();
        $eventdata->userid = 1;
        
        $result = delete_repo_keys($eventdata);
        $this->assertTrue($result);        

        // make sure that there are no dropbox fields for target user
        $records = $DB->get_records('user_preferences', array('userid' => 1));
        $this->assertEmpty($records);
        
        // make sure that there are repo fields for other user
        $records = $DB->get_records('user_preferences', array('userid' => 2));
        $this->assertNotEmpty($records);        
        
        $this->resetAfterTest();
    }
    
    /**
     * Multiple users has boxnet enabled
     */
    function test_multi_boxnet() {
        global $DB;
        
        // first row are the columns
        $data['user_preferences'][] = array('userid', 'name', 'value');
        // followed by the actual data you want in the db
        $data['user_preferences'][] = array('userid' => 1, 'name' => 'boxnet__auth_token', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 2, 'name' => 'boxnet__auth_token', 'value' => 'test123');

        $dataset = $this->createArrayDataSet($data);
        $this->loadDataSet($dataset);
        
        $eventdata = new stdClass();
        $eventdata->userid = 1;
        
        $result = delete_repo_keys($eventdata);
        $this->assertTrue($result);        

        // make sure that there are no repo fields for user
        $records = $DB->get_records('user_preferences', array('userid' => 1));
        $this->assertEmpty($records);
                
        // make sure that there are repo fields for other user
        $records = $DB->get_records('user_preferences', array('userid' => 2));
        $this->assertNotEmpty($records);        
        
        $this->resetAfterTest();
    }    
    
    /**
     * Multiple users have dropbox and boxnet enabled
     */
    function test_mutil_dropbox_and_boxnet() {
        global $DB;
        
        // first row are the columns
        $data['user_preferences'][] = array('userid', 'name', 'value');
        // followed by the actual data you want in the db
        $data['user_preferences'][] = array('userid' => 1, 'name' => 'dropbox__access_key', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 1, 'name' => 'dropbox__access_secret', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 1, 'name' => 'dropbox__request_secret', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 1, 'name' => 'boxnet__auth_token', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 2, 'name' => 'dropbox__access_key', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 2, 'name' => 'dropbox__access_secret', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 2, 'name' => 'dropbox__request_secret', 'value' => 'test123');
        $data['user_preferences'][] = array('userid' => 2, 'name' => 'boxnet__auth_token', 'value' => 'test123');
        
        $dataset = $this->createArrayDataSet($data);
        $this->loadDataSet($dataset);
        
        $eventdata = new stdClass();
        $eventdata->userid = 1;
        
        $result = delete_repo_keys($eventdata);
        $this->assertTrue($result);        

        // make sure that there are no repo fields for user
        $records = $DB->get_records('user_preferences', array('userid' => 1));
        $this->assertEmpty($records);
                
        // make sure that there are repo fields for other user
        $records = $DB->get_records('user_preferences', array('userid' => 2));
        $this->assertNotEmpty($records);        
        
        $this->resetAfterTest();
    }        
}
