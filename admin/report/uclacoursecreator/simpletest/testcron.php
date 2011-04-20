<?php
// This file is a part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *  A Unit test for uclacoursecreator.
 *
 *  This test is primarily a temporary testing component for the class
 *  uclacoursecreator. It will test to make sure that certain parts
 *  of the implementation works correctly.
 *  Eventually this will test the happy path and all other extreme cases.
 *
 *  @package ucla
 *  @subpackage course_creator
 *  @copyright 2011 UCLA
 **/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

$curdir = '/admin/report/uclacoursecreator';

require_once($CFG->dirroot . $curdir . '/uclacoursecreator.class.php');

/**
 *  Sorry about the mixed ML and underscore casings.
 **/
class UCLACCTest extends UnitTestCase {
    // This contains our course_creator object
    private $course_creator = null;

    // This contains the tables we wish to revert
    private $revert = null;

    // Maybe if code coverage is included
    public static $includecoverage = array('admin/report/uclacoursecreator/uclacoursecreator.class.php');

    function setUp() {
        parent::setUp();

        $this->course_creator = new uclacoursecreator(); 

        // Force no log file
        $this->course_creator->set_debug(false);

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
            echo "Reverting $table... (current unimplemented)\n";
        }
    }

    /**
     *  Backs up the current tables into a file.
     *  @todo need to do this for ucla_request_classes and ucla_request_crosslist.
     **/
    function backupTable($table) {
        // @todo copy the table into a file
        // @todo empty the table
        return true;
    }

    function testRequests() {
        global $DB;

        $assert = true;
        
        try {
            // @todo Get this to use a more independent model, maybe move into setUp
            $this->course_creator->handle_locking(true, false);
            $this->course_creator->start_cron_term('10F');

            // actual test
            $this->course_creator->retrieve_requests(); 

            // try to make sure the state is not affected...
            $this->course_creator->mark_cron_term(true);
            $this->course_creator->handle_locking(false, false);
        } catch (CourseCreatorException $e) {
            echo $e->getMessage();
            $assert = false;
        }

        // Using our revert data, we're going to do magic
        // Actually, we're going to validate that all our requests were properly accepted
        $cc = $this->course_creator->dump_cache();

        $req_keys = array_keys($cc['requests']);

        foreach ($this->revert['ucla_request_classes'] as $test_case) {
            $contains = in_array($test_case['srs'], $req_keys);
            $this->assertTrue($contains, 'For some reason ' . $test_case['srs'] . ' was not found in object');
        }

        $count = count($cc['requests']);
        $goal_count = count($this->revert['ucla_request_classes']);
        $this->assertEqual($count, $goal_count);

        // Final finishing message
        $this->assertTrue($assert, 'Finished testing retrieve_requests');
    }

    function testEnrolmentPluginCache() {
        $enrol = $this->course_creator->get_enrol_plugin('imsenterprise');

        $assert = is_object($enrol) && (get_class($enrol) == 'enrol_imsenterprise_plugin');
        $this->assertTrue($assert, 'Tested that we have instanciated ims properly');

        $enrol_copy = $this->course_creator->get_enrol_plugin('imsenterprise');

        $assert = $enrol_copy === $enrol;

        $this->assertTrue($assert, 'Finished testing imsenterprise loading');
    }

    // @todo test failure handling

    /**
     *  Testing a complicated sub-routine.
     **/
    function testInsertLocalEntry() {
        $test_entry = array();
        $test_entry['srs'] = '123456789';
        $test_entry['term'] = '001';

        $test_orig = new StdClass();
        $test_orig->srs = '123456789';
        $test_orig->term = '001';

        $args = new StdClass();
        
        $this->course_creator->insert_local_entry($test_entry, $test_orig, $args);

        $cc = $this->course_creator->dump_cache();
        
        $val = isset($cc['term_rci'][$test_entry['srs']]);

        $this->assertTrue($val, 'Tested insert_local_entry for term_rci');
    
        // Remember that term and srs have carried over from before
        $test_entry['ucla_id'] = 'Test';
        $test_entry['profcode'] = '01';

        $args->test_func = 'no_empty';
        $args->key_field = 'key_field_instructors';
        $this->course_creator->insert_local_entry($test_entry, $test_orig, $args);

        $cc = $this->course_creator->dump_cache();
        
        $val = isset($cc['instructors'][$test_entry['srs']][$test_entry['ucla_id']]);

        $this->assertTrue($val, 'Tested insert_local_entry for ' . $args->key_field);
       
        // Another test
        $test_entry['ucla_id'] = 'Second';
        $test_entry['profcode'] = '01';

        $this->course_creator->insert_local_entry($test_entry, $test_orig, $args);
        
        $cc = $this->course_creator->dump_cache();
        
        $val = isset($cc['instructors'][$test_entry['srs']][$test_entry['ucla_id']]);

        $this->assertTrue($val, 'Tested insert_local_entry for ' . $args->key_field);

        // Testing that it catches problems
        $test_entry['srs'] = 'notmatch!';
        $nargs = new StdClass();

        $caught = false;

        try {
            $this->course_creator->insert_local_entry($test_entry, $test_orig, $nargs);
        } catch (CourseCreatorException $e) {
            $caught = true;
        }

        $this->assertTrue($caught, 'Testing test_entry catch unmatching');

        // Test that no_empty works
        $test_entry['srs'] = $test_orig->srs;
        $test_entry['ucla_id'] = 'fail';
        $test_entry['profcode'] = '';

        // Use $args, which specify instructor insert
        $this->course_creator->insert_local_entry($test_entry, $test_orig, $args);

        $cc = $this->course_creator->dump_cache();

        $val = isset($cc['instructors'][$test_entry['srs']][$test_entry['ucla_id']]);

        $this->assertFalse($val, 'Testing test_entry catch empty');
    }

    function testRetrieveRegistrarInfo() {
        $mockcc = new mock_uclacoursecreator();
    }
}

class mock_uclacoursecreator extends uclacoursecreator {
    function open_registrar_connection() {
        return new mock_odbc();
    }
}

class mock_odbc {
    function Execute($query) {
        global $CFG;
        if (!class_exists('ADORecordSet_array')) {
            require_once($CFG->libdir . '/adodb/adodb.inc.php');
        }

        // Blindly return some recordset
        $rs = new ADORecordSet_array();

        // @todo fill with hard-coded values
        $rs->InitArray(array(), array(), false);

        return $rs;
    }
}
