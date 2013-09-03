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
     * Helper method to create an entry for the mocked registrar call to 
     * ccle_courseinstructorsget.
     * 
     * @param string $term
     * @param string $srs
     * @param string $rolecode
     * @param object $user
     *
     * @return array
     */
    private function create_ccle_courseinstructorsget_entry($term, $srs, $rolecode, $user) {
            return array('term' => $term,
                         'srs' => $srs,
                         'role' => $rolecode,
                         'ucla_id' => $user->idnumber,
                         'first_name_person' => $user->firstname,
                         'last_name_person' => $user->lastname,
                         'bolid' => str_replace('@ucla.edu', '', $user->username),
                         'ursa_email' => $user->email);
    }

    /**
     * Helper method to check if given user with given user name is enrolled
     * in a course or not.
     *
     * @param int $courseid
     * @param string $username
     */
    private function is_username_enrolled($courseid, $username) {
        global $DB;

        $context = context_course::instance($courseid);
        $user = $DB->get_record('user', array('username' => $username));
        if (empty($user)) {
            return false;
        }

        return is_enrolled($context, $user);
    }

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
        @$retval = $this->mockregdata[$sp][$term][$srs];
        return $retval;
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
        set_config('localuserfield', 'idnumber', 'enrol_database');
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

        // Purge usermapping cache.
        $cache = cache::make('local_ucla', 'usermappings');
        $cache->purge();
    }

    /**
     * Make sure createorfinduser throws an exception if it finds multiple
     * records for a given idnumber.
     *
     * Note, cannot make a test case if there are multiple username names
     * returned, because username plus mnet_localhost_id is a unique key.
     *
     * @expectedException dml_multiple_records_exception
     */
    public function test_createorfinduser_exception_idnumber() {
        $fieldmappings = array('uid'        => 'idnumber',
                               'firstname'  => 'firstname',
                               'lastname'   => 'lastname',
                               'email'      => 'email',
                               'username'   => 'username');

        // Create duplicate idnumbers.
        $uclauser = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_user(
                        array('idnumber' => '123456789'));
        $uclauser = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_user(
                        array('idnumber' => '123456789'));
        $enrollment = array();
        foreach ($fieldmappings as $key => $value) {
            $enrollment[$key] = $uclauser->$value;
        }
        $enrollment['username'] = '';

        $this->mockenrollmenthelper->createorfinduser($enrollment);
    }

    /**
     * Make sure createorfinduser can find an existing user.
     */
    public function test_createorfinduser_existing() {
        $fieldmappings = array('uid'        => 'idnumber',
                               'firstname'  => 'firstname',
                               'lastname'   => 'lastname',
                               'email'      => 'email',
                               'username'   => 'username');

        // Find a UCLA user with idnumber and username set.
        $uclauser = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_user();
        $enrollment = array();
        foreach ($fieldmappings as $key => $value) {
            $enrollment[$key] = $uclauser->$value;
        }

        // Make sure cache is working.
        $cache = cache::make('local_ucla', 'usermappings');
        $cachekey = sprintf('idnumber:%s:username:%s',
                $enrollment['uid'], $enrollment['username']);
        $result = $cache->get($cachekey);
        $this->assertFalse($result);

        // Make sure exact user is found.
        $founduser = $this->mockenrollmenthelper->createorfinduser($enrollment);
        $this->assertNotNull($founduser);
        foreach ($fieldmappings as $key => $value) {
            $this->assertEquals($uclauser->$value, $founduser->$value);
        }
        $result = $cache->get($cachekey);
        $this->assertEquals($result, $founduser);
    }

    /**
     * Make sure createorfinduser returns null for a nonexisting enrollment
     * record that is missing the username.
     */
    public function test_createorfinduser_invalid() {
        $enrollment = array('uid'        => '123456789',
                            'firstname'  => 'Joe',
                            'lastname'   => 'Bruin',
                            'email'      => 'test@ucla.edu',
                            'username'   => '');

        // Make sure cache is working.
        $cache = cache::make('local_ucla', 'usermappings');
        $cachekey = sprintf('idnumber:%s:username:%s',
                $enrollment['uid'], $enrollment['username']);
        $result = $cache->get($cachekey);
        $this->assertFalse($result);

        $founduser = $this->mockenrollmenthelper->createorfinduser($enrollment);
        $this->assertNull($founduser);
        $result = $cache->get($cachekey);
        $this->assertEquals($result, $founduser);
    }

    /**
     * Make sure createorfinduser creates a non-existing user.
     */
    public function test_createorfinduser_nonexisting() {
        $fieldmappings = array('uid'        => 'idnumber',
                               'firstname'  => 'firstname',
                               'lastname'   => 'lastname',
                               'email'      => 'email',
                               'username'   => 'username');

        $enrollment = array('uid'        => '123456789',
                            'firstname'  => 'Joe',
                            'lastname'   => 'Bruin',
                            'email'      => 'test@ucla.edu',
                            'username'   => 'joe.bruin@ucla.edu');

        // Make sure cache is working.
        $cache = cache::make('local_ucla', 'usermappings');
        $cachekey = sprintf('idnumber:%s:username:%s',
                $enrollment['uid'], $enrollment['username']);
        $result = $cache->get($cachekey);
        $this->assertFalse($result);

        // Make sure user is created.
        $founduser = $this->mockenrollmenthelper->createorfinduser($enrollment);
        $this->assertNotNull($founduser);
        foreach ($fieldmappings as $key => $value) {
            $this->assertEquals($enrollment[$key], $founduser->$value);
        }
        $result = $cache->get($cachekey);
        $this->assertEquals($result, $founduser);
    }

    /**
     * Call enrol_database_plugin->sync_enrolments() and make sure that it 
     * adds the database enrollment plugin for given set of courses that do
     * not already have it.
     */
    public function test_enrol_database_plugin_add_instance() {
        // Add mocked enrollment helper.
        $enrol = enrol_get_plugin('database');
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
     * Test syncing of a given roster (instructors and students) including
     * enrolling/unenrolling.
     *
     * @group totest
     */
    public function test_enrol_database_plugin_sync_enrolments() {
        // Add mocked enrollment helper.
        $enrol = enrol_get_plugin('database');
        $enrol->enrollmenthelper = $this->mockenrollmenthelper;

        $class = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class(
                        array('term' => '13S', 'subj_area' => 'MATH', 'division' => 'PS'));
        $course = array_pop($class);
        $term = $course->term;
        $srs = $course->srs;
        $courseid = $course->courseid;

        $courseusernames = array();

        // Create enrollment records, first for instructors. Let system find
        // Instructor, but create the TA.
        $instructor = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->
                create_user(array('idnumber' => '123456789',
                                  'username' => 'instructor@ucla.edu'));
        $courseusernames[] = $instructor->username;
        $ta = new stdClass();
        $ta->firstname = 'Joe';
        $ta->lastname = 'Bruin';
        $ta->username = 'ta@ucla.edu';
        $ta->email = 'ta@ucla.edu';
        $ta->idnumber = '987654321';
        $courseusernames[] = $ta->username;

        $reginstructors = array();
        $reginstructors[] = $this->create_ccle_courseinstructorsget_entry(
                    $term, $srs, '01', $instructor);
        $reginstructors[] = $this->create_ccle_courseinstructorsget_entry(
                    $term, $srs, '02', $ta);
        $this->set_mockregdata('ccle_courseinstructorsget', $term, $srs, $reginstructors);

        // Make sure that users are currently not enrolled.
        foreach ($courseusernames as $username) {
            $result = $this->is_username_enrolled($courseid, $username);
            $this->assertFalse($result);
        }

        // Run sync_enrolments and magically all users should be enrolled.
        $enrol->sync_enrolments($this->trace, array('13S'));
        foreach ($courseusernames as $username) {
            $result = $this->is_username_enrolled($courseid, $username);
            $this->assertTrue($result);
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
        foreach ($instructors as $rolecode => $role) {
            $reginstructors[] = $this->create_ccle_courseinstructorsget_entry(
                    $term, $srs, $rolecode, $role);
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
        foreach ($enrollments as $enrollment) {
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
     * Make sure that get_requested_roles returns an properly formatted array of
     * users and their roles.
     */
    public function test_get_requested_roles() {
        global $DB;
        $class = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_class(array());

        // Create results to use for ccle_courseinstructorsget.
        $course = array_pop($class);
        $term = $course->term;
        $srs = $course->srs;

        // Create Instructor and Supervising instructor.
        $instructor = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_user();
        $supervisinginstructor = $this->getDataGenerator()->
                get_plugin_generator('local_ucla')->create_user();
        $instructors = array('01' => $instructor,
                             '03' => $supervisinginstructor);
        foreach ($instructors as $rolecode => $role) {
            $reginstructors[] = $this->create_ccle_courseinstructorsget_entry(
                    $term, $srs, $rolecode, $role);
        }
        $this->set_mockregdata('ccle_courseinstructorsget', $term, $srs, $reginstructors);

        $courseobj = $DB->get_record('course', array('id' => $course->courseid));
        $returnedroles = $this->mockenrollmenthelper->get_requested_roles($courseobj);

        // Make sure instructors are returned.
        foreach ($instructors as $rolecode => $expected) {
            foreach ($returnedroles as $userid => $roles) {
                if ($expected->id == $userid) {
                    if ($rolecode == '01') {
                        // Returned roleid should be for editinginstructor.
                        $roleid = $DB->get_field('role', 'id',
                                array('shortname' => 'editinginstructor'));
                    } else if ($rolecode == '03') {
                        $roleid = $DB->get_field('role', 'id',
                                array('shortname' => 'supervising_instructor'));
                    }
                    $true = in_array($roleid, $roles);
                    $this->assertTrue($true);
                }
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
