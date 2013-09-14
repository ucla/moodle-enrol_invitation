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
 * Helper class to aid our modifications for the database enrolment plugin.
 *
 * This plugin contains the methods and logic needed to modify the database
 * enrollment plugin to support UCLA's unique way of mapping Registrar data
 * to Moodle courses and roles.
 *
 * @package local_ucla
 * @author  Rex Lorenzo - based on code by Yangmun Choi
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclacourserequestor/lib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/local/ucla/datetimehelpers.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/validateurlsyntax.php');
ucla_require_registrar();

/**
 * Helper class for database enrolment plugin implementation.
 */
class local_ucla_enrollment_helper {

    /**
     * If set, is course that we are processing.
     * @var int
     */
    private $courseid = null;

    /**
     * Value of enrol_database|localcoursefield.
     *
     * Should be "id".
     *
     * @var string
     */
    protected $localcoursefield;

    /**
     * Value of enrol_database|localuserfield.
     *
     * Should be "idnumber".
     *
     * @var string
     */
    protected $localuserfield;

    /**
     * Value of local_ucla|minuserupdatewaitdays times # of seconds in a day.
     *
     * Default is 30 * 86400.
     *
     * @var int
     */
    protected $minuserupdatewait;

    /**
     * Value of enrol_database|remoterolefield.
     *
     * Should be "role".
     *
     * @var string
     */
    protected $remoterolefield;

    /**
     * Value of enrol_database|remoteuserfield.
     *
     * Should be "uid".
     *
     * @var string
     */
    protected $remoteuserfield;

    /**
     * If set, are terms that we are processing.
     * @var int
     */
    private $terms = null;

    /**
     * Used to output any messages.
     *
     * @var progress_trace
     */
    protected $trace;

    /**
     * Constructor.
     *
     * @param progress_trace $trace
     * @param enrol_database_plugin $enroldatabase
     * @param int|array $termsorcourseid
     */
    public function __construct(progress_trace $trace,
            enrol_database_plugin $enroldatabase, $termsorcourseid = null) {

        $this->trace = $trace;

        // Store needed config variables.
        $this->localcoursefield = $enroldatabase->get_config('localcoursefield');
        $this->localuserfield   = $enroldatabase->get_config('localuserfield');
        $this->remoterolefield  = $enroldatabase->get_config('remoterolefield');
        $this->remoteuserfield  = $enroldatabase->get_config('remoteuserfield');

        $this->minuserupdatewait = ((int) get_config('local_ucla', 'minuserupdatewaitdays')) * 86400;

        $this->set_run_parameter($termsorcourseid);
    }

    /**
     * Will try to create or find user that matches the given enrollment record.
     *
     * @param arrray $enrollment    Enrollment records returned by
     *                              $this->get_instructors and
     *                              $this->get_students
     *
     * @return object               Returns user record matching enrollment
     *                              record. If no user is found and cannot
     *                              create the user, will return null.
     *
     * @throws dml_multiple_records_exception If multiple users were found for
     *                                        a given idnumber or username.
     * @throws dml_write_exception            If cannot create user.
     */
    public function createorfinduser(array $enrollment) {
        global $CFG, $DB;
        $retval = null;
        /* Expecting array with following keys:
         * $this->remoteuserfield | 'uid'
         * 'firstname'
         * 'lastname'
         * 'email'
         * 'username'
         * $this->remoterolefield | 'role'
         */

        // Let's use Moodle caching since we are going to be processing the same
        // users for multiple courses.
        $cache = cache::make('local_ucla', 'usermappings');
        $cachekey = sprintf('idnumber:%s:username:%s',
                $enrollment[$this->remoteuserfield], $enrollment['username']);
        $retval = $cache->get($cachekey);
        if ($retval !== false) {
            // Cache returns false if record does not exist. But we are setting
            // null for a given cachekey if the user cannot be created, because
            // if is missing the username. So we need to make sure to explicitly
            // check for false and return null.
            return $retval;
        }

        // Unable to find cached user, so let's try to find a user. Need to be
        // flexible in trying to find a user. In order of preference we will try
        // to find a user by idnumber/UID or username/UCLA logonID.

        // 1) localuserfield/remoteuserfield, aka UCLA UID, aka user.idnumber.
        $foundmultipleuids = false;
        if (!empty($enrollment[$this->remoteuserfield])) {
            try {
                $retval = $DB->get_record('user',
                        array($this->localuserfield => $enrollment[$this->remoteuserfield],
                              'mnethostid' => $CFG->mnet_localhost_id,
                              'auth' => 'shibboleth'), '*', MUST_EXIST);
            } catch (dml_missing_record_exception $notfound) {
                // This is okay and expected, so just continue along.
            } catch (dml_multiple_records_exception $multiple) {
                // This is kinda bad. Means that multiple users have the same
                // UID set. Need to report this, and try to see if username
                // can give us a unique record.
                $this->trace->output(sprintf('ERROR: Found multiple users ' .
                        'with idnumber (%s). Trying to see if unique record ' .
                        'for username (%s) can be found.',
                        $enrollment[$this->remoteuserfield],
                        $enrollment['username']));
                $foundmultipleuids = true;
                $retval = null;
            }
        }

        // 2) username, aka UCLA LogonID, aka user.username.
        if (empty($retval) && !empty($enrollment['username'])) {
            try {
                $retval = $DB->get_record('user',
                        array('username' => $enrollment['username'],
                              'mnethostid' => $CFG->mnet_localhost_id,
                              'auth' => 'shibboleth'), '*', MUST_EXIST);
            } catch (dml_missing_record_exception $notfound) {
                // This is okay and expected, so just continue along.
            }

            if (!empty($foundmultipleuids) && !empty($retval)) {
                $this->trace->output(sprintf('WARNING: Found unique user ' .
                        'record for username (%s), but still exist multiple ' .
                        'users with same idnumber (%s)',
                        $enrollment['username'],
                        $enrollment[$this->remoteuserfield]));
            }
        }

        if (!empty($foundmultipleuids) && empty($retval)) {
            $this->trace->output('Unable to find unique user record for ' .
                    'idnumber ().', $enrollment[$this->remoteuserfield]);
            return null;
        }

        // User is not found on the local system, so we have to create one.
        if (empty($retval)) {
            if (empty($enrollment['username'])) {
                // Cannot create a user with no username.
                $this->trace->output(sprintf('Cannot create user without username: %s',
                        implode(',', $enrollment)), 1);
                // Set cachekey to return null, so we don't keep on trying to
                // find and fail to create this user.
                $cache->set($cachekey, null);
                return null;
            }
            // Taken from user/editadvanced.php.
            $retval = new stdclass();
            $retval->confirmed = 1;
            $retval->auth = 'shibboleth';
            $retval->mnethostid = $CFG->mnet_localhost_id;
            $retval->{$this->localuserfield} = $enrollment[$this->remoteuserfield];
            $retval->firstname = $enrollment['firstname'];
            $retval->lastname = $enrollment['lastname'];
            $retval->email = $enrollment['email'];
            $retval->username = $enrollment['username'];
            $retval->id = user_create_user($retval);

            $this->trace->output(sprintf('Created user: %s',
                    implode(',', $enrollment)), 1);
        } else {
            // User was found on local system, so update their info, if needed.
            $needsupdating = false;
            $updateuserfields = array('uid', 'firstname', 'lastname', 'email');

            // Clone, because we might not use the updates if minuserupdatewait
            // did not pass, but still want to be notified of potentially out
            // of date data from the Registrar.
            $user = clone($retval);

            foreach ($updateuserfields as $field) {
                if (empty($enrollment[$field])) {
                    // We do not want to process blank values from enrollment.
                    continue;
                }

                // Do not accept invalid emails.
                if ($field == 'email') {
                    if (!validateEmailSyntax($enrollment[$field])) {
                        $this->trace->output(sprintf('Invalid email: %s. ' .
                                'Enrollment record: %s', $enrollment[$field],
                                implode(',', $enrollment)), 2);
                        continue;
                    }
                }

                // We only want to update idnumber if it is blank locally.
                if ($field == 'uid') {
                    if (empty($user->{$this->localuserfield})) {
                        // Local idnumber is not set for some reason.
                        $needsupdating = true;
                        $this->trace->output(
                                sprintf('User %d setting %s/%s: [%s]',
                                        $retval->id, $field,
                                        $this->localuserfield,
                                        $enrollment[$field]), 2);
                        $user->{$this->localuserfield} = $enrollment[$field];
                    } else if ($enrollment[$field]
                            != $user->{$this->localuserfield}) {
                        // Sanity check! If uid exists, it should match.
                        $this->trace->output(sprintf(
                                'ERROR: Found mismatching user UIDs ' .
                                '(%s vs %s) for given UCLA LogonID %s. ' .
                                'Enrollment record: %s', $enrollment[$field],
                                $user->{$this->localuserfield},
                                $enrollment['username'],
                                implode(',', $enrollment)), 2);
                        $retval = null;
                        $needsupdating = false;
                        break;
                    }
                } else if ($enrollment[$field] !== $user->$field) {
                    $needsupdating = true;
                    $this->trace->output(
                            sprintf('User %d needs update: %s [%s] => [%s]',
                                    $retval->id, $field, $retval->$field,
                                    $enrollment[$field]), 2);
                    $user->$field = $enrollment[$field];
                }
            }

            if ($needsupdating) {
                // Check if it has passed minuserupdatewaitdays, else we are just
                // fighting against Shibboleth data.
                if ((time() - $user->lastaccess) > $this->minuserupdatewait) {
                    // We aren't updating a user's password.
                    unset($user->password);
                    user_update_user($user);
                    $this->trace->output(sprintf('Updated user %d: ' .
                            'enrollment record: %s', $user->id,
                            implode(',', $enrollment)), 2);
                    // User was updated, so replace what we queried for before.
                    $retval = $user;
                } else {
                    $this->trace->output(sprintf(
                            'Skip updating user %d, because user logged in %s ago',
                            $user->id,
                            distance_of_time_in_words($user->lastaccess, time())), 2);
                }
            }
        }

        $cache->set($cachekey, $retval);
        return $retval;
    }

    /**
     * Used by sync_user_enrolments to map the remote course field termsrs to a
     * local Moodle course.
     *
     * @param string $termsrs
     *
     * @return object           Returns the course object, if any.
     */
    public function get_course($termsrs) {
        global $DB;

        list($term, $srs) = explode('-', $termsrs);
        if (empty($term) || empty($srs)) {
            return false;
        }

        $sql = 'SELECT  c.*
                FROM    {course} c
                JOIN    {ucla_request_classes} urc ON urc.courseid = c.id
                WHERE   urc.term = :term AND
                        urc.srs = :srs';
        $course = $DB->get_record_sql($sql, array('term' => $term, 'srs' => $srs));

        return $course;
    }

    /**
     * For a given course, will return an array of enrollment records containing
     * a user's information and role in course.
     *
     * @param array $requestclasss  Array of ucla_request_classes entries for a
     *                              single course.
     *
     * @return array                Array of enrollment records of user info and
     *                              user's roleid.
     */
    public function get_enrollments(array $requestclassses) {
        $instructors = $this->get_instructors($requestclassses);
        $students = $this->get_students($requestclassses);
        return array_merge($instructors, $students);
    }

    /**
     * Calls stored procedure ccle_courseinstructorsget, translates results,
     * and then does the role mapping to return an array of instructors and
     * their roles for a given course.
     *
     * @param array $requestclasss  Array of ucla_request_classes entries for a
     *                              single course.
     *
     * @return array                Array of enrollment records of user info and
     *                              user's roleid.
     */
    public function get_instructors(array $requestclasses) {
        $retval = array();

        foreach ($requestclasses as $requestclass) {
            $subjarea = $requestclass->department;

            // Query registrar for instructors.
            $instructors = $this->query_registrar('ccle_courseinstructorsget',
                    $requestclass->term, $requestclass->srs);

            if (empty($instructors)) {
                continue;
            }

            // Need to create mapping of role code to primary/secondary
            // sections.
            $otherroles = array();
            foreach ($instructors as $index => $instructor) {
                // If srs is different than parameters given for
                // ccle_courseinstructorsget, then it is a secondary srs.
                $sectiontype = 'primary';
                if ($instructor['srs'] != $requestclass->srs) {
                    $sectiontype = 'secondary';
                }

                $primaryccode = $instructor['role'];
                $otherroles[$sectiontype][] = $primaryccode;
                $instructors[$index]['prof_codes'][$sectiontype][] = $primaryccode;
            }

            // Map instructors to their appropiate role in the course.
            foreach ($instructors as $instructor) {
                // Skip "THE STAFF" or "TA".
                if (is_dummy_ucla_user($instructor['ucla_id'])) {
                    continue;
                }

                try {
                    $localrole = role_mapping(
                            $instructor['prof_codes'], $otherroles, $subjarea
                    );
                } catch (moodle_exception $me) {
                    // Cannot find a good role map, so skip processing.
                    $this->trace('Could not get good mapping for instructor: ' .
                            implode('|', $instructor), 1);
                    continue;
                }

                $user = $this->translate_ccle_course_instructorsget($instructor);
                $user[$this->remoterolefield] = $localrole;
                $retval[] = $user;
            }
        }

        return $retval;
    }

    /**
     * Returns roleid for give pseudorole and subject area pair.
     *
     * @param string $pseudorole
     * @param string $subjarea
     * @return int                  Role id for given mapping. Returns false on
     *                              error.
     */
    public function get_role($pseudorole, $subjarea) {
        try {
            $roleid = get_moodlerole($pseudorole, $subjarea);
        } catch (moodle_exception $me) {
            // Cannot find a good role map, so skip processing.
            $this->trace(sprintf('Could not get role mapping for %s|%s',
                    $pseudorole, $subjarea), 1);
            return false;
        }
        return $roleid;
    }

    /**
     * Calls stored procedure ccle_roster_class, translates results, and then
     * does the role mapping to return an array of students and their roles for
     * a given course.
     *
     * @param array $requestclasss  Array of ucla_request_classes entries for a
     *                              single course.
     *
     * @return array                Array of enrollment records of user info and
     *                              user's roleid.
     */
    public function get_students(array $requestclasses) {
        $retval = array();

        foreach ($requestclasses as $requestclass) {
            $subjarea = $requestclass->department;

            // Query registrar for $students.
            $students = $this->query_registrar('ccle_roster_class',
                    $requestclass->term, $requestclass->srs);

            if (empty($students)) {
                continue;
            }

            foreach ($students as $student) {
                $pseudorole = get_student_pseudorole($student['enrl_stat_cd']);
                if (empty($pseudorole)) {
                    // Student has dropped or cancelled.
                    continue;
                }

                $roleid = $this->get_role($pseudorole, $subjarea);
                if (empty($roleid)) {
                    continue;
                }

                $user = $this->translate_ccle_roster_class($student);
                $user[$this->remoterolefield] = $roleid;
                $retval[] = $user;
            }
        }

        return $retval;
    }

    /**
     * Returns an array of courses for the given terms we are working on that
     * already have the enrol_database plugin added.
     */
    public function get_existing_courses() {
        global $DB;
        $retval = array();

        if (empty($this->terms)) {
            throw new Exception('No terms to query');
        }

        list($tconditions, $tparams) = $DB->get_in_or_equal($this->terms);

        $sql = "SELECT  c.id,
                        c.visible,
                        c." . $this->localcoursefield . " AS mapping,
                        e.id AS enrolid,
                        c.shortname
                FROM    {course} c
                JOIN    {enrol} e ON (e.courseid = c.id AND e.enrol = 'database')
                JOIN    {ucla_request_classes} urc ON (urc.courseid = c.id)
                WHERE   urc.term $tconditions";
        $rs = $DB->get_recordset_sql($sql, $tparams);
        foreach ($rs as $course) {
            $retval[$course->{$this->localcoursefield}] = $course;
        }
        $rs->close();

        return $retval;
    }

    /**
     * Returns an array of courseids mapped to a boolean that is expected by the
     * enrol_database plugin when it is checking which courses to add the
     * enrollment plugin.
     */
    public function get_external_enrollment_courses() {
        global $DB;
        $retval = array();

        if (empty($this->terms)) {
            throw new Exception('No terms to query');
        }

        $records = $DB->get_records_list('ucla_request_classes', 'term', $this->terms);
        foreach ($records as $record) {
            // Only return records of built courses with courseids.
            if ($record->action == UCLA_COURSE_BUILT && !empty($record->courseid)) {
                $retval[$record->courseid] = true;
            }
        }

        return $retval;
    }

    /**
     * Returns mapping of userid to roleid enrollments for given course.
     *
     * Since returned mapping needs to have a userid, will create users who
     * are returned by the Registrar, but do not currently exist on the server.
     * Else will update a user's information.
     *
     * @param object $course    Database record with following attributes:
     *                              id, visible, shortname
     *
     * @return array            Returns in following format:
     *                          [userid][roleid] => roleid
     */
    public function get_requested_roles($course) {
        $retval = array();

        $requestclasses = ucla_map_courseid_to_termsrses($course->id);
        $enrollments = $this->get_enrollments($requestclasses);

        // Go through each enrollment and either create user or find/update
        // their info.
        foreach ($enrollments as $enrollment) {
            $user = $this->createorfinduser($enrollment);
            if (empty($user)) {
                // Cannot create user!
                $this->trace->output('Skipping user: ' . implode(',', $enrollment));
                continue;
            }
            $roleid = $enrollment[$this->remoterolefield];
            $retval[$user->id][$roleid] = $roleid;
        }

        return $retval;
    }

    /**
     * Appends '@ucla' to the 'bolid' field from Registrar data.
     *
     * @param string $bolid
     * @return string           Returns an empty string in bolid is empty.
     */
    public function normalize_bolid($bolid) {
        $bolid = trim($bolid);
        if (!empty($bolid)) {
            return $bolid . '@ucla.edu';
        }
        return '';
    }

    /**
     * Queries given stored procedure and filters results.
     *
     * Function is basically a wrapper for run_registrar_query, to make unit
     * testing stubbing possible.
     *
     * @param string $sp        Stored procedure to call.
     * @param string $term
     * @param string $srs
     *
     * @return array            Returns array of successful results.
     */
    public function query_registrar($sp, $term, $srs) {
        $results = registrar_query::run_registrar_query(
                        $sp, array($term, $srs), false
        );

        // Log any failed results.
        if (!empty($results[registrar_query::failed_outputs])) {
            $this->trace->output(sprintf('%d failed results from %s (%s,%s):',
                    count($results[registrar_query::failed_outputs]), $sp,
                    $term, $srs), 1);
            foreach ($results[registrar_query::failed_outputs] as $failed) {
                $failed = array_map('trim', $failed);
                $this->trace->output(implode(',', $failed), 2);
            }
        }

        return $results[registrar_query::query_results];
    }

    /**
     * Sets what type of enrollment we are doing.
     *
     * @param int|array $termsorcourseid
     *
     * @throws Exception
     */
    public function set_run_parameter($termsorcourseid = null) {
        global $DB;
        $this->courseid = null;
        $this->terms = null;

        if (is_array($termsorcourseid)) {
            // Really a list of terms to process.
            foreach ($termsorcourseid as $term) {
                if (!ucla_validator('term', $term)) {
                    throw new Exception('Invalid term: ' . $term);
                }
            }
            $this->terms = $termsorcourseid;
        } else if (is_int($termsorcourseid)) {
            // Make sure that given term belongs to a reg course.
            if (!$DB->record_exists('ucla_request_classes',
                    array('courseid' => $termsorcourseid))) {
                throw new Exception('Invalid courseid: ' . $termsorcourseid);
            }
            $this->courseid = $termsorcourseid;
        }
    }

    /**
     * Translate results from the stored procedure "ccle_course_instructorsget"
     * to fields expected by Moodle.
     *
     * @param array $regdata            Row from "ccle_course_instructorsget".
     *
     * @return array
     */
    public function translate_ccle_course_instructorsget(array $regdata) {
        return array(
            $this->remoteuserfield => trim($regdata['ucla_id']),
            'firstname' => ucla_format_name(trim($regdata['first_name_person'])),
            'lastname' => ucla_format_name(trim($regdata['last_name_person'])),
            'email' => trim($regdata['ursa_email']),
            'username' => $this->normalize_bolid($regdata['bolid'])
        );
    }

    /**
     * Translate results from the stored procedure "ccle_roster_class" to
     * fields expected by Moodle.
     *
     * @param progress_trace $trace
     * @param array $regdata            Data row from "ccle_roster_class" SP.
     * @param string $remoteuserfield   Config enrol_database|remoteuserfield.
     *
     * @return array
     */
    public function translate_ccle_roster_class(array $regdata) {
        $name = format_displayname($regdata['full_name_person']);
        return array(
            $this->remoteuserfield => trim($regdata['stu_id']),
            'firstname' => $name['firstname'],
            'lastname' => $name['lastname'],
            'email' => trim($regdata['ss_email_addr']),
            'username' => $this->normalize_bolid($regdata['bolid'])
        );
    }

    /**
     * Let other plugins know that enrollment was updated for the following
     * courses.
     *
     * @param array     Expecting $courses to be an array similar to what is
     *                  returned by get_existing_courses().
     *
     * @return void
     */
    public function trigger_sync_enrolments_event($courses) {
        if (class_exists('phpunit_util') && phpunit_util::is_test_site()) {
            // Don't run event trigger if running in a unit test, or else will
            // throw up a bunch of errors.
            return;
        }
        $eventdata = new object();
        $eventdata->courses = $courses;
        events_trigger('sync_enrolments_finished', $eventdata);
    }
}
