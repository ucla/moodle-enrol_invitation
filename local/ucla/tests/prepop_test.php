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
 * Tests the UCLA modifications to the external database enrollment plugin
 * involved in doing pre-pop enrollment.
 *
 * @package    local_ucla
 * @category   phpunit
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class local_ucla_prepop_testcase extends advanced_testcase {
    /**
     * Mapping of role shortname to roleid.
     * @var array
     */
    private $createdroles = array();

    /**
     * Stores mocked version of local_ucla_enrollment_helper.
     * @var local_ucla_enrollment_helper
     */
    private $mockenrollmenthelper = null;    

    /**
     * Used by mocked_query_registrar to return data for a given stored 
     * procedure, term, and srs.
     * @var array
     */
    private $mockregdata = array();

    /**
     * Stores the trace object.
     * @var null_progress_trace
     */
    private $trace = null;

    /**
     * Stubs the query_registrar method of local_ucla_enrollment_helper class,
     * so we aren't actually making a live call to the Registrar.
     *
     * Must call set_mockregdata() beforehand to set what data should be
     * returned.
     *
     * @param string $sp        Stored procedure to call.
     * @param string $term
     * @param string $srs
     *
     * @return array            Returns corresponding value in $mockregdata.
     */
    public function mocked_query_registrar($sp, $term, $srs) {
        /* The $mockregdata array is indexed as follows:
         *  [storedprocedure] => [term] => [srs] => [array of results]
         */
        return $this->mockregdata[$sp][$term][$srs];
    }

    /**
     * Prepares data that will be returned by mocked_query_registrar.
     *
     * @param string $sp
     * @param string $term
     * @param string $srs
     * @param array $results
     */
    protected function set_mockregdata($sp, $term, $srs, array $results) {
        $this->mockregdata[$sp][$term][$srs] = $results;
    }

    /**
     * Create UCLA roles that will be needed for Registrar role mapping.
     */
    protected function setUp() {
        $this->resetAfterTest(true);

        // Create roles every unit test, because database is wiped clean.
        $uclagenerator = $this->getDataGenerator()->get_plugin_generator('local_ucla');
        $this->createdroles = $uclagenerator->create_ucla_roles();

        // For some very odd reason, there is no API to "enable" an enrollment
        // plugin, so need to do these steps to activate the database plugin.
        $enabled = enrol_get_plugins(true);
        $enabled = array_keys($enabled);
        $enabled[] = 'database';
        set_config('enrol_plugins_enabled', implode(',', $enabled));
        context_system::instance()->mark_dirty(); // Resets all enrol caches.

        // These configs are normally hardcoded in local/ucla/config, but
        // PHPUnit tests do not load up these values, so we manually set them.
        set_config('dbtype', 'odbc_mssql', 'enrol_database');
        set_config('remoteenroltable', 'enroll2_test', 'enrol_database');
        set_config('remotecoursefield', 'termsrs', 'enrol_database');
        set_config('remoterolefield', 'role', 'enrol_database');
        set_config('remoteuserfield', 'uid', 'enrol_database');
        set_config('localcoursefield', 'id', 'enrol_database');
        set_config('localrolefield', 'id', 'enrol_database');
        set_config('unenrolaction', ENROL_EXT_REMOVED_UNENROL, 'enrol_database');
        set_config('overrideenroldatabase', 1, 'local_ucla');

        // Create mocked version of the local_ucla_enrollment_helper.

        // For debugging, use text_progress_trace(), otherwise prevent text
        // output by using null_progress_trace().
        //$this->trace = new text_progress_trace();
        $this->trace = new null_progress_trace();
        $enrol = enrol_get_plugin('database');

        // Only stub the query_registrar method.
        $this->mockenrollmenthelper = $this->getMockBuilder('local_ucla_enrollment_helper')
                ->setConstructorArgs(array($this->trace, $enrol))
                ->setMethods(array('query_registrar'))
                ->getMock();

        // Method $this->mocked_query_registrar will be called instead of
        // local_ucla_enrollment_helper->query_registrar.
        $this->mockenrollmenthelper->expects($this->any())
                ->method('query_registrar')
                ->will($this->returnCallback(array($this, 'mocked_query_registrar')));

        // Remove any previous registrar data.
        unset($this->mockregdata);
        $this->mockregdata = array();
    }

    /**
     * Try to free up some memory.
     */
    public function tearDown() {
        unset($this->trace);
    }

    /**
     * Call enrol_database_plugin->sync_enrolments() and make sure that it 
     * adds the database enrollment plugin for given set of courses that do
     * not already have it.
     */
    public function test_enrol_database_plugin_add_instance() {
        $enrol = enrol_get_plugin('database');

        // Add mocked enrollment helper.
        $enrol->enrollmenthelper = $this->mockenrollmenthelper;

        $class = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class(
                        array('term' => '13S'));
        $course = array_pop($class);
        
        // Just created course, so shouldn't have database enrollment plugin.
        $instances = enrol_get_instances($course->courseid, true);
        $foundenroldatabase = false;
        foreach ($instances as $instance) {
            if ($instance->enrol == 'database') {
                $foundenroldatabase = true;
                break;
            }
        }
        $this->assertFalse($foundenroldatabase);

        // Call sync for 1 course.
        $enrol->sync_enrolments($this->trace, $course->courseid);

        // Should have enrollment plugin added.
        $instances = enrol_get_instances($course->courseid, true);
        $foundenroldatabase = false;
        foreach ($instances as $instance) {
            if ($instance->enrol == 'database') {
                $foundenroldatabase = true;
                break;
            }
        }
        $this->assertTrue($foundenroldatabase);

        // Now, make sure database enrollment plugin is added if we are
        // processing courses for a term.
        $class = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class(
                        array('term' => '13W'));
        $courseidsexpected[] = array_pop($class)->courseid;
        $class = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class(
                        array('term' => '13W'), array('term' => '13W'));
        $courseidsexpected[] = array_pop($class)->courseid;

        // Just created courses, so shouldn't have database enrollment plugin.
        foreach ($courseidsexpected as $courseid) {
            $instances = enrol_get_instances($courseid, true);
            $foundenroldatabase = false;
            foreach ($instances as $instance) {
                if ($instance->enrol == 'database') {
                    $foundenroldatabase = true;
                    break;
                }
            }
            $this->assertFalse($foundenroldatabase);
        }

        // Call sync for a term.
        $enrol->sync_enrolments($this->trace, array('13W'));

        // Make sure those courses have the database plugin enabled.
        foreach ($courseidsexpected as $courseid) {
            $instances = enrol_get_instances($courseid, true);
            $foundenroldatabase = false;
            foreach ($instances as $instance) {
                if ($instance->enrol == 'database') {
                    $foundenroldatabase = true;
                    break;
                }
            }
            $this->assertTrue($foundenroldatabase);
        }
    }

    /**
     * Make sure that get_external_enrollment_courses returns the proper data.
     */
    public function test_get_external_enrollment_courses() {
        $courseidsexpected = array();

        // Create non-crosslist course.
        $class = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class(
                        array('term' => '13S'));
        $courseidsexpected[] = array_pop($class)->courseid;

        // Create crosslist course.
        $class = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class(
                        array('term' => '13S'), array('term' => '13S'));
        $courseidsexpected[] = array_pop($class)->courseid;

        // Expecting to find all courseids above in result array.
        $this->mockenrollmenthelper->set_run_parameter(array('13S'));
        $results = $this->mockenrollmenthelper->get_external_enrollment_courses();
        $courseidsreturned = array_keys($results);
        sort($courseidsexpected);
        sort($courseidsreturned);
        $this->assertEquals($courseidsexpected, $courseidsreturned);

        // Create courses in another term and check they are returned.
        $class = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class(
                        array('term' => '131'));
        $courseidsexpected[] = array_pop($class)->courseid;
        $class = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class(
                        array('term' => '131'), array('term' => '131'));
        $courseidsexpected[] = array_pop($class)->courseid;

        // Expecting to find all courseids above in result array.
        $this->mockenrollmenthelper->set_run_parameter(array('13S', '131'));
        $results = $this->mockenrollmenthelper->get_external_enrollment_courses();
        $courseidsreturned = array_keys($results);
        sort($courseidsexpected);
        sort($courseidsreturned);
        $this->assertEquals($courseidsexpected, $courseidsreturned);
    }

    /**
     * Test adding enrollment for a course for users that are already existing
     * in the system.
     */
    public function test_get_instructors() {
        // Get a non-crosslisted class.
        $class = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class(array());

        // Create instructors to enroll. Avoid TA role here, because for some
        // subject areas it maps to either taadmin or ta.
        $instructor = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_user();
        $supervisinginstructor = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_user();

        // Index by role code.
        $instructors = array('01' => $instructor,
                             '03' => $supervisinginstructor);

        // Create results to use for ccle_courseinstructorsget.
        $entry = array_pop($class);
        $term = $entry->term;
        $srs = $entry->srs;
        $courseid = $entry->courseid;
        foreach ($instructors as $rolecode => $role) {
            $reginstructors[] = array('term' => $term,
                                      'srs' => $srs,
                                      'role' => $rolecode,
                                      'ucla_id' => $role->idnumber,
                                      'first_name_person' => $role->firstname,
                                      'last_name_person' => $role->lastname,
                                      'bolid' => str_replace('@ucla.edu', '', $role->username),
                                      'ursa_email' => $role->email);
        }
        $this->set_mockregdata('ccle_courseinstructorsget', $term, $srs, $reginstructors);

        // Get data to send query get_instructors().
        $termcourses = ucla_get_courses_by_terms(array($term));

        // Should only be one, since we only created 1 course.
        $this->assertEquals(1, count($termcourses));
        $requestclasses = array_pop($termcourses);

        $enrollments = $this->mockenrollmenthelper->get_instructors($requestclasses);
        $this->assertNotEmpty($enrollments);

        // Make sure users return have proper roles and proper data returned.
        foreach ($enrollments[$courseid] as $enrollment) {
            if ($enrollment['uid'] == $instructor->idnumber) {
                $this->assertEquals($this->createdroles['editinginstructor'],
                        $enrollment['role']);
                $this->assertEquals($enrollment['username'], $instructor->username);
            } else if ($enrollment['uid'] == $supervisinginstructor->idnumber) {
                $this->assertEquals($this->createdroles['supervising_instructor'],
                        $enrollment['role']);
                $this->assertEquals($enrollment['username'], $supervisinginstructor->username);
            }
        }
    }

    /**
     * Test that we are not processing dummy "THE STAFF" or TA accounts.
     */
    public function test_nodummy() {
        $class = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class();

        // Create results to use for ccle_courseinstructorsget.
        $entry = array_pop($class);
        $term = $entry->term;
        $srs = $entry->srs;

        // Dummy instructor.
        $reginstructors[] = array('term' => $term,
                                  'srs' => $srs,
                                  'role' => '01',
                                  'ucla_id' => '100399990',
                                  'first_name_person' => 'THE STAFF');
        // Dummy TA.
        $reginstructors[] = array('term' => $term,
                                  'srs' => $srs,
                                  'role' => '02',
                                  'ucla_id' => '200399999',
                                  'first_name_person' => 'TA');
        $this->set_mockregdata('ccle_courseinstructorsget', $term, $srs, $reginstructors);

        // Get data to send query get_instructors().
        $termcourses = ucla_get_courses_by_terms(array($term));

        // Should only be one, since we only created 1 course.
        $this->assertEquals(1, count($termcourses));
        $requestclasses = array_pop($termcourses);

        $enrollments = $this->mockenrollmenthelper->get_instructors($requestclasses);
        $this->assertEmpty($enrollments);
    }
}
