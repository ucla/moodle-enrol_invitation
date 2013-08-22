<?php
// This file is part of Moodle - http://moodle.org/
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
 * UCLA data generator tests.
 *
 * @package    local_ucla
 * @category   phpunit
 * @copyright  2013 UC Regents
*/

defined('MOODLE_INTERNAL') || die();

// @todo Include local_ucla generator code, because "getDataGenerator" does not
// yet work for local plugins. When local plugins are support, please change
// $generator = new local_ucla_generator();
// to
// $generator = $this->getDataGenerator()->get_plugin_generator('local_ucla');
global $CFG;
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/local/ucla/tests/generator/lib.php');

/**
 * PHPUnit data generator testcase
 *
 * @package    local_ucla
 * @category   phpunit
 * @copyright  2013 UC Regents
 */
class local_ucla_generator_testcase extends advanced_testcase {

    /**
     * Try to pass an array of two empty arrays to tell the random class creator
     * to create a random crosslisted class.
     */
    public function test_create_class_empty_crosslisted() {
        global $DB;
        
        $beforecourse = $DB->count_records('course');
        $beforerequest = $DB->count_records('ucla_request_classes');
        $beforeclassinfo = $DB->count_records('ucla_reg_classinfo');

        $param = array(array(), array());
        $createdclass = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class($param);
        $this->assertFalse(empty($createdclass));

        $aftercourse = $DB->count_records('course');
        $afterrequest = $DB->count_records('ucla_request_classes');
        $afterclassinfo = $DB->count_records('ucla_reg_classinfo');

        $this->assertEquals($beforecourse+1, $aftercourse);
        $this->assertEquals($beforerequest+2, $afterrequest);
        $this->assertEquals($beforeclassinfo+2, $afterclassinfo);
    }

    /**
     * Try to create a duplicate class.
     *
     * @expectedException dml_exception
     */
    public function test_create_class_exception_manual() {
        $param = array('term' => '13F', 'srs' => '262508200',
                       'subj_area' => 'MATH', 'crsidx' => '0135    ',
                       'secidx' => ' 001  ', 'division' => 'PS');
        $this->getDataGenerator()->get_plugin_generator('local_ucla')
                ->create_class($param);
        // This should raise an exception.
        $this->getDataGenerator()->get_plugin_generator('local_ucla')
                ->create_class($param);
    }

    /**
     * Try to create a randomly created class.
     */
    public function test_create_class_random() {
        global $DB;

        $beforecourse = $DB->count_records('course');
        $beforerequest = $DB->count_records('ucla_request_classes');
        $beforeclassinfo = $DB->count_records('ucla_reg_classinfo');

        $createdclass = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class();
        $this->assertFalse(empty($createdclass));

        $aftercourse = $DB->count_records('course');
        $afterrequest = $DB->count_records('ucla_request_classes');
        $afterclassinfo = $DB->count_records('ucla_reg_classinfo');

        $this->assertGreaterThan($beforecourse, $aftercourse);
        $this->assertGreaterThan($beforerequest, $afterrequest);
        $this->assertGreaterThan($beforeclassinfo, $afterclassinfo);
    }

    /**
     * Try to create a bunch of classes for a given term.
     */
    public function test_create_class_term() {
        global $DB;

        $numcourses = $DB->count_records('course');
        $numrequests = $DB->count_records('ucla_request_classes');
        $numclassinfos = $DB->count_records('ucla_reg_classinfo');

        // Generate a class with all fields defined.
        $param = array('term' => '13F', 'srs' => '262508200',
                       'subj_area' => 'MATH', 'crsidx' => '0135    ',
                       'secidx' => ' 001  ', 'division' => 'PS');
        $createdclass = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class($param);
        $this->assertFalse(empty($createdclass));
        $aftercourse = $DB->count_records('course');
        $afterrequest = $DB->count_records('ucla_request_classes');
        $afterclassinfo = $DB->count_records('ucla_reg_classinfo');
        $this->assertEquals($numcourses+1, $aftercourse);
        $this->assertEquals($numrequests+1, $afterrequest);
        $this->assertEquals($numclassinfos+1, $afterclassinfo);
        $numcourses = $aftercourse;
        $numrequests = $afterrequest;
        $numclassinfos = $afterclassinfo;

        // Generate a crosslisted class with all fields defined.
        $param = array(
            array('term' => '13F', 'srs' => '285061200',
                'subj_area' => 'NR EAST', 'crsidx' => '0020  M ',
                'secidx' => ' 001  ', 'division' => 'HU'),
            array('term' => '13F', 'srs' => '257060200',
                'subj_area' => 'ASIAN', 'crsidx' => '0020  M ',
                'secidx' => ' 001  ', 'division' => 'HU'));
        $createdclass = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class($param);
        $this->assertFalse(empty($createdclass));
        $aftercourse = $DB->count_records('course');
        $afterrequest = $DB->count_records('ucla_request_classes');
        $afterclassinfo = $DB->count_records('ucla_reg_classinfo');
        $this->assertEquals($numcourses+1, $aftercourse);
        $this->assertEquals($numrequests+2, $afterrequest);
        $this->assertEquals($numclassinfos+2, $afterclassinfo);
        $numcourses = $aftercourse;
        $numrequests = $afterrequest;
        $numclassinfos = $afterclassinfo;

        // Make sure that all created courses belong to 13F.
        $terms = $DB->get_fieldset_select('ucla_request_classes', 'term',
                '', array());
        foreach ($terms as $term) {
            $this->assertEquals('13F', $term);
        }

        // Generate a random non-crosslisted class.
        $param = array(array('term' => '13F'));
        $createdclass = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class($param);
        $this->assertFalse(empty($createdclass));
        $aftercourse = $DB->count_records('course');
        $afterrequest = $DB->count_records('ucla_request_classes');
        $afterclassinfo = $DB->count_records('ucla_reg_classinfo');
        $this->assertEquals($numcourses+1, $aftercourse);
        $this->assertEquals($numrequests+1, $afterrequest);
        $this->assertEquals($numclassinfos+1, $afterclassinfo);
        $numcourses = $aftercourse;
        $numrequests = $afterrequest;
        $numclassinfos = $afterclassinfo;

        // Make sure that all created courses belong to 13F.
        $terms = $DB->get_fieldset_select('ucla_request_classes', 'term',
                '', array());
        foreach ($terms as $term) {
            $this->assertEquals('13F', $term);
        }

        // Generate a random crosslisted class.
        $param = array(array('term' => '13F'), array('term' => '13F'));
        $createdclass = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class($param);
        $this->assertFalse(empty($createdclass));
        $aftercourse = $DB->count_records('course');
        $afterrequest = $DB->count_records('ucla_request_classes');
        $afterclassinfo = $DB->count_records('ucla_reg_classinfo');
        $this->assertEquals($numcourses+1, $aftercourse);
        $this->assertEquals($numrequests+2, $afterrequest);
        $this->assertEquals($numclassinfos+2, $afterclassinfo);
        $numcourses = $aftercourse;
        $numrequests = $afterrequest;
        $numclassinfos = $afterclassinfo;

        // Make sure that all created courses belong to 13F.
        $terms = $DB->get_fieldset_select('ucla_request_classes', 'term',
                '', array());
        foreach ($terms as $term) {
            $this->assertEquals('13F', $term);
        }
    }    

    /**
     * Test creating a user.
     */
    public function test_create_user() {
        $user = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_user();
        $this->assertNotEmpty($user->username);
        $this->assertNotEmpty($user->idnumber);

        // Create user with predefined username and idnumbers.
        $presetuser['username'] = 'test@ucla.edu';
        $presetuser['idnumber'] = '123456789';
        $user = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_user($presetuser);
        $this->assertNotEmpty($user->username);
        $this->assertNotEmpty($user->idnumber);
        $this->assertDebuggingNotCalled();

        // Create user with improperly predefined username and idnumbers.
        $improperusername['username'] = 'test';
        $user = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_user($improperusername);
        $this->assertDebuggingCalled('Given username does not end with @ucla.edu');
        $improperidnumber['idnumber'] = '12345678';
        $user = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_user($improperidnumber);
        $this->assertDebuggingCalled('Given idnumber is not 9 digits long');
    }

    protected function setUp() {
        $this->resetAfterTest(true);
    } 
}
