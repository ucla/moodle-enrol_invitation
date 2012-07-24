<?php
/** 
 * Unit tests for the functions in the eventlib.php file.
 * 
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/uclacoursecreator/eventlib.php');
 
class eventlib_test extends UnitTestCaseUsingDatabase {    
    private $uclacoursecreator;
    private $noncrosslist_course1 = 2;  // need to start at 2, because of SITEID
    private $noncrosslist_course2 = 3;
    private $crosslist_course1 = 4;

    private $noncrosslisted = array();
    private $crosslisted = array();    
    
    /**
     * See if request are deleted for a crosslisted course.
     */
    function test_delete_crosslist_course() {
        global $DB;

        $course = new stdClass();
        $course->id = $this->crosslist_course1;
        $result = handle_course_deleted($course);
        $this->assertTrue($result);
        
        // make sure that course does not exist
        $exists = $DB->record_exists('ucla_request_classes', array('courseid' => $this->crosslist_course1));
        $this->assertFalse($exists);
        
        // make sure that other course requests were not affected
        $exists = $DB->record_exists('ucla_request_classes', array('courseid' => $this->noncrosslist_course2));
        $this->assertTrue($exists);        
    }
    
    /**
     * See if request are deleted for a single, non-crosslisted course.
     */
    function test_delete_noncrosslist_course() {
        global $DB;

        $course = new stdClass();
        $course->id = $this->noncrosslist_course1;
        $result = handle_course_deleted($course);
        $this->assertTrue($result);
        
        // make sure that course does not exist
        $exists = $DB->record_exists('ucla_request_classes', array('courseid' => $this->noncrosslist_course1));
        $this->assertFalse($exists);
        
        // make sure that other course requests were not affected
        $exists = $DB->record_exists('ucla_request_classes', array('courseid' => $this->noncrosslist_course2));
        $this->assertTrue($exists);        
    }
    
    /**
     * Test clearing of an existing MyUCLA url.
     */
    function test_existing_myuclaurl () {
        global $CFG;
        
        $cc = new uclacoursecreator();
        $myucla_urlupdater = $cc->get_myucla_urlupdater();        
        
        // url exist, is for current server => should clear it
        
        // first create url for course
        $url_course = array('term' => $this->noncrosslisted[0]['term'],
                            'srs' => $this->noncrosslisted[0]['srs'],
                            'url' => $CFG->wwwroot . '/course/view.php?id=' . $this->noncrosslist_course1);
        $result = $myucla_urlupdater->send_MyUCLA_urls(array($url_course), true); 
        $this->assertTrue(strpos(array_pop($result), $myucla_urlupdater::expected_success_message));
        
        // now run delete function
        $course = new stdClass();
        $course->id = $this->noncrosslist_course1;        
        $result = handle_course_deleted($course);
        $this->assertTrue($result);        
        
        // verify that myucla url is deleted
        $result = $myucla_urlupdater->send_MyUCLA_urls(array($url_course)); 
        $result = array_pop($result);
        $this->assertTrue(empty($result));
    }
    
    /**
     * Test clearing of an existing MyUCLA url for a crosslisted course.
     */
    function test_existing_myuclaurl_crosslisted () {
        global $CFG;
        
        $cc = new uclacoursecreator();
        $myucla_urlupdater = $cc->get_myucla_urlupdater();        
        
        // url exist, is for current server => should clear it
        
        // first create url for course
        foreach ($this->crosslisted as $crosslist) {
            $url_course = array('term' => $crosslist['term'],
                                'srs' => $crosslist['srs'],
                                'url' => $CFG->wwwroot . '/course/view.php?id=' . $this->crosslist_course1);
            $result = $myucla_urlupdater->send_MyUCLA_urls(array($url_course), true); 
            $this->assertTrue(strpos(array_pop($result), $myucla_urlupdater::expected_success_message));           
        }
        
        // now run delete function
        $course = new stdClass();
        $course->id = $this->crosslist_course1;        
        $result = handle_course_deleted($course);
        $this->assertTrue($result);        
        
        // verify that myucla urls are deleted
        foreach ($this->crosslisted as $crosslist) {
            $url_course = array('term' => $crosslist['term'],
                                'srs' => $crosslist['srs']);
            $result = $myucla_urlupdater->send_MyUCLA_urls(array($url_course)); 
            $result = array_pop($result);
            $this->assertTrue(empty($result));     
        }
    }    
    
    /**
     * Test not clearing of an existing MyUCLA url that isn't on current server.
     */
    function test_existing_nonlocal_myuclaurl () {
        global $CFG;
        
        $cc = new uclacoursecreator();
        $myucla_urlupdater = $cc->get_myucla_urlupdater();        
        
        // url exist, is for current server => should clear it
        
        // first create url for course
        $url_course = array('term' => $this->noncrosslisted[0]['term'],
                            'srs' => $this->noncrosslisted[0]['srs'],
                            'url' => 'http://ucla.edu');
        $result = $myucla_urlupdater->send_MyUCLA_urls(array($url_course), true); 
        $this->assertTrue(strpos(array_pop($result), $myucla_urlupdater::expected_success_message));
        
        // now run delete function
        $course = new stdClass();
        $course->id = $this->noncrosslist_course1;        
        $result = handle_course_deleted($course);
        $this->assertTrue($result);        
        
        // verify that myucla url is not deleted
        $result = $myucla_urlupdater->send_MyUCLA_urls(array($url_course)); 
        $result = array_pop($result);
        $this->assertEqual($result, $url_course['url']);
    }
    
    // setup/teardown functions
    public function setUp() {
        global $CFG, $DB;
        
        parent::setUp();
        $this->switch_to_test_db(); // All operations until end of test method will happen in test DB
        
        // make sure that the necessary tables are generated
        $this->create_test_table('ucla_request_classes', $CFG->admin . '/tool/uclacourserequestor'); 
        
        // make non-crosslisted courses
        $this->noncrosslisted = array(
            array('term' => '121',
                  'srs' => '111111111',
                  'courseid' => $this->noncrosslist_course1,
                  'setid' => '1',
                  'hostcourse' => '1'),
            array('term' => '12F',
                  'srs' => '111111111',
                  'courseid' => $this->noncrosslist_course2,
                  'setid' => '2',
                  'hostcourse' => '1'),
            );
        $this->crosslisted = array(
            array('term' => '12F',
                  'srs' => '222222222',
                  'courseid' => $this->crosslist_course1,
                  'setid' => '3',
                  'hostcourse' => '1'),
            array('term' => '12F',
                  'srs' => '333333333',
                  'courseid' => $this->crosslist_course1,
                  'setid' => '3',
                  'hostcourse' => '0'),
            );
        
        // make an entry in request table
        $requests = array_merge($this->noncrosslisted, $this->crosslisted);
        foreach ($requests as $request) {
            $DB->insert_record('ucla_request_classes', $request);
        }
    }
}
