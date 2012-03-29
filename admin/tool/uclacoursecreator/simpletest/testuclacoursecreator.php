<?php
/**
 * Unit tests for uclacoursecreator.class.php.
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/uclacoursecreator/uclacoursecreator.class.php');
 
class uclacoursecreator_test extends UnitTestCaseUsingDatabase {    
    private $uclacoursecreator;
 
    /**
     * Try to create a new category
     */
    function test_new_category() {
        global $DB;

        $this->switch_to_test_db(); // All operations until end of test method will happen in test DB
        
        // make sure that the necessary course tables are generated
        $this->create_test_table('cache_flags', 'lib'); 
        $this->create_test_table('config_plugins', 'lib'); 
        $this->create_test_table('context', 'lib'); 
        $this->create_test_table('context_temp', 'lib'); 
        $this->create_test_table('course', 'lib'); 
        $this->create_test_table('course_categories', 'lib'); 
        
        // be sure to create system context when playing with anything that 
        // touches context table
        $this->create_system_context_record();  
        
        // need to create a "frontcourse" so that fix_course_sortorder() works
        $frontcourse = new StdClass();
        $frontcourse->category = 0;
        $frontcourse->format = 'site';
        $frontcourse->shortname = 'frontpage';
        $DB->insert_record('course', $frontcourse);
        
        // create a parent category
        $this->uclacoursecreator->new_category('Parent');
        // make sure it exists
        $parent = $DB->get_record('course_categories', array('name' => 'Parent'));
        $this->assertFalse(empty($parent));
        $context = context_coursecat::instance($parent->id, true);
        $this->assertFalse(empty($context));
        
        // create a child category
        $this->uclacoursecreator->new_category('Child', $parent->id);
        // make sure it exists
        $child = $DB->get_record('course_categories', array('name' => 'Child'));
        $this->assertFalse(empty($child));
        $context = context_coursecat::instance($child->id, true);
        $this->assertFalse(empty($context));

        // now see if category paths are propery set
        fix_course_sortorder();
        $parent = $DB->get_record('course_categories', array('name' => 'Parent'));
        $this->assertFalse(empty($parent->path));
        $child = $DB->get_record('course_categories', array('name' => 'Child'));
        $this->assertFalse(empty($child->path));
    }
    
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
        parent::setUp();
        $this->uclacoursecreator = new uclacoursecreator();
    }

    public function tearDown() {
        parent::tearDown();
        $this->uclacoursecreator = null;
    }    
}
?>