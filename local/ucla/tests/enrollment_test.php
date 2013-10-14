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
 * involved in doing login-time/pre-pop enrollment.
 *
 * @package    local_ucla
 * @category   phpunit
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class local_ucla_enrollment_testcase extends advanced_testcase {
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
     * Helper method to create an entry for the mocked registrar call to
     * ccle_roster_class.
     *
     * @param string $term
     * @param string $srs
     * @param string $enrollmentcode
     * @param object $user
     *
     * @return array
     */
    private function create_ccle_roster_class_entry($term, $srs, $enrollmentcode, $user) {
            return array('term_cd' => $term,
                         'stu_id' => $user->idnumber,
                         'full_name_person' => sprintf('%s, %s', $user->lastname, $user->firstname),
                         'enrl_stat_cd' => $enrollmentcode,
                         'ss_email_addr' => $user->email,
                         'bolid' => str_replace('@ucla.edu', '', $user->username));
    }

    /**
     * Creates a temporary table to be used to fake the enroll2 table used
     * during login time enrollment.
     *
     * Code copied from enrol/database/tests/sync_test.php: init_enrol_database
     *
     * @throws exception
     */
    protected function create_enroll2_table() {
        global $DB, $CFG;

        $dbman = $DB->get_manager();

        set_config('dbencoding', 'utf-8', 'enrol_database');

        set_config('dbhost', $CFG->dbhost, 'enrol_database');
        set_config('dbuser', $CFG->dbuser, 'enrol_database');
        set_config('dbpass', $CFG->dbpass, 'enrol_database');
        set_config('dbname', $CFG->dbname, 'enrol_database');

        if (!empty($CFG->dboptions['dbport'])) {
            set_config('dbhost', $CFG->dbhost.':'.$CFG->dboptions['dbport'], 'enrol_database');
        }

        switch (get_class($DB)) {
            case 'mssql_native_moodle_database':
                set_config('dbtype', 'mssql_n', 'enrol_database');
                set_config('dbsybasequoting', '1', 'enrol_database');
                break;

            case 'mysqli_native_moodle_database':
                set_config('dbtype', 'mysqli', 'enrol_database');
                set_config('dbsetupsql', "SET NAMES 'UTF-8'", 'enrol_database');
                set_config('dbsybasequoting', '0', 'enrol_database');
                if (!empty($CFG->dboptions['dbsocket'])) {
                    $dbsocket = $CFG->dboptions['dbsocket'];
                    if ((strpos($dbsocket, '/') === false and strpos($dbsocket, '\\') === false)) {
                        $dbsocket = ini_get('mysqli.default_socket');
                    }
                    set_config('dbtype', 'mysqli://'.rawurlencode($CFG->dbuser).':'.rawurlencode($CFG->dbpass).'@'.rawurlencode($CFG->dbhost).'/'.rawurlencode($CFG->dbname).'?socket='.rawurlencode($dbsocket), 'enrol_database');
                }
                break;

            case 'oci_native_moodle_database':
                set_config('dbtype', 'oci8po', 'enrol_database');
                set_config('dbsybasequoting', '1', 'enrol_database');
                break;

            case 'pgsql_native_moodle_database':
                set_config('dbtype', 'postgres7', 'enrol_database');
                $setupsql = "SET NAMES 'UTF-8'";
                if (!empty($CFG->dboptions['dbschema'])) {
                    $setupsql .= "; SET search_path = '".$CFG->dboptions['dbschema']."'";
                }
                set_config('dbsetupsql', $setupsql, 'enrol_database');
                set_config('dbsybasequoting', '0', 'enrol_database');
                if (!empty($CFG->dboptions['dbsocket']) and ($CFG->dbhost === 'localhost' or $CFG->dbhost === '127.0.0.1')) {
                    if (strpos($CFG->dboptions['dbsocket'], '/') !== false) {
                      set_config('dbhost', $CFG->dboptions['dbsocket'], 'enrol_database');
                    } else {
                      set_config('dbhost', '', 'enrol_database');
                    }
                }
                break;

            case 'sqlsrv_native_moodle_database':
                set_config('dbtype', 'mssqlnative', 'enrol_database');
                set_config('dbsybasequoting', '1', 'enrol_database');
                break;

            default:
                throw new exception('Unknown database driver '.get_class($DB));
        }

        // NOTE: It is stongly discouraged to create new tables in advanced_testcase classes,
        //       but there is no other simple way to test ext database enrol sync.

        $table = new xmldb_table('enrol_database_test_enroll2');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('termsrs', XMLDB_TYPE_CHAR, '13', null, null, null);
        $table->add_field('uid', XMLDB_TYPE_CHAR, '9', null, null, null);
        $table->add_field('role', XMLDB_TYPE_CHAR, '25', null, null, null);
        $table->add_field('subj_area', XMLDB_TYPE_CHAR, '7', null, null, null);
        $table->add_field('catlg_no', XMLDB_TYPE_CHAR, '8', null, null, null);
        $table->add_field('term_int', XMLDB_TYPE_INTEGER, '3', null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        $dbman->create_table($table);
        set_config('remoteenroltable', $CFG->prefix.'enrol_database_test_enroll2', 'enrol_database');
    }

    /**
     * Returns an array to be used in testing createfinduser's updating process.
     *
     * @return array    Some combo of firstname, lastname, and email.
     */
    public function diff_conditions_provider() {
        $conditions = array('firstname', 'lastname', 'email');
        $retval = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->power_set($conditions, 0);
        foreach ($retval as $index => $value) {
            $retval[$index] = array($value);
        }
        return $retval;
    }

    /**
     * Drops the table that was created by create_enroll2_table().
     */
    protected function drop_enroll2_table() {
        global $DB;

        $dbman = $DB->get_manager();
        $table = new xmldb_table('enrol_database_test_enroll2');
        $dbman->drop_table($table);
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
     * Make sure createorfinduser can find an existing user.
     */
    public function test_createorfinduser_existing() {
        $fieldmappings = array('uid'        => 'idnumber',
                               'firstname'  => 'firstname',
                               'lastname'   => 'lastname',
                               'email'      => 'email',
                               'username'   => 'username');

        // Find a UCLA user with idnumber and username set.
        $uclauser = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_user();
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
     * Make sure createorfinduser returns null for several cases of an invalid
     * enrollment record.
     */
    public function test_createorfinduser_invalid() {
        // Make sure that enrollment records without usernames are rejected.
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

        // Make sure that createorfinduser checks that UIDs and usernames match.
        $user = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user(array('idnumber' => '123456789'));

        // Generate enrollment record, but with different UID.
        $enrollment = array('uid'        => '987654321',
                            'firstname'  => $user->firstname,
                            'lastname'   => $user->lastname,
                            'email'      => $user->email,
                            'username'   => $user->username);
        $founduser = $this->mockenrollmenthelper->createorfinduser($enrollment);
        $this->assertNull($founduser);
    }

    /**
     * Make sure createorfinduser does not update invalid emails.
     */
    public function test_createorfinduser_invalid_email() {
        $user = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_user();

        // Make sure that enrollment records with invalid emails are handled.
        $enrollment = array('uid'        => $user->idnumber,
                            'firstname'  => $user->firstname,
                            'lastname'   => $user->lastname,
                            'email'      => 'invalid-email',
                            'username'   => $user->username);

        $founduser = $this->mockenrollmenthelper->createorfinduser($enrollment);
        $this->assertNotEmpty($founduser);
        $this->assertEquals($user->email, $founduser->email);
    }

    /**
     * Make sure that createorfinduser properly handles finding multiple users
     * with the same UID/idnumber.
     */
    public function test_createorfinduser_multiple() {
        $fieldmappings = array('uid'        => 'idnumber',
                               'firstname'  => 'firstname',
                               'lastname'   => 'lastname',
                               'email'      => 'email',
                               'username'   => 'username');

        // Create users with the same idnumber.
        $users = array();
        $users[] = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user(array('idnumber' => '123456789'));
        $users[] = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user(array('idnumber' => '123456789'));

        foreach ($users as $user) {
            // See if we return null if there was no way to disambiguate.
            $enrollment = array('uid'        => $user->idnumber,
                                'firstname'  => $user->firstname,
                                'lastname'   => $user->lastname,
                                'email'      => $user->email,
                                'username'   => '');
            $founduser = $this->mockenrollmenthelper->createorfinduser($enrollment);
            $this->assertEmpty($founduser);

            // See if we are able to find the right user for given username.
            $enrollment = array('uid'        => $user->idnumber,
                                'firstname'  => $user->firstname,
                                'lastname'   => $user->lastname,
                                'email'      => $user->email,
                                'username'   => $user->username);
            $founduser = $this->mockenrollmenthelper->createorfinduser($enrollment);
            $this->assertNotEmpty($founduser);
            foreach ($fieldmappings as $key => $value) {
                $this->assertEquals($enrollment[$key], $founduser->$value);
            }
        }
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
     * Make sure that when createorfinduser does update a user's information it
     * will update user's information if Registrar has data and local DB is
     * empty, regardless of minuserupdatewaitdays.
     */
    public function test_createorfinduser_update_empty() {
        global $DB;

        // Set user's last access time to some recent time, before
        // minuserupdatewaitdays.
        $maxtime = ((int) get_config('local_ucla', 'minuserupdatewaitdays')) * 86400;
        $lastaccess = time() - mt_rand(0, $maxtime);

        // Fields that we will try to make an empty an empty user for.
        $emptyfields = array('firstname', 'lastname', 'email', 'uid');

        foreach ($emptyfields as $field) {
            $user = $this->getDataGenerator()
                    ->get_plugin_generator('local_ucla')
                    ->create_user(array('lastaccess' => $lastaccess));

            // Change either firstname or lastname, make sure this doesn't change.
            $rejectchangefield = 'firstname';
            if ($field == 'firstname') {
                $rejectchangefield = 'lastname';
            }

            $enrollment = array('uid'       => $user->idnumber,
                                'firstname' => $user->firstname,
                                'lastname'  => $user->lastname,
                                'email'     => $user->email,
                                'username'  => $user->username);

            // Attempt to change a non-blank field with minuserupdatewaitdays in effect.
            $enrollment[$rejectchangefield] = substr(md5(rand()),0,7);

            // Now blank out user field in DB.
            $userfield = $field;
            if ($field == 'uid') {
                $userfield = 'idnumber';
            }
            $DB->set_field('user', $userfield, '', array('id' => $user->id));

            $founduser = $this->mockenrollmenthelper->createorfinduser($enrollment);

            // Make sure that we didn't update non-empty local fields.
            $this->assertEquals($user->$rejectchangefield, $founduser->$rejectchangefield,
                    'Field updated: ' . $rejectchangefield);

            // Make sure returned user does have empty local field updated.
            $this->assertEquals($enrollment[$field], $founduser->$userfield,
                    'Field being processed: ' . $field);

            // Make sure local DB was updated as well.
            $dbuser = $DB->get_record('user', array('id' => $user->id));
            $this->assertEquals($enrollment[$field], $dbuser->$userfield,
                    'Field being processed: ' . $field);
        }
    }

    /**
     * Make sure that createorfinduser does update a user's information if
     * information is out of date according to the externaldb and user has not
     * logged in for a while.
     *
     * @dataProvider diff_conditions_provider
     */
    public function test_createorfinduser_update_needed(array $diffconditions) {
        global $DB;

        // Set user's last access time to some very far time, after
        // minuserupdatewaitdays.
        $mintime = ((int) get_config('local_ucla', 'minuserupdatewaitdays')) * 86401;
        $lastaccess = max(time() - mt_rand($mintime, mt_getrandmax()), 0);
        $user = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user(array('lastaccess' => $lastaccess));

        $diffuser = clone($user);
        if (!empty($diffconditions)) {
            // The empty case makes no sense for this test.
            return;
        }
        foreach ($diffconditions as $field) {
            $diffuser->$field = str_shuffle($diffuser->$field);
        }

        $enrollment = array('uid'       => $diffuser->idnumber,
                            'firstname' => $diffuser->firstname,
                            'lastname'  => $diffuser->lastname,
                            'email'     => $diffuser->email,
                            'username'  => $diffuser->username);

        $founduser = $this->mockenrollmenthelper->createorfinduser($enrollment);

        // Make sure returned user does have info updated.
        $dbuser = $DB->get_record('user', array('id' => $user->id));
        foreach (array('firstname', 'lastname', 'email') as $field) {
            $this->assertEquals($diffuser->$field, $founduser->$field,
                    'Field being processed: ' . $field);
            // Make sure local DB was updated as well.
            $this->assertEquals($dbuser->$field, $founduser->$field,
                    'Field being processed: ' . $field);
        }
    }

    /**
     * Make sure that when createorfinduser does update a user's information it
     * will not update with empty data.
     */
    public function test_createorfinduser_update_notempty() {
        global $DB;

        // Set user's last access time to some very far time, after
        // minuserupdatewaitdays.
        $mintime = ((int) get_config('local_ucla', 'minuserupdatewaitdays')) * 86401;
        $lastaccess = max(time() - mt_rand($mintime, mt_getrandmax()), 0);
        $user = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user(array('lastaccess' => $lastaccess));

        // Fields that we will try to make an empty enrollment record for.
        $emptyfields = array('firstname', 'lastname', 'email');
        foreach ($emptyfields as $field) {
            $enrollment = array('uid'       => $user->idnumber,
                                'firstname' => $user->firstname,
                                'lastname'  => $user->lastname,
                                'email'     => $user->email,
                                'username'  => $user->username);
            $enrollment[$field] = '';
            $founduser = $this->mockenrollmenthelper->createorfinduser($enrollment);

            // Make sure returned user does not have empty info updated.
            $this->assertEquals($user->$field, $founduser->$field,
                    'Field being processed: ' . $field);

            // Make sure local DB was not updated as well.
            $dbuser = $DB->get_record('user', array('id' => $user->id));
            $this->assertEquals($dbuser->$field, $founduser->$field,
                    'Field being processed: ' . $field);
        }
    }

    /**
     * Make sure that createorfinduser does not update a user's information
     * even if information is out of date according to the externaldb.
     *
     * @dataProvider diff_conditions_provider
     */
    public function test_createorfinduser_update_notneeded(array $diffconditions) {
        // Set user's last access time to some recent time, before
        // minuserupdatewaitdays.
        $maxtime = ((int) get_config('local_ucla', 'minuserupdatewaitdays')) * 86400;
        $user = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user(array('lastaccess' => time() - mt_rand(0, $maxtime)));

        $diffuser = clone($user);
        if (!empty($diffconditions)) {
            foreach ($diffconditions as $field) {
                $diffuser->$field = str_shuffle($diffuser->$field);
            }
        }

        $enrollment = array('uid'       => $diffuser->idnumber,
                            'firstname' => $diffuser->firstname,
                            'lastname'  => $diffuser->lastname,
                            'email'     => $diffuser->email,
                            'username'  => $diffuser->username);

        $founduser = $this->mockenrollmenthelper->createorfinduser($enrollment);

        // Make sure returned user does not have any info changed.
        foreach (array('firstname', 'lastname', 'email') as $field) {
            $this->assertEquals($user->$field, $founduser->$field);
        }
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
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array('term' => '13W'));
        $courseidsexpected[] = array_pop($class)->courseid;
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array('term' => '13W'), array('term' => '13W'));
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
     */
    public function test_enrol_database_plugin_sync_enrolments() {
        global $DB;
        // Add mocked enrollment helper.
        $enrol = enrol_get_plugin('database');
        $enrol->enrollmenthelper = $this->mockenrollmenthelper;

        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array('term' => '13S',
                                     'subj_area' => 'MATH',
                                     'division' => 'PS'));
        $course = array_pop($class);
        $term = $course->term;
        $srs = $course->srs;
        $courseid = $course->courseid;

        $courseusernames = array();

        // Create enrollment records, first for instructors. Let system find
        // Instructor, but create the TA.
        $instructor = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_user(array('idnumber' => '123456789',
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

        // Create student enrollment records.
        $students[0] = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_user();
        $students[1] = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_user();
        $students[2] = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_user();
        $regstudents= array();
        foreach ($students as $index => $student) {
            $regstudents[$index] = $this->create_ccle_roster_class_entry($term, $srs, 'E', $student);
            $courseusernames[] = $student->username;
        }
        $this->set_mockregdata('ccle_roster_class', $term, $srs, $regstudents);

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

        // Drop a student and make sure they do not appear in the enrollment.
        $droppedstudent = $students[2];
        unset($regstudents[2]);
        $enrol->sync_enrolments($this->trace, array('13S'));
        $result = $this->is_username_enrolled($courseid, $droppedstudent->username);
        $this->assertTrue($result);

        // Make sure we are not syncing enrollments for any other term.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array('term' => '13F'));
        $course = array_pop($class);
        $courseid = $course->courseid;
        $courseobj = $DB->get_record('course', array('id' => $courseid));
        $instanceid = $enrol->add_instance($courseobj);
        $instance = $DB->get_record('enrol', array('id' => $instanceid));

        $enrol->enrol_user($instance, $instructor->id, $this->createdroles['editinginstructor']);
        $result = $this->is_username_enrolled($courseid, $instructor->username);
        $this->assertTrue($result);
        $enrol->sync_enrolments($this->trace, array('13S'));
        $result = $this->is_username_enrolled($courseid, $instructor->username);
        $this->assertTrue($result);
    }

    /**
     * Makes sure that the sync_user_enrolments() method is working with the
     * UCLA modifications.
     */
    public function test_enrol_database_plugin_sync_user_enrolments() {
        global $DB;

        // Setup enroll2 table.
        $this->create_enroll2_table();

        // Create some classes and what role the user should have.
        $classes = array();
        $roles = array('editingteacher', 'student', 'student_instructor',
            'ta_instructor', 'supervising_instructor');
        foreach ($roles as $role) {
            $classes[$role] = $this->getDataGenerator()
                    ->get_plugin_generator('local_ucla')->create_class();
        }

        // Create a user to add to those classes.
        $user = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_user();
        $this->setUser($user);

        // Add that user to enroll2 table with given role.
        $numcourses = 0;
        foreach ($classes as $role => $class) {
            $course = array_pop($class);
            $term = $course->term;
            $srs = $course->srs;

            $classinfo = ucla_get_reg_classinfo($term, $srs);
            $record = new stdClass();
            $record->termsrs = $term.'-'.$srs;
            $record->uid = $user->idnumber;
            $record->role = $role;
            $record->subj_area = $classinfo->subj_area;
            $record->catlg_no = $classinfo->crsidx;
            $record->sect_no = $classinfo->classidx;

            $DB->insert_record('enrol_database_test_enroll2', $record);
            ++$numcourses;
        }
        $this->assertEquals(count($classes), $numcourses);

        // Run sync, and user should get enrolled appropiately.
        $enrol = enrol_get_plugin('database');
        $enrol->sync_user_enrolments($user);

        foreach ($classes as $role => $class) {
            $course = array_pop($class);
            $term = $course->term;
            $srs = $course->srs;
            $courseid = $course->courseid;
            $context = context_course::instance($courseid);

            $isenrolled = $this->is_username_enrolled($courseid, $user->username);
            $this->assertTrue($isenrolled);

            // Role name from Registrar do not always line up with Moodle role.
            $roleshortname = '';
            switch ($role) {
                case 'editingteacher':
                    $roleshortname = 'editinginstructor';
                    break;
                case 'student_instructor':
                    $roleshortname = 'studentfacilitator';
                    break;
                default:
                    $roleshortname = $role;
            }

            $hasrole = has_role_in_context($roleshortname, $context);
            $this->assertTrue($hasrole);
        }

        // Cleaup after ourselves.
        $this->drop_enroll2_table();
    }

    /**
     * Make sure that get_course returns the proper course record for a given
     * term/srs.
     */
    public function test_get_course() {
        global $DB;

        // Create non-crosslist course.
        $classA = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_class(array());
        // Create crosslisted courses.
        $classB = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array(), array(), array());
        $classsections = array_merge($classA, $classB);

        foreach ($classsections as $course) {
            $term = $course->term;
            $srs = $course->srs;
            $courseid = $course->courseid;

            $actualcourse = $this->mockenrollmenthelper->get_course($term.'-'.$srs);
            $expectedcourse = $DB->get_record('course', array('id' => $courseid));
            $this->assertEquals($expectedcourse, $actualcourse);
        }
    }

    /**
     * Make sure that get_external_enrollment_courses returns the proper data.
     */
    public function test_get_external_enrollment_courses() {
        global $DB;
        $courseidsexpected = array();

        // Create non-crosslist course.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array('term' => '13S'));
        $courseidsexpected[] = array_pop($class)->courseid;

        // Create crosslist course.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array('term' => '13S'), array('term' => '13S'));
        $courseidsexpected[] = array_pop($class)->courseid;

        // Expecting to find all courseids above in result array.
        $this->mockenrollmenthelper->set_run_parameter(array('13S'));
        $results = $this->mockenrollmenthelper->get_external_enrollment_courses();
        $courseidsreturned = array_keys($results);
        sort($courseidsexpected);
        sort($courseidsreturned);
        $this->assertEquals($courseidsexpected, $courseidsreturned);
        $springcourseidsexpected = $courseidsexpected;

        // Create courses in another term and check they are returned.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array('term' => '131'));
        $courseidsexpected[] = array_pop($class)->courseid;
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')
                ->create_class(array('term' => '131'), array('term' => '131'));
        $courseidsexpected[] = array_pop($class)->courseid;

        // Expecting to find all courseids above in result array.
        $this->mockenrollmenthelper->set_run_parameter(array('13S', '131'));
        $results = $this->mockenrollmenthelper->get_external_enrollment_courses();
        $courseidsreturned = array_keys($results);
        sort($courseidsexpected);
        sort($courseidsreturned);
        $this->assertEquals($courseidsexpected, $courseidsreturned);

        // Query just for Sprint again and make sure nothing else is returned.
        $this->mockenrollmenthelper->set_run_parameter(array('13S'));
        $results = $this->mockenrollmenthelper->get_external_enrollment_courses();
        $springcourseidsreturned = array_keys($results);
        sort($springcourseidsreturned);
        $this->assertEquals($springcourseidsexpected, $springcourseidsreturned);

        // Add an course request that hasn't beeen built to Spring and make
        // sure it isn't returned.
        $request = new stdClass();
        $request->term = '13S';
        $request->srs = '000000001';
        $request->department = 'MATH';
        $request->course = '1';
        $request->action = UCLA_COURSE_TOBUILD;
        $DB->insert_record('ucla_request_classes', $request);
        $results = $this->mockenrollmenthelper->get_external_enrollment_courses();
        $this->assertEquals(count($springcourseidsreturned), count($results));
    }

    /**
     * Test getting enrollment for course instructors.
     */
    public function test_get_instructors() {
        // Get a non-crosslisted class.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_class(array());

        // Create instructors to enroll. Avoid TA role here, because for some
        // subject areas it maps to either taadmin or ta.
        $instructor = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_user();
        $supervisinginstructor = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_user();

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
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_class(array());

        // Create results to use for ccle_courseinstructorsget.
        $course = array_pop($class);
        $term = $course->term;
        $srs = $course->srs;

        // Create Instructor and Supervising instructor.
        $instructor = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_user();
        $supervisinginstructor = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_user();
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
     * Test getting enrollment for a course roster for students.
     */
    public function test_get_students() {
        // Get a non-crosslisted class.
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_class(array());

        // Create students to enroll.
        $dropped = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_user();
        $enrolled = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_user();
        $waitlist = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_user();

        // Index by enrollment code.
        $students = array('D' => $dropped,
                          'E' => $enrolled,
                          'W' => $waitlist);

        // Create results to use for ccle_roster_class.
        $entry = array_pop($class);
        $term = $entry->term;
        $srs = $entry->srs;
        foreach ($students as $enrollmentcode => $role) {
            $regstudents[] = $this->create_ccle_roster_class_entry(
                    $term, $srs, $enrollmentcode, $role);
        }
        $this->set_mockregdata('ccle_roster_class', $term, $srs, $regstudents);

        // Get data to send query get_students().
        $termcourses = ucla_get_courses_by_terms(array($term));

        // Should only be one, since we only created 1 course.
        $this->assertEquals(1, count($termcourses));
        $requestclasses = array_pop($termcourses);

        $enrollments = $this->mockenrollmenthelper->get_students($requestclasses);
        $this->assertNotEmpty($enrollments);

        // Make sure users return have proper roles and proper data returned.
        $founddropped = false;
        foreach ($enrollments as $enrollment) {
            if ($enrollment['uid'] == $dropped->idnumber) {
                $founddropped = true;
            } else if ($enrollment['uid'] == $enrolled->idnumber) {
                $this->assertEquals($this->createdroles['student'],
                        $enrollment['role']);
                $this->assertEquals($enrollment['username'], $enrolled->username);
            } else if ($enrollment['uid'] == $waitlist->idnumber) {
                $this->assertEquals($this->createdroles['student'],
                        $enrollment['role']);
                $this->assertEquals($enrollment['username'], $waitlist->username);
            }
        }
        $this->assertFalse($founddropped);
    }

    /**
     * Test that we are not processing dummy "THE STAFF" or TA accounts.
     */
    public function test_nodummy() {
        $class = $this->getDataGenerator()
                ->get_plugin_generator('local_ucla')->create_class();

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
