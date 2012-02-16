<?php
/**
 * Unit tests for myucla_urlupdater.class.php.
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/myucla_url/myucla_urlupdater.class.php');
 
class myucla_urlupdater_test extends UnitTestCase {
    private $myucla_urlupdater = null;
    
    /**
     * Try to set a valid course's url.
     */
    function test_setting_valid_course() {
        $course = array('term' => '12W', 
                        'srs' => '123456789', 
                        'url' => 'http://ucla.edu');
        $result = $this->set_url($course);
        $this->assertTrue($result);
    }
 
    /**
     * Try to set an invalid course's url.
     */
    function test_setting_invalid_course() {
        $course = array('term' => '12W', 
                        'srs' => '12345678', 
                        'url' => 'http://ucla.edu');
        $result = $this->set_url($course);        
        $this->assertFalse($result);
    }   

    /**
     * Try to set a complex URL to test encoding/decoding. 
     */
    function test_setting_complex_url() {
        $test_url = 'http://ucla.edu/index.php?id=something&id2=somewhere';
        $course = array('term' => '12W', 
                        'srs' => '123456789', 
                        'url' => $test_url);
        $result = $this->set_url($course);        
        $this->assertTrue($result);
        
        // now get url and make sure it matches
        $result = $this->get_url($course);
        $this->assertTrue($test_url == $result);
    }        

    /**
     * Try to set, get, and clear url using empty string.
     */
    function test_set_get_clear_with_empty() {
        $test_url = 'http://ucla.edu';
        $course = array('term' => '12W', 
                        'srs' => '123456789', 
                        'url' => $test_url);
        $result = $this->set_url($course);       
        $this->assertTrue($result);
        
        // now get url and make sure it matches
        $result = $this->get_url($course);
        $this->assertTrue($test_url == $result);
        
        // now clear it
        $course['url'] = '';
        $result = $this->set_url($course);
        $this->assertTrue($result);   
        
        // get it to make sure it is clear
        $result = $this->get_url($course);
        $this->assertTrue(empty($result));    
    }          
    
    /**
     * Try to set, get, and clear url using null.
     */
    function test_set_get_clear_with_null() {
        $test_url = 'http://ucla.edu';
        $course = array('term' => '12W', 
                        'srs' => '123456789', 
                        'url' => $test_url);
        $result = $this->set_url($course);       
        $this->assertTrue($result);
        
        // now get url and make sure it matches
        $result = $this->get_url($course);
        $this->assertTrue($test_url == $result);
        
        // now clear it
        $course['url'] = null;
        $result = $this->set_url($course);
        $this->assertTrue($result);   
        
        // get it to make sure it is clear
        $result = $this->get_url($course);
        $this->assertTrue(empty($result));    
    }          

    
    /**
     * Try syncing a variety of course urls (both set and unset) and an invalid 
     * course.
     */
    function test_sync() {
        $set_course = array('term' => '12W', 
                            'srs' => '123456789', 
                            'url' => 'http://ucla.edu');
        $unset_course = array('term' => '12W', 
                              'srs' => '987654321', 
                              'url' => 'http://newsroom.ucla.edu');        
        $invalid_course = array('term' => '12W', 
                            'srs' => '12345678', 
                            'url' => 'http://www.usc.edu');
        
        // first set $set_course
        $result = $this->set_url($set_course);       
        $this->assertTrue($result);
        
        // make sure that $unset_course is unset
        $unset_course_tmp = $unset_course;
        $unset_course_tmp['url'] = null;
        $result = $this->set_url($unset_course_tmp);       
        $this->assertTrue($result);

        // then try to sync them all
        $courses['set'] = $set_course;
        $courses['unset'] = $unset_course;
        $courses['invalid'] = $invalid_course;
        $this->myucla_urlupdater->sync_MyUCLA_urls($courses);

        // make sure that $unset_course has a success message
        $success = $this->myucla_urlupdater->successful;
        // make sure that $set_course's url was returned (meaning if existed already)
        $this->assertTrue($success['set'] == $set_course['url']);
        $this->assertTrue(false !== strpos($success['unset'] , myucla_urlupdater::expected_success_message));

        // make sure that $invalid_course has an error message
        $failed = $this->myucla_urlupdater->failed;
        $this->assertTrue(false === strpos($failed['invalid'] , myucla_urlupdater::expected_success_message));
    }
    
    
    // helper functions

    /**
     * Gets url from MyUCLA for given course.
     * 
     * @param mixed $url    Expects url to have following keys:
     *                      term, srs, url
     * @return string       Returns url from MyUCLA service call 
     */
    protected function get_url($course) {
        // this expects a multi-dimensonal array
        $result = $this->myucla_urlupdater->send_MyUCLA_urls(array($course));        
        return array_pop($result);  // returns indexed array
    }    
    
    /**
     * Sends given course and url to MyUCLA.
     * 
     * @param mixed $url    Expects url to have following keys:
     *                      term, srs, url
     * @return boolean      Returns true if URL was set, otherwise false. 
     */
    protected function set_url($course) {
        // this expects a multi-dimensonal array
        $result = $this->myucla_urlupdater->send_MyUCLA_urls(array($course), true);        
        return false !== strpos(array_pop($result), myucla_urlupdater::expected_success_message);
    }
    
    // setup/teardown functions
    public function setUp() {
        // ...
        $this->myucla_urlupdater = new myucla_urlupdater();
    }

    public function tearDown() {
        $this->myucla_urlupdater = null;
    }    
}
?>