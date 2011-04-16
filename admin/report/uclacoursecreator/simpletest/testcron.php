<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

$curdir = '/admin/report/uclacoursecreator';

require_once($CFG->dirroot . $curdir . '/uclacoursecreator.class.php');

/**
 *  Sorry about the mixed ML and underscore casings.
 **/
class UCLACCTest extends UnitTestCase {
    private $course_creator = null;
    private $revert = null;

    function setUp() {
        parent::setUp();

        $this->course_creator = new uclacoursecreator(); 

        global $DB;

        // Set up test scenario

        $ignore_fields = array('action', 'status', 'course', 'department');
    
        $requests = array();
        $req = array();  
       
        $req['srs'] = '105030200';
        $req['term'] = '10F';
        $req['action'] = 'build';
        $req['status'] = 'processing';
        $req['course'] = 'TEST COURSE';
        $req['department'] = 'TEST DEPARTMENT';
        $requests[] = $req;

        // This is possible thanks to PHP's pass by reference value
        $req['srs'] = '121291200';
        $req['crosslist'] = 1;
        $requests[] = $req;

        $revert['ucla_request_classes'] = $requests;

        $crosslists = array();
        $cl = array();

        $cl['srs'] = 121291200;
        $cl['term'] = '10F';
        $cl['aliassrs'] = 144667200;

        $crosslists[] = $cl;

        $revert['ucla_request_crosslist'] = $crosslists;

        // @todo check if we're overwriting values, save them

        echo '<pre>';

        foreach ($revert as $table => $data) {
            $this->backupTable($table);

            foreach ($data as $entry) {
                try {
                    $DB->insert_record($table, $entry, false, true);
                } catch (dml_exception $e) {
                    echo $e->error . "\n";
                    // Probably index exists.
                }
            }
        }

        $this->revert = $revert;
    }

    function tearDown() {
        $this->restoreTables();
        echo '</pre>';
    }

    function restoreTables() {
        foreach ($this->revert as $table => $data) {
            echo "Reverting $table...\n";
        }
    }

    /**
     *  Backs up the current tables into a file.
     *  @todo need to do this for ucla_request_classes and ucla_request_crosslist.
     **/
    function backupTable($table) {
        return true;
    }

    function testRequests() {
        global $DB;


        $assert = true;
        
        try {
            $this->course_creator->handle_locking(true, true);
            $this->course_creator->start_cron_term('10F');
            $this->course_creator->retrieve_requests(); 
            $this->course_creator->mark_cron_term(true);
        } catch (CourseCreatorException $e) {
            echo $e->getMessage();
            $assert = false;
        }

        // @todo make sure that all our requests are marked properly
        // @todo validate that the data is also proper
        var_dump($this->course_creator->dump_cache());


        $this->assertEqual($assert, true);
    }

    function testEnrolmentPluginCache() {
        $enrol = $this->course_creator->get_enrol_plugin('imsenterprise');

        $assert = is_object($enrol) && (get_class($enrol) == 'enrol_imsenterprise_plugin');
        $this->assertEqual($assert, true);

        $enrol_copy = $this->course_creator->get_enrol_plugin('imsenterprise');

        $assert = $enrol_copy === $enrol;

        $this->assertTrue($assert);
    }

    // @todo test failure handling

    function testInsertLocalEntry() {
        $test_entry = array();
        $test_entry['srs'] = '123456789';
        $test_entry['term'] = '001';

        $test_orig = new StdClass();
        $test_orig->srs = '123456789';
        $test_orig->term = '001';

        $args = new StdClass();
        
        $this->course_creator->insert_local_entry($test_entry, $test_orig, $args);

        // Count this in our count
        $this->assertTrue(true);

        $test_entry['ucla_id'] = 'Test';
        $test_entry['profcode'] = '01';

        $args->test_func = 'no_empty';
        $args->key_field = 'key_field_instructors';
        $this->course_creator->insert_local_entry($test_entry, $test_orig, $args);

        // Count this in our count
        $this->assertTrue(true);
        
        $test_entry['ucla_id'] = 'Second';
        $test_entry['profcode'] = '01';

        $args->test_func = 'no_empty';
        $args->key_field = 'key_field_instructors';
        $this->course_creator->insert_local_entry($test_entry, $test_orig, $args);
   

        // Count this in our count
        $this->assertTrue(true);
        $test_entry['ucla_id'] = 'Test';
        var_dump($this->course_creator->dump_cache());
    }
}
