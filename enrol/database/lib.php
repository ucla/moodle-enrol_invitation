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
 * Database enrolment plugin.
 *
 * This plugin synchronises enrolment and roles with external database table.
 *
 * @package    enrol
 * @subpackage database
 * @copyright  2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot .'/user/lib.php');

/**
 * Database enrolment plugin implementation.
 * @author  Petr Skoda - based on code by Martin Dougiamas, Martin Langhoff and others
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_database_plugin extends enrol_plugin {
    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param object $instance
     * @return bool
     */
    public function instance_deleteable($instance) {
        if (!enrol_is_enabled('database')) {
            return true;
        }
        if (!$this->get_config('dbtype') or !$this->get_config('dbhost') or !$this->get_config('remoteenroltable') or !$this->get_config('remotecoursefield') or !$this->get_config('remoteuserfield')) {
            return true;
        }

        //TODO: connect to external system and make sure no users are to be enrolled in this course
        return false;
    }

    /**
     *  Function to translate the stored procedures results to Moodle 
     *  user results (named after the stored procedure).
     **/
    public function translate_ccle_roster_class($reg) {
        $names = explode(',', trim($reg['full_name_person']));

        if (empty($names)) {
            // no name?!
            mtrace('WARNING: Found user with no name from class roster: '. print_r($reg, true));    
            $names[0] = '';
            $firstmiddle = array('');
        } else if (empty($names[1])) {
            // No first name, they must be a rock star
            $firstmiddle = array('');
        } else {
            $firstmiddle = explode(' ', trim($names[1]));
        }

        return array(
            $this->get_config('remoteuserfield') => $reg['stu_id'],
            'firstname' => $firstmiddle[0],
            'lastname'  => $names[0],
            'email'     => $reg['ss_email_addr'],
            $this->get_config('fbremoteuserfield') => $reg['bolid'] . '@ucla.edu'
        );
    }

    /**
     *  Function to translate the stored procedures results to Moodle 
     *  user results (named after the stored procedure).
     **/
    public function translate_ccle_course_instructorsget($reg) {
        return array(
            $this->get_config('remoteuserfield') => $reg['ucla_id'],
            'firstname' => $reg['first_name_person'],
            'lastname'  => $reg['last_name_person'],
            'email'     => $reg['ursa_email'],
            $this->get_config('fbremoteuserfield')  => $reg['bolid'] . '@ucla.edu'
        );
    }

    /**
     * Forces synchronisation of user enrolments with external database,
     * does not create new courses.
     *
     * @param object $user user record
     * @return void
     */
    public function sync_user_enrolments($user) {
        global $CFG, $DB;

        // we do not create courses here intentionally because it requires full sync and is slow
        if (!$this->get_config('dbtype') or !$this->get_config('dbhost') or !$this->get_config('remoteenroltable') or !$this->get_config('remotecoursefield') or !$this->get_config('remoteuserfield')) {
            return;
        }

        $table            = $this->get_config('remoteenroltable');
        $coursefield      = strtolower($this->get_config('remotecoursefield'));
        $userfield        = strtolower($this->get_config('remoteuserfield'));
        $rolefield        = strtolower($this->get_config('remoterolefield'));
        $subjfield        = strtolower($this->get_config('remotesubjfield'));

        $localrolefield   = $this->get_config('localrolefield');
        $localuserfield   = $this->get_config('localuserfield');
        $localcoursefield = $this->get_config('localcoursefield');

        $unenrolaction    = $this->get_config('unenrolaction');
        $defaultrole      = $this->get_config('defaultrole');

        $ignorehidden     = $this->get_config('ignorehiddencourses');

        if (!is_object($user) or !property_exists($user, 'id')) {
            throw new coding_exception('Invalid $user parameter in sync_user_enrolments()');
        }

        if (!property_exists($user, $localuserfield)) {
            debugging('Invalid $user parameter in sync_user_enrolments(), missing '.$localuserfield);
            $user = $DB->get_record('user', array('id'=>$user->id));
        }

        // create roles mapping
        $allroles = get_all_roles();
        if (!isset($allroles[$defaultrole])) {
            $defaultrole = 0;
        }

        $enrols = array();
        $instances = array();

        if (!$extdb = $this->db_init()) {
            // can not connect to database, sorry
            return;
        }

        // read remote enrols and create instances
        $sql = $this->db_get_sql($table, array($userfield=>$user->$localuserfield), array(), false);

        if ($rs = $extdb->Execute($sql)) {
            if (!$rs->EOF) {
                while ($fields = $rs->FetchRow()) {
                    $fields = array_change_key_case($fields, CASE_LOWER);
                    $fields = $this->db_decode($fields);

                    if (empty($fields[$coursefield])) {
                        // missing course info
                        continue;
                    }
                    
                    // START UCLA MODIFICATION CCLE-2275: Enrolment - View 
                    // Map term-srs to our entry in the requests table
                    list($term, $srs) = explode('-', $fields[$coursefield]);

                    $localcourseid = ucla_map_termsrs_to_courseid($term, $srs);
                    if (!$localcourseid) {
                        continue;
                    }

                    if (!$course = $DB->get_record('course', array($localcoursefield=>$localcourseid), 'id,visible')) {
                        continue;
                    }

                    if (!$course->visible and $ignorehidden) {
                        continue;
                    }

                    if (empty($fields[$rolefield])) {
                        if (!$defaultrole) {
                            // role is mandatory
                            continue;
                        }
                        $roleid = $defaultrole;
                    } else {
                        // Map a Registrar-provided role to a local moodle role
                        $roleid = get_moodlerole($fields[$rolefield], $fields['subj_area']);
                    }

                    if (empty($enrols[$course->id])) {
                        $enrols[$course->id] = array();
                    }
                    $enrols[$course->id][] = $roleid;

                    if ($instance = $DB->get_record('enrol', array('courseid'=>$course->id, 'enrol'=>'database'), '*', IGNORE_MULTIPLE)) {
                        $instances[$course->id] = $instance;
                        continue;
                    }

                    $enrolid = $this->add_instance($course);
                    $instances[$course->id] = $DB->get_record('enrol', array('id'=>$enrolid));
                }
            }
            $rs->Close();
            $extdb->Close();
        } else {
            // bad luck, something is wrong with the db connection
            $extdb->Close();
            return;
        }

        // enrol user into courses and sync roles
        foreach ($enrols as $courseid => $roles) {
            if (!isset($instances[$courseid])) {
                // ignored
                continue;
            }
            $instance = $instances[$courseid];

            if ($e = $DB->get_record('user_enrolments', array('userid'=>$user->id, 'enrolid'=>$instance->id))) {
                // reenable enrolment when previously disable enrolment refreshed
                if ($e->status == ENROL_USER_SUSPENDED) {
                    $this->update_user_enrol($instance, $user->id, ENROL_USER_ACTIVE);
                }
            } else {
                $roleid = reset($roles);
                $this->enrol_user($instance, $user->id, $roleid, 0, 0, ENROL_USER_ACTIVE);
            }

            if (!$context = get_context_instance(CONTEXT_COURSE, $instance->courseid)) {
                //weird
                continue;
            }
            $current = $DB->get_records('role_assignments', array('contextid'=>$context->id, 'userid'=>$user->id, 'component'=>'enrol_database', 'itemid'=>$instance->id), '', 'id, roleid');

            $existing = array();
            foreach ($current as $r) {
                if (in_array($r->roleid, $roles)) {
                    $existing[$r->roleid] = $r->roleid;
                } else {
                    role_unassign($r->roleid, $user->id, $context->id, 'enrol_database', $instance->id);
                }
            }
            foreach ($roles as $rid) {
                if (!isset($existing[$rid])) {
                    role_assign($rid, $user->id, $context->id, 'enrol_database', $instance->id);
                }
            }
        }

        // unenrol as necessary
        $sql = "SELECT e.*, c.visible AS cvisible, ue.status AS ustatus
                  FROM {enrol} e
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id
                  JOIN {course} c ON c.id = e.courseid
                 WHERE ue.userid = :userid AND e.enrol = 'database'";
        $rs = $DB->get_recordset_sql($sql, array('userid'=>$user->id));
        foreach ($rs as $instance) {
            if (!$instance->cvisible and $ignorehidden) {
                continue;
            }

            if (!$context = get_context_instance(CONTEXT_COURSE, $instance->courseid)) {
                //weird
                continue;
            }

            if (!empty($enrols[$instance->courseid])) {
                // we want this user enrolled
                continue;
            }

            // deal with enrolments removed from external table
            if ($unenrolaction == ENROL_EXT_REMOVED_UNENROL) {
                // unenrol
                $this->unenrol_user($instance, $user->id);

            } else if ($unenrolaction == ENROL_EXT_REMOVED_KEEP) {
                // keep - only adding enrolments

            } else if ($unenrolaction == ENROL_EXT_REMOVED_SUSPEND or $unenrolaction == ENROL_EXT_REMOVED_SUSPENDNOROLES) {
                // disable
                if ($instance->ustatus != ENROL_USER_SUSPENDED) {
                    $this->update_user_enrol($instance, $user->id, ENROL_USER_SUSPENDED);
                }
                if ($unenrolaction == ENROL_EXT_REMOVED_SUSPENDNOROLES) {
                    role_unassign_all(array('contextid'=>$context->id, 'userid'=>$user->id, 'component'=>'enrol_database', 'itemid'=>$instance->id));
                }
            }
        }
        $rs->close();

        // START UCLA MOD: Events for login-time enrolment
        $edata = new object();
        $edata->user = $user;
        $edata->enrols = $enrols;
        events_trigger('sync_user_enrolments_finished', $edata);
        // END UCLA MOD
    }

    /**
     * Forces synchronisation of all enrolments with external database.
     *
     * @param bool $verbose
     * @param Array $terms - if null, then all terms. if empty() then no terms.
     * @param int $singlecourse - if null, then ignored, otherwise the course.id 
     *      of the course you with to prepopulate.
     * @return int 0 means success, 1 db connect failure, 2 db read failure
     */
    public function sync_enrolments($verbose = false, $terms = null, $singlecourse = null) {
        global $CFG, $DB;

        // we do not create courses here intentionally because it requires full sync and is slow
        if (!$this->get_config('dbtype') or !$this->get_config('dbhost') or !$this->get_config('remoteenroltable') or !$this->get_config('remotecoursefield') or !$this->get_config('remoteuserfield')) {
            if ($verbose) {
                mtrace('User enrolment synchronisation skipped.');
            }
            return 0;
        }

        if ($verbose) {
            mtrace('Starting user enrolment synchronisation...');
        }

        if (!$extdb = $this->db_init()) {
            mtrace('Error while communicating with external enrolment database');
            return 1;
        }

        // we may need a lot of memory here
        @set_time_limit(0);
        raise_memory_limit(MEMORY_HUGE);

        // second step is to sync instances and users
        $table            = $this->get_config('remoteenroltable');
        $coursefield      = strtolower($this->get_config('remotecoursefield'));
        $userfield        = strtolower($this->get_config('remoteuserfield'));
        $rolefield        = strtolower($this->get_config('remoterolefield'));

        $localrolefield   = $this->get_config('localrolefield');
        $localuserfield   = $this->get_config('localuserfield');
        $localcoursefield = $this->get_config('localcoursefield');

        // CCLE-2910: Fallback method of identifying UNEX students
        $fbremoteuserfield = strtolower($this->get_config('fbremoteuserfield'));
        $fblocaluserfield = $this->get_config('fblocaluserfield');

        $unenrolaction    = $this->get_config('unenrolaction');
        $defaultrole      = $this->get_config('defaultrole');

        // create roles mapping
        $allroles = get_all_roles();
        if (!isset($allroles[$defaultrole])) {
            $defaultrole = 0;
        }
        $roles = array();
        foreach ($allroles as $role) {
            $roles[$role->$localrolefield] = $role->id;
        }

        /** UCLA MODIFIATION CCLE-2275: Disabling core moodle sync
        // get a list of courses to be synced that are in external table
        $externalcourses = array();
        $sql = $this->db_get_sql($table, array(), array($coursefield), true);
        if ($rs = $extdb->Execute($sql)) {
            if (!$rs->EOF) {
                while ($mapping = $rs->FetchRow()) {
                    $mapping = reset($mapping);
                    $mapping = $this->db_decode($mapping);
                    if (empty($mapping)) {
                        // invalid mapping
                        continue;
                    }
                    $externalcourses[$mapping] = true;
                }
            }
            $rs->Close();
        } else {
            mtrace('Error reading data from the external enrolment table');
            $extdb->Close();
            return 2;
        }
        // END UCLA MODIFICATION CCLE-2275    **/

        // UCLA MODIFICATION CCLE-2275: Prepop uses different data
        // sources
        ucla_require_registrar();

        if ($terms === null) {
            if ($singlecourse === null) {
                if ($verbose) {
                    mtrace("Working for all terms.");
                }

                // No single course was provided, and no term was provided
                $courses = $DB->get_records('ucla_request_classes');
                $course_indexed = index_ucla_course_requests($courses, 'courseid');
                unset($courses);
            } else {
                if ($verbose) {
                    mtrace("Working for single course $singlecourse");
                }

                // Get a single course 
                $courses = ucla_get_course_info($singlecourse);

                $course_set = array();
                foreach ($courses as $courseinfo) {
                    $tbicourse = new stdclass();

                    $tbicourse->department = $courseinfo->subj_area;
                    $tbicourse->courseid = $singlecourse;
                    $tbicourse->term = $courseinfo->term;
                    $tbicourse->srs = $courseinfo->srs;
                    
                    $course_set[] = $tbicourse;
                }

                $course_indexed = array($singlecourse => $course_set);
            }
        } else if (!empty($terms)) {
            if ($verbose) {
                mtrace("Working for " . implode(' ', $terms));
            }

            $course_indexed = ucla_get_courses_by_terms($terms);
        }

        if (empty($course_indexed)) {
            return 0;
        }

        $enrolment_info = array();

        $failed_users = array();

        foreach ($course_indexed as $courseid => $set) {
            $externalcourses[$courseid] = true;

            foreach ($set as $course) {

                $regdata = array(array($course->term, $course->srs));

                $subjarea = $course->department;
                $localmap = $course->courseid;

                // grab the instructors... from a different data source
                $results = registrar_query::run_registrar_query(
                    'ccle_courseinstructorsget', $regdata
                );

                $instrs = $results[registrar_query::query_results];

                $otherroles = array();

                // We need to flatten out all the available profcodes
                foreach ($instrs as $instructor) {
                    $pc = $instructor['role'];
                    $otherroles[$pc] = $pc;
                }

                // Now we need to save the roles per course
                // TODO what should happen if in a crosslisted course a 
                // professor gets two different roles?
                $instructorcount = 0;
                foreach ($instrs as $instructor) {
                    // No need to enrol "THE STAFF" or "TA"
                    if (is_dummy_ucla_user($instructor['ucla_id'])) {
                        continue;
                    }

                    $user = $this->translate_ccle_course_instructorsget(
                        $instructor
                    );

                    $user[$rolefield] = $roles[role_mapping(
                        $instructor['role'],
                        $otherroles,
                        $subjarea
                    )];

                    $enrolment_info[$localmap][] = $user;
                    $instructorcount++;
                }

                // grab the roster... from a different data source
                $results = registrar_query::run_registrar_query(
                    'ccle_roster_class', $regdata
                );

                $roster = $results[registrar_query::query_results];
                    
                $studentcount = 0;
                foreach ($roster as $student) {
                    // Do something to make it into a friendly format for the
                    // next section...
                    $studentpr = 
                        get_student_pseudorole($student['enrl_stat_cd']);

                    if ($studentpr === false) {
                        continue;
                    }

                    $user = $this->translate_ccle_roster_class($student);

                    $user[$rolefield] = $roles[
                        get_moodlerole($studentpr, $subjarea)
                    ];

                    $enrolment_info[$localmap][] = $user;
                    $studentcount++;
                }

                if ($verbose) {
                    mtrace("Fetching data for course $courseid: " . $course->term 
                        . ' ' . $course->srs . " $instructorcount instructors $studentcount students");
                }
            }
        }

        // Notify someone of users without a logonid 
        if (!empty($failed_users)) {

        }

        // END CCLE-2275: Fetch users and limit courses to run sync on.

        $preventfullunenrol = empty($externalcourses);
        if ($preventfullunenrol and $unenrolaction == ENROL_EXT_REMOVED_UNENROL) {
            if ($verbose) {
                mtrace('  Preventing unenrolment of all current users, because it might result in major data loss, there has to be at least one record in external enrol table, sorry.');
            }
        }

        // first find all existing courses with enrol instance
        $existing = array();
        $sql = "SELECT c.id, c.visible, c.$localcoursefield AS mapping, e.id AS enrolid, c.shortname
                  FROM {course} c
                  JOIN {enrol} e ON (e.courseid = c.id AND e.enrol = 'database')";
        $rs = $DB->get_recordset_sql($sql); // watch out for idnumber duplicates
        foreach ($rs as $course) {
            if (empty($course->mapping)) {
                continue;
            }
            $existing[$course->mapping] = $course;
        }
        $rs->close();

        // add necessary enrol instances that are not present yet
        $params = array();
        $localnotempty = "";
        if ($localcoursefield !== 'id') {
            $localnotempty =  "AND c.$localcoursefield <> :lcfe";
            $params['lcfe'] = $DB->sql_empty();
        }
        $sql = "SELECT c.id, c.visible, c.$localcoursefield AS mapping, c.shortname
                  FROM {course} c
             LEFT JOIN {enrol} e ON (e.courseid = c.id AND e.enrol = 'database')
                 WHERE e.id IS NULL $localnotempty";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $course) {
            if (empty($course->mapping)) {
                continue;
            }
            if (!isset($externalcourses[$course->mapping])) {
                // course not synced
                continue;
            }
            if (isset($existing[$course->mapping])) {
                // some duplicate, sorry
                continue;
            }
            $course->enrolid = $this->add_instance($course);
            $existing[$course->mapping] = $course;
        }
        $rs->close();

        // free memory
        unset($externalcourses);

        // sync enrolments
        $ignorehidden = $this->get_config('ignorehiddencourses');
        $sqlfields = array($userfield);
        if ($rolefield) {
            $sqlfields[] = $rolefield;
        }
        
        // UCLA MOD CCLE-2924: Update user data with prepop.
        $user_caches = array();

        // These are fields that are arbitrarily updated.
        // THe auth plugins are not usable in determining what fields to update
        $updateuserfields = array('firstname', 'lastname', 'email');

        // This configuration value comes in days, so multiply
        // This value is in seconds, or is false which is 0, meaning instantly/always
        // 86400 == 60 * 60 * 24 (seconds in a day)
        $minuserupdatewait = $this->get_config('minuserupdatewaitdays') * 86400;
       
        // This may cause seconds/minutes of disparity
        $currtime = time();

        foreach ($existing as $course) {
            // CCLE-2275: Ignoring courses that are not selected to be
            // synchronized (such as courses in other terms)
            if (!isset($enrolment_info[$course->mapping])) {
                continue;
            }
            // End Modification CCLE-2275

            if ($ignorehidden and !$course->visible) {
                continue;
            }
            if (!$instance = $DB->get_record('enrol', array('id'=>$course->enrolid))) {
                continue; //weird
            }
            $context = get_context_instance(CONTEXT_COURSE, $course->id);


            // get current list of enrolled users with their roles
            $current_roles  = array();
            $current_status = array();
            $user_mapping   = array();

            // START UCLA MOD CCLE-2910: Fixing issue with UNEX students,
            // check for username
            $user_fallback  = array();

            $fallbacksqlselect = '';
            if (!empty($fblocaluserfield)) {
                $fallbacksqlselect = "u.$fblocaluserfield AS fallback, ";
            }

            $sql = "SELECT u.$localuserfield AS mapping, $fallbacksqlselect u.id, ue.status, ue.userid, ra.roleid
                      FROM {user} u
                      JOIN {user_enrolments} ue ON (ue.userid = u.id AND ue.enrolid = :enrolid)
                      JOIN {role_assignments} ra ON (ra.userid = u.id AND ra.itemid = ue.enrolid AND ra.component = 'enrol_database')
                     WHERE u.deleted = 0";
            $params = array('enrolid'=>$instance->id);
            if ($localuserfield === 'username') {
                $sql .= " AND u.mnethostid = :mnethostid";
                $params['mnethostid'] = $CFG->mnet_localhost_id;
            }
            $rs = $DB->get_recordset_sql($sql, $params);
            foreach ($rs as $ue) {
                $current_roles[$ue->userid][$ue->roleid] = $ue->roleid;
                $current_status[$ue->userid] = $ue->status;

                // This is for UNEX students
                if (!empty($ue->mapping)) {
                    $user_mapping[$ue->mapping] = $ue->userid;
                }

                if (isset($ue->fallback)) {
                    $user_fallback[$ue->fallback] = $ue->userid;
                }
            }
            $rs->close();

            // get list of users that need to be enrolled and their roles
            $requested_roles = array();

            // START UCLA MODIFICATION CCLE-2275: Prepopulate (ucla tinkering)
            if (!empty($enrolment_info[$course->mapping])) {
                foreach ($enrolment_info[$course->mapping] as $fields) {
                    $usersearch = array();

                    if ($localuserfield === 'username') {
                        $usersearch['mnethostid'] = $CFG->mnet_localhost_id; 
                        $usersearch['deleted'] = 0;
                    }

                    $fields = array_change_key_case($fields, CASE_LOWER);
                    if (empty($fields[$userfield])) {
                        //user identification is mandatory!
                    }
                    $mapping = $fields[$userfield];

                    if (!empty($fblocaluserfield)) {
                        $fallback = $fields[$fblocaluserfield];
                    }

                    if (empty($user_mapping[$mapping]) || (!empty($fblocaluserfield) && empty($user_fallback[$fallback]))) {
                        // Find the user from our database
                        $sqlparams = array();
                        $sqlbuilder = array();
                        foreach ($usersearch as $f => $v) {
                            $sqlbuilder[] = "$f = ?";
                            $sqlparams[] = $v;
                        }

                        $searchstr = "$localuserfield = ?";
                        $sqlparams[] = $mapping;

                        if (!empty($fblocaluserfield)) {
                            $searchstr = "($searchstr OR $fblocaluserfield = ?)";
                            $sqlparams[] = $fallback;
                        } 

                        $sqlbuilder[] = $searchstr;
                        $usersql = implode(' AND ', $sqlbuilder);

                        $user = $DB->get_record_select('user', $usersql, $sqlparams, 
                            "id, $localuserfield, $fblocaluserfield", IGNORE_MULTIPLE);

                        if (!$user) {
                            // UCLA MODIFICATION CCLE-2275: Pre-populate needs
                            // to create users that do not exist.
                            // user does not exist or was deleted
                            // Stolen from user/editadvanced.php
                            $user = new stdclass();
                            $user->confirmed = 1;
                            $user->timecreated = time();
                            $user->password = '';
                            $user->auth = 'shibboleth';
                            $user->mnethostid = $CFG->mnet_localhost_id;
                            
                            // This will fill in user fields with stored procedure
                            // data such as firstname lastname
                            foreach ($fields as $k => $v) {
                                if ($k == $fbremoteuserfield) {
                                    $user->{$fblocaluserfield} = $v;
                                } else if ($k == $userfield) {
                                    $user->{$localuserfield} = $v;
                                } else {
                                    $user->{$k} = $v;
                                }
                            }

                            try {
                                $user->id = user_create_user($user);
                            } catch (dml_exception $e) {
                                mtrace("Skipping enrollments for "   
                                    . $user->username);
                                mtrace($e->debuginfo);
                                continue;
                            }

                            $user_cache[$user->id] = $user;
                        }
                        
                        $userid = $user->id;

                        // Update our local DB with new identifying information
                        // since we either only came from user field or fallback
                        // field
                        $needsupdate = false;
                        if (empty($user->{$localuserfield})) {
                            $user->{$localuserfield} = $mapping;
                            mtrace("Updating user $userid: $localuserfield $mapping");
                            $needsupdate = true;
                        }

                        if (empty($user->{$fblocaluserfield})) {
                            $user->{$fblocaluserfield} = $fallback;
                            mtrace("Updating user $userid: $fblocaluserfield $fallback");
                            $needsupdate = true;
                        }

                        if ($needsupdate) {
                            $DB->update_record('user', $user);
                        }

                        $user_mapping[$mapping] = $userid;
                        if (!empty($fblocaluserfield)) {
                            $user_fallback[$fallback] = $userid;
                        }
                    } else {
                        // CCLE-2910: adding fallback for UNEX students
                        if (!empty($fblocaluserfield)) {
                            $userid = $user_fallback[$fallback];
                        } else {
                            $userid = $user_mapping[$mapping];
                        }
                    }

                    // CCLE-2924: Update users: Match the user and update information if needed.
                    if (!isset($user_cache[$userid])) {
                        $user_cache[$userid] = $DB->get_record('user', array('id' => $userid));
                    }

                    // This clone might not be necessary, but this is useful for debugging purposes...
                    $userinfo = clone($user_cache[$userid]);
                    $needsupdate = false;

                    $updatedebugstr = '';

                    // Go through updater fields and sync with registrar
                    foreach ($updateuserfields as $updateuserfield) {
                        if (!empty($fields[$updateuserfield]) && $userinfo->{$updateuserfield} != $fields[$updateuserfield]) {
                            if (!empty($updatedebugstr)) {
                                $updatedebugstr .= "\n";
                            }

                            $updatedebugstr .= "Updating user $userid data: "
                                . "$updateuserfield [{$userinfo->{$updateuserfield}}] "
                                . "=> [{$fields[$updateuserfield]}]";

                            $userinfo->{$updateuserfield} = $fields[$updateuserfield];

                            $needsupdate = true;
                        }
                    }

                    if ($needsupdate) {
                        if ($currtime - $userinfo->lastaccess > $minuserupdatewait) {
                            if ($verbose) {
                                mtrace($updatedebugstr);
                            }

                            user_update_user($userinfo);

                            // If the clone() is not above, then this line is not necessary
                            $user_cache[$userid] = $userinfo;
                        }  else if ($verbose) {
                            // If the clone() is not above, this debugging message won't work
                            $origuserinfo = $user_cache[$userid];

                            $userstr = fullname($origuserinfo) . ' ' . $origuserinfo->email;
                            $remoteuserstr = fullname($userinfo) . ' ' . $userinfo->email;

                            mtrace('User data (' . $userstr 
                                . ') does not match externaldb (' . $remoteuserstr 
                                . '), but ignoring (minuserupdatewaitdays)');
                        }
                    }

                    // Since we cloned, we want to clear memory
                    unset($userinfo);

                    if (empty($fields[$rolefield]) or !isset($roles[$fields[$rolefield]])) {
                        if (!$defaultrole) {
                            // role is mandatory
                            continue;
                        }
                        $roleid = $defaultrole;
                    } else {
                        $roleid = $roles[$fields[$rolefield]];
                    }

                    $requested_roles[$userid][$roleid] = $roleid;
                }
            }
            unset($user_mapping);
            unset($user_fallback);

            // enrol all users and sync roles
            foreach ($requested_roles as $userid=>$userroles) {
                foreach ($userroles as $roleid) {
                    if (empty($current_roles[$userid])) {
                        $this->enrol_user($instance, $userid, $roleid, 0, 0, ENROL_USER_ACTIVE);
                        $current_roles[$userid][$roleid] = $roleid;
                        $current_status[$userid] = ENROL_USER_ACTIVE;
                        if ($verbose) {
                            mtrace("  enrolling: $userid ==> $course->shortname as ".$allroles[$roleid]->shortname);
                        }
                    }
                }

                // assign extra roles
                foreach ($userroles as $roleid) {
                    if (empty($current_roles[$userid][$roleid])) {
                        role_assign($roleid, $userid, $context->id, 'enrol_database', $instance->id);
                        $current_roles[$userid][$roleid] = $roleid;
                        if ($verbose) {
                            mtrace("  assigning roles: $userid ==> $course->shortname as ".$allroles[$roleid]->shortname);
                        }
                    }
                }

                // unassign removed roles
                foreach($current_roles[$userid] as $cr) {
                    if (empty($userroles[$cr])) {
                        role_unassign($cr, $userid, $context->id, 'enrol_database', $instance->id);
                        unset($current_roles[$userid][$cr]);
                        if ($verbose) {
                            mtrace("  unsassigning roles: $userid ==> $course->shortname");
                        }
                    }
                }

                // reenable enrolment when previously disable enrolment refreshed
                if ($current_status[$userid] == ENROL_USER_SUSPENDED) {
                    $this->update_user_enrol($instance, $userid, ENROL_USER_ACTIVE);
                    if ($verbose) {
                        mtrace("  unsuspending: $userid ==> $course->shortname");
                    }
                }
            }

            // deal with enrolments removed from external table
            if ($unenrolaction == ENROL_EXT_REMOVED_UNENROL) {
                if (!$preventfullunenrol) {
                    // unenrol
                    foreach ($current_status as $userid=>$status) {
                        if (isset($requested_roles[$userid])) {
                            continue;
                        }
                        $this->unenrol_user($instance, $userid);
                        if ($verbose) {
                            mtrace("  unenrolling: $userid ==> $course->shortname");
                        }
                    }
                }

            } else if ($unenrolaction == ENROL_EXT_REMOVED_KEEP) {
                // keep - only adding enrolments

            } else if ($unenrolaction == ENROL_EXT_REMOVED_SUSPEND or $unenrolaction == ENROL_EXT_REMOVED_SUSPENDNOROLES) {
                // disable
                foreach ($current_status as $userid=>$status) {
                    if (isset($requested_roles[$userid])) {
                        continue;
                    }
                    if ($status != ENROL_USER_SUSPENDED) {
                        $this->update_user_enrol($instance, $userid, ENROL_USER_SUSPENDED);
                        if ($verbose) {
                            mtrace("  suspending: $userid ==> $course->shortname");
                        }
                    }
                    if ($unenrolaction == ENROL_EXT_REMOVED_SUSPENDNOROLES) {
                        role_unassign_all(array('contextid'=>$context->id, 'userid'=>$userid, 'component'=>'enrol_database', 'itemid'=>$instance->id));
                        if ($verbose) {
                            mtrace("  unsassigning all roles: $userid ==> $course->shortname");
                        }
                    }
                }
            }
        }

        // close db connection
        $extdb->Close();

        if ($verbose) {
            mtrace('...user enrolment synchronisation finished.');
        }

        // START UCLA MOD: adding event to prepop 
        $edata = new object();
        $edata->courses = $course_indexed;
        events_trigger('sync_enrolments_finished', $edata);
        // END UCLA MOD

        return 0;
    }

    /**
     * Performs a full sync with external database.
     *
     * First it creates new courses if necessary, then
     * enrols and unenrols users.
     *
     * @param bool $verbose
     * @return int 0 means success, 1 db connect failure, 4 db read failure
     */
    public function sync_courses($verbose = false) {
        global $CFG, $DB;

        // make sure we sync either enrolments or courses
        if (!$this->get_config('dbtype') or !$this->get_config('dbhost') or !$this->get_config('newcoursetable') or !$this->get_config('newcoursefullname') or !$this->get_config('newcourseshortname')) {
            if ($verbose) {
                mtrace('Course synchronisation skipped.');
            }
            return 0;
        }

        if ($verbose) {
            mtrace('Starting course synchronisation...');
        }

        // we may need a lot of memory here
        @set_time_limit(0);
        raise_memory_limit(MEMORY_HUGE);

        if (!$extdb = $this->db_init()) {
            mtrace('Error while communicating with external enrolment database');
            return 1;
        }

        // first create new courses
        $table     = $this->get_config('newcoursetable');
        $fullname  = strtolower($this->get_config('newcoursefullname'));
        $shortname = strtolower($this->get_config('newcourseshortname'));
        $idnumber  = strtolower($this->get_config('newcourseidnumber'));
        $category  = strtolower($this->get_config('newcoursecategory'));

        $sqlfields = array($fullname, $shortname);
        if ($category) {
            $sqlfields[] = $category;
        }
        if ($idnumber) {
            $sqlfields[] = $idnumber;
        }
        $sql = $this->db_get_sql($table, array(), $sqlfields);
        $createcourses = array();
        if ($rs = $extdb->Execute($sql)) {
            if (!$rs->EOF) {
                while ($fields = $rs->FetchRow()) {
                    $fields = array_change_key_case($fields, CASE_LOWER);
                    $fields = $this->db_decode($fields);
                    if (empty($fields[$shortname]) or empty($fields[$fullname])) {
                        if ($verbose) {
                            mtrace('  error: invalid external course record, shortname and fullname are mandatory: ' . json_encode($fields)); // hopefully every geek can read JS, right?
                        }
                        continue;
                    }
                    if ($DB->record_exists('course', array('shortname'=>$fields[$shortname]))) {
                        // already exists
                        continue;
                    }
                    // allow empty idnumber but not duplicates
                    if ($idnumber and $fields[$idnumber] !== '' and $fields[$idnumber] !== null and $DB->record_exists('course', array('idnumber'=>$fields[$idnumber]))) {
                        if ($verbose) {
                            mtrace('  error: duplicate idnumber, can not create course: '.$fields[$shortname].' ['.$fields[$idnumber].']');
                        }
                        continue;
                    }
                    if ($category and !$DB->record_exists('course_categories', array('id'=>$fields[$category]))) {
                        if ($verbose) {
                            mtrace('  error: invalid category id, can not create course: '.$fields[$shortname]);
                        }
                        continue;
                    }
                    $course = new stdClass();
                    $course->fullname  = $fields[$fullname];
                    $course->shortname = $fields[$shortname];
                    $course->idnumber  = $idnumber ? $fields[$idnumber] : NULL;
                    $course->category  = $category ? $fields[$category] : NULL;
                    $createcourses[] = $course;
                }
            }
            $rs->Close();
        } else {
            mtrace('Error reading data from the external course table');
            $extdb->Close();
            return 4;
        }
        if ($createcourses) {
            require_once("$CFG->dirroot/course/lib.php");

            $templatecourse = $this->get_config('templatecourse');
            $defaultcategory = $this->get_config('defaultcategory');

            $template = false;
            if ($templatecourse) {
                if ($template = $DB->get_record('course', array('shortname'=>$templatecourse))) {
                    unset($template->id);
                    unset($template->fullname);
                    unset($template->shortname);
                    unset($template->idnumber);
                } else {
                    if ($verbose) {
                        mtrace("  can not find template for new course!");
                    }
                }
            }
            if (!$template) {
                $courseconfig = get_config('moodlecourse');
                $template = new stdClass();
                $template->summary        = '';
                $template->summaryformat  = FORMAT_HTML;
                $template->format         = $courseconfig->format;
                $template->numsections    = $courseconfig->numsections;
                $template->hiddensections = $courseconfig->hiddensections;
                $template->newsitems      = $courseconfig->newsitems;
                $template->showgrades     = $courseconfig->showgrades;
                $template->showreports    = $courseconfig->showreports;
                $template->maxbytes       = $courseconfig->maxbytes;
                $template->groupmode      = $courseconfig->groupmode;
                $template->groupmodeforce = $courseconfig->groupmodeforce;
                $template->visible        = $courseconfig->visible;
                $template->lang           = $courseconfig->lang;
                $template->groupmodeforce = $courseconfig->groupmodeforce;
            }
            if (!$DB->record_exists('course_categories', array('id'=>$defaultcategory))) {
                if ($verbose) {
                    mtrace("  default course category does not exist!");
                }
                $categories = $DB->get_records('course_categories', array(), 'sortorder', 'id', 0, 1);
                $first = reset($categories);
                $defaultcategory = $first->id;
            }

            foreach ($createcourses as $fields) {
                $newcourse = clone($template);
                $newcourse->fullname  = $fields->fullname;
                $newcourse->shortname = $fields->shortname;
                $newcourse->idnumber  = $fields->idnumber;
                $newcourse->category  = $fields->category ? $fields->category : $defaultcategory;

                $c = create_course($newcourse);
                if ($verbose) {
                    mtrace("  creating course: $c->id, $c->fullname, $c->shortname, $c->idnumber, $c->category");
                }
            }

            unset($createcourses);
            unset($template);
        }

        // close db connection
        $extdb->Close();

        if ($verbose) {
            mtrace('...course synchronisation finished.');
        }

        return 0;
    }

    protected function db_get_sql($table, array $conditions, array $fields, $distinct = false, $sort = "") {
        $fields = $fields ? implode(',', $fields) : "*";
        $where = array();
        if ($conditions) {
            foreach ($conditions as $key=>$value) {
                $value = $this->db_encode($this->db_addslashes($value));

                $where[] = "$key = '$value'";
            }
        }
        $where = $where ? "WHERE ".implode(" AND ", $where) : "";
        $sort = $sort ? "ORDER BY $sort" : "";
        $distinct = $distinct ? "DISTINCT" : "";
        $sql = "SELECT $distinct $fields
                  FROM $table
                 $where
                  $sort";

        return $sql;
    }

    /**
     * Tries to make connection to the external database.
     *
     * @return null|ADONewConnection
     */
    protected function db_init() {
        global $CFG;

        require_once($CFG->libdir.'/adodb/adodb.inc.php');

        // Connect to the external database (forcing new connection)
        $extdb = ADONewConnection($this->get_config('dbtype'));
        if ($this->get_config('debugdb')) {
            $extdb->debug = true;
            ob_start(); //start output buffer to allow later use of the page headers
        }

        $result = $extdb->Connect($this->get_config('dbhost'), $this->get_config('dbuser'), $this->get_config('dbpass'), $this->get_config('dbname'), true);
        if (!$result) {
            return null;
        }

        $extdb->SetFetchMode(ADODB_FETCH_ASSOC);
        if ($this->get_config('dbsetupsql')) {
            $extdb->Execute($this->get_config('dbsetupsql'));
        }
        return $extdb;
    }

    protected function db_addslashes($text) {
        // using custom made function for now
        if ($this->get_config('dbsybasequoting')) {
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace(array('\'', '"', "\0"), array('\\\'', '\\"', '\\0'), $text);
        } else {
            $text = str_replace("'", "''", $text);
        }
        return $text;
    }

    protected function db_encode($text) {
        $dbenc = $this->get_config('dbencoding');
        if (empty($dbenc) or $dbenc == 'utf-8') {
            return $text;
        }
        if (is_array($text)) {
            foreach($text as $k=>$value) {
                $text[$k] = $this->db_encode($value);
            }
            return $text;
        } else {
            return textlib_get_instance()->convert($text, 'utf-8', $dbenc);
        }
    }

    protected function db_decode($text) {
        $dbenc = $this->get_config('dbencoding');
        if (empty($dbenc) or $dbenc == 'utf-8') {
            return $text;
        }
        if (is_array($text)) {
            foreach($text as $k=>$value) {
                $text[$k] = $this->db_decode($value);
            }
            return $text;
        } else {
            return textlib_get_instance()->convert($text, $dbenc, 'utf-8');
        }
    }
}

