<?php
/**
 * Unit tests for uclacoursecreator.class.php.
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/uclacoursecreator/uclacoursecreator.class.php');
 
class uclacoursecreator_test extends UnitTestCase {    
    private $uclacoursecreator;
    
    /**
     * Try parse a valid email template
     */
    function test_valid_email_template() {
        $valid_template_file = dirname(__FILE__) . '/valid_email_template.txt';
        $result = $this->uclacoursecreator->email_parse_file($valid_template_file);
        
        $this->assertTrue(is_array($result));
        $this->assertEqual($result['from'], 'CCLE <ccle@ucla.edu>');
        $this->assertEqual($result['bcc'], 'Kearney, Deborah (dkearney@oid.ucla.edu)');
        $this->assertEqual($result['subject'], '#=nameterm=# #=coursenum-sect=# class site created');
        $this->assertFalse(empty($result['subject']));
    }
 
    // setup/teardown functions
    public function setUp() {
        // ...
        $this->uclacoursecreator = new uclacoursecreator();
    }

    public function tearDown() {
        $this->uclacoursecreator = null;
    }    
}
?>