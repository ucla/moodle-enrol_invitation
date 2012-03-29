<?php 

defined('MOODLE_INTERNAL') || die();

/**
 *  The course creator class.
 *
 *  This creates courses from the course requestor. It can be configured to 
 *  build a certain subset of courses.
 *  TODO use local configuration vars instead of global
 *
 *  @author Yangmun Choi
 *  @package ucla
 *  @subpackage course_creator
 *  @copyright 2011 UCLA
 **/

// Exception... TODO see if the gravity of these are used properly
require_once($CFG->dirroot  . '/' . $CFG->admin . '/tool/uclacoursecreator/' 
    . 'course_creator_exception.class.php');

// Require requestor
require_once($CFG->dirroot  . '/' . $CFG->admin
    . '/tool/uclacourserequestor/lib.php');

// Require essential stuff... 
require_once($CFG->dirroot . '/course/lib.php');

// Required for categories
require_once($CFG->dirroot . '/course/editcategory_form.php');

require_once($CFG->dirroot . '/local/ucla/lib.php');

/**
 *  Course creator.
 **/
class uclacoursecreator {
    /** Stuff for Logging **/
    // This is the huge text that will email to admins.
    public $email_log = '';

    // Contains the log file pointer.
    private $log_fp;

    // The path for the output files as parsed from the configuration, 
    // or defaulting to dataroot.
    // defined in @see check_write()
    public $output_path;

    // Used to force failure at the end
    private $force_fail = false;

    // Set to true to hide output?
    private $no_send_mails = false;

    // Private identifier for this cron task
    private $db_id;

    /** Variants for the cron **/
    // The current term we are working for
    private $cron_term;

    // array Contains all the information for a current term
    /**
     *  requests - the list of ucla_request_classes that are hosts.
     *  instructors - the set of instructors.
     *  profcodes - an Array in an Array of profcodes in a course.
     *  created_courses - an Array of created course objects.
     *  activate - courses that we have to activate (all courses)
     *  url_info - the information to send to MyUCLA
     *  local_emails - the local emails from the mdl_user tables
     *  course_mapper - an Array with SRS => mdl_course.id
     **/
    private $cron_term_cache = array();

    // Caches
    private $categories_cache = array();
    private $course_defaults = array();
    private $publicprivate_enabled;

    /** Non Variants **/
    // These are just simple caches.
    private $shell_date;
    private $full_date;
    private $timestamp;

    // Terms to be used by course creator.
    private $terms_list = null;

    // Contains the information regarding subject area long names.
    private $subj_trans;

    // Email parsing cache
    private $parsed_param = array();

    // Email file location cache...
    private $email_prefix;
    private $email_suffix;

    // Email file default
    private $default_email_file;

    // These are the requests of courses that have been built
    private $built_requests;

    // Note: There are dynamically generated fields for this class, which
    // contain references to the enrollment object.

    // This is the course creator cron
    function cron() {
        global $CFG;

        if (!$this->get_config('cron_enabled')) {
            // TODO Test if this logic works
            static $cronuser;

            if (isset($cronuser)) {
                echo get_string('cron_quit_out', 'tool_uclacoursecreator');
                return false;
            }
        }

        $this->println('Running prerequisite checks...');

        // Make sure our email configurations are valid
        $this->figure_email_vars();

        // Check that we have our registrar wrapper functions
        ucla_require_registrar();

        // Validate that we have the registrar queries we need all wrapped up
        $registrars = array('ccle_getclasses', 'ccle_courseinstructorsget');

        foreach ($registrars as $registrar_class) {
            if (!registrar_query::get_registrar_query($registrar_class)) {
                throw new moodle_exception('Missing ' . $reg_filename);
            }
        }

        try {
            // This will check if we can write and lock
            $this->handle_locking(true);
        } catch (course_creator_exception $e) {
            $this->debugln($e->getMessage());

            $this->finish_cron();
            return false;
        }

        /** Run the course creator **/
        // Figure out what terms we're running
        $termlist = $this->get_terms_creating();

        if (empty($termlist)) {
            $this->debugln('No terms to deal with, exiting...');

            $this->handle_locking(false);
            $this->finish_cron();

            return false;
        }

        $this->course_defaults = get_config('moodlecourse');

        $this->println("---- Course Creator run at {$this->full_date} "
            . "({$this->shell_date}) -----");

        foreach ($termlist as $work_term) {
            try {
                // Flush the cache
                $this->start_cron_term($work_term);

                // Get stuff from course requestor
                $retrieved = $this->retrieve_requests();

                // If we actually have entries to process
                // Hooray a list.
                if ($retrieved) {
                    // Get official data from Registrar
                    $this->requests_to_rci();

                    // Prepare the categories
                    $this->prepare_categories();

                    // Create the courses
                    $this->create_courses();

                    // Update the URLs for the Registrar
                    $this->update_MyUCLA_urls();

                    // Send emails to the instructors
                    $this->send_emails();
                }

                if ($this->force_fail) {
                    throw new course_creator_exception(
                        '** Debugging break **'
                    );
                }
                
                // This will mark term as finished, insert entries into 
                // ucla_reg_classinfo, mark the entries in 
                // ucla_request_classes as done
                $this->mark_cron_term(true);
            } catch (moodle_exception $e) {
                $this->debugln($e->getMessage());
                $this->debugln($e->debuginfo);

                // Since the things were not processed, try to revert the
                // changes in the requestor
                $this->mark_cron_term(false);

                try {
                    throw $e;
                } catch (course_creator_exception $cce) {
                    // Do nothing, this is safe
                }
            } catch (Exception $e) {
                // This is a much more serious error
                $this->debugln($e->getMessage());

                throw $e;
            }
        }

        $this->handle_locking(false);

        $this->finish_cron();
    }

    /** ******************* **/
    /*  Debugging Functions  */
    /** ******************* **/

    /**
     *  Will print to course creator log.
     *  @param $mesg The message.
     **/
    function printl($mesg) {
        if (debugging()) {
            echo $mesg;
        }

        if (!isset($this->log_fp)) {
            $this->log_fp = $this->get_course_creator_log_fp();
        }

        fwrite($this->log_fp, $mesg);
    }

    /**
     *  Will print to the designated course creator log with newline appended.
     *  @param $mesg The message to print.
     **/
    function println($mesg='') {
        $this->printl($mesg . "\n");
    }

    /**
     *  Will output to the email log.
     *  @param $mesg The message to output.
     **/
    function emailln($mesg='') {
        $this->email_log = $this->email_log . $mesg . "\n";
    }
    
    /**
     *  Shortcut function to print to both the log and the email.
     *  @param $mesg The message to print to log and email.
     **/
    function debugln($mesg='') {
        $this->println($mesg);
        $this->emailln($mesg);
    }

    /** ************************ **/
    /*  Accessor Functions        */
    /** ************************ **/
   
    /**
     *  This returns the course creator log's file pointer.
     *
     *  @see printl
     *
     *  @return file pointer The log file pointer.
     **/
    function get_course_creator_log_fp() {
        if (isset($this->log_fp)) {
            return $this->log_fp;
        }

        // This will set where all our files will be thrown
        // @throws course_creator_exception
        if (!isset($this->output_path)) {
            $this->check_write();
        }

        $this->make_dbid();

        // Do we want to save this?
        $log_file = $this->output_path . '/course_creator.' 
            . $this->shell_date . '.' . $this->db_id . '.log';
    
        $this->log_fp = fopen($log_file, 'a');
        $this->log_file = $log_file;

        return $this->log_fp;
    }

    /**
     *  Aliases for @see get_registrar_translation().
     **/
    function get_subj_area_translation($subjarea) {
        return $this->get_registrar_translation('ucla_reg_subjectarea',
            $subjarea, 'subjarea', 'subj_area_full');
    }

    function get_division_translation($division) {
        return $this->get_registrar_translation('ucla_reg_division', 
            $division, 'code', 'fullname');
    }

    /**
     *  Returns the long name of the target if found.
     *  This is used for getting the long name for divisions and
     *  subject areas.
     *
     *  May alter the state of the object.
     *
     *  @param $table The table to use.
     *  @param $target The string we are translating.
     *  @param $from_field The field that we are using to search if the 
     *      target exists.
     *  @param $to_field The field that we are going to return if we find
     *      the target entry.
     *  @return The long name of the target, or the short name if no long 
     *      name was found.
     **/
    function get_registrar_translation($table, $target, $from_field, 
            $to_field) {
        global $DB;

        if (!isset($this->reg_trans[$table]) || $this->reg_trans == null) {
            $this->reg_trans = array();

            $indexed_sa = array();

            $translations = $DB->get_records($table);

            foreach ($translations as $translate) {
                $indexed_sa[$translate->$from_field] = 
                    $translate->$to_field;
            }

            $this->reg_trans[$table] = $indexed_sa;
        }

        if (!isset($this->reg_trans[$table][$target])) { 
            return $target;
        } 

        // format result nicely, not in all caps
        return ucla_format_name($this->reg_trans[$table][$target]);
    }

    /**
     *  Returns the current term we are working for.
     *
     *  @return string The term we are working on or false 
     *      if the code has not been properly set.
     **/
    function get_cron_term() {
        if (!isset($this->cron_term)) {
            return false;
        }

        return $this->cron_term;
    }

    /**
     *  Returns an Array of terms to work for. If {@see set_term_list()} 
     *  is used, then it will return whatever has been set already.
     *
     *  Wrapper for @see figure_terms().
     *
     *  May change state of object.
     *
     *  @return Array of terms 
     **/
    function get_terms_creating() {
        if (!isset($this->terms_list)) {
            $this->figure_terms();
        }

        return $this->terms_list;
    }

    /**
     *  Return if the instructor should be emailed to people.
     *
     *  @param mixed $instructor The instructor from ccle_CourseInstructorGet
     *  @param array $profcode_set A set of profcodes for the course.
     *  @param string $subj_area The subject area of the request.
     *  @return boolean If the instructor is viewable or not.
     **/
    function get_viewable_status($instructor, $profcode_set, $subj_area) {
        if (function_exists('role_mapping')) {
            $printstr = $instructor->last_name_person . ' has ' 
                . $instructor->role . ' which is moodlerole ';

            try {
                $moodleroleid = role_mapping($instructor->role, $profcode_set,
                    $subj_area);
            } catch (moodle_exception $e) {
                var_dump($e);
                throw $e;
            }

            $printstr .= $moodleroleid . '. ';

            $req_cap = 'moodle/course:update';
            if (!isset($this->capcache)) {
                $caps = get_roles_with_capability($req_cap);
                $this->capcache = $caps;
            }

            
            // Do a local cache
            $res = isset($this->capcache[$moodleroleid]);

            if ($res) {
                $printstr .= "Has $req_cap, will be emailed.";
            } else {
                $printstr .= "Does not have capability [$req_cap], "
                    . "not emailing.";
            }

            $this->println($printstr);

            return $res;
        } else {
            debugging('No role_mapping function exists.');
            return false;
        }
    }

    /** 
     *  Link to another external tool...yay.
     **/
    function get_myucla_urlupdater() {
        global $CFG;

        $cn = 'myucla_urlupdater';

        $path = $CFG->dirroot . '/' . $CFG->admin . '/tool/myucla_url/'
            . $cn . '.class.php';

        if (class_exists($cn)) {
            return new $cn();
        }

        if (file_exists($path)) {
            require_once($path);
        }

        return new $cn();
    }

    /**
     *  Return the current cache.
     *  @return Array The current state of the cache.
     **/
    function dump_cache() {
        return $this->cron_term_cache;
    }

    /** *********** **/
    /*  Closers      */
    /** *********** **/

    /**
     *  This will close the file pointer.
     *
     *  Will change the state of the function.
     **/
    function close_log_file_pointer() {
        if (isset($this->log_fp)) {
            fclose($this->log_fp);

            unset($this->log_fp);

            return true;
        }

        return false;
    }

    /** *********** **/
    /*  Modifiers    */
    /** *********** **/

    /**
     *  Sets the current term, validates the term.
     *
     *  @param $term The term to set the current term to.
     *  @return boolean If the term has been set.
     **/
    function set_cron_term($term) {
        if (isset($this->cron_term)) {
            return false;
        }

        if (!$this->validate_term($term)) {
            return false;
        }

        $this->cron_term = $term;

        return true;
    }
    
    /**
     *  Sets the terms to be run.
     *
     *  Changes the state of the function.
     *
     *  @param $terms_list The array of terms to run for.
     **/
    function set_term_list($terms_list) {
        if ($terms_list != null && !empty($terms_list)) {
            $this->terms_list = $terms_list;
        } else {
            $this->figure_terms();
        }
    }

    /**
     *  Forces a fail condition to activate at the end of a term
     *
     *  @param $bool boolean Is force fail on 
     **/
    function set_autofail($bool) {
        $this->force_fail = $bool;
    }

    /**
     *  Allows mails to be sent to requestors and instructors.
     *  @param  $b  true = no mails sent, false = mails sent

     **/
    function set_mailer($b) {
        $this->no_send_mails = $b;
    }

    function send_mails() {
        return !$this->no_send_mails;
    }

    /** ************************** **/
    /*  Cron-Controller Functions   */
    /** ************************** **/

    /**
     *  Set the term that we are working on.
     *  Flush the current state of the course creator.
     *  @param $term The term to work for.
     **/
    function start_cron_term($term) {
        global $DB;

        $this->flush_cron_term();

        if (!$this->set_cron_term($term)) {
            throw new course_creator_exception(
                'Could not set the term [' . $term . ']'
            );
        }
            
        // this will let both build and rebuild be built
        $conds = array(
            'action' => UCLA_COURSE_TOBUILD,
            'term' => $term
        );

        $DB->set_field('ucla_request_classes', 'action', 
            UCLA_COURSE_LOCKED, $conds);

        $this->println("-------- Starting $term ---------");
    }

    /**
     *  Will remove all previously set term information.
     *
     *  Will change the state of the object.
     **/
    function flush_cron_term() {
        unset($this->cron_term_cache);

        $this->cron_term_cache = array();

        unset($this->cron_term);
    }

    /**
     *  This will mark the entries as either finished or reset.
     *
     *  @param $done If we should mark the requests as done or reset them
     **/
    function mark_cron_term($done) {
        global $DB;

        $thiscronterm = $this->get_cron_term();
        if (!$thiscronterm) {
            return false;
        }

        $this->println("Determining what happened for $thiscronterm...");

        // Do something with these requests
        $action_ids = array();

        // Save a config setting
        $reverting = $this->get_config('revert_failed_cron');

        if (isset($this->cron_term_cache['created_courses'])) {
            $created_courses =& $this->cron_term_cache['created_courses'];
        }

        // We're going to attempt to delete a course, and if we fail,
        // save it somewhere.
        if (!empty($this->cron_term_cache['requests'])) {
            $requests =& $this->cron_term_cache['requests'];
        } else {
            $requests = array();
        }

        // Some stats and counters
        $numdeletedcourses = 0;
        $failed = 0;

        foreach ($requests as $reqkey => $request) {
            $rid = $request->id;

            $action = UCLA_COURSE_FAILED;
            if (isset($created_courses[$reqkey])) {
                // The course got built, but the process was interrupted at
                // one point... but why?
                $course = $created_courses[$reqkey];
                $courseid = $course->id;

                if ($done) {
                    $action = UCLA_COURSE_BUILT;

                    // Save the created course for the trigger later...
                    $this->build_courseids[$courseid] = $courseid;
                } else {
                    if ($reverting) {
                        ob_start();
                        $result = delete_course($courseid);
                        $delete_results = ob_get_clean();

                        $action = UCLA_COURSE_TOBUILD;

                        $numdeletedcourses = true;

                        $this->debugln($delete_results);
                    } else {
                        $this->debugln('leaving course ' . $courseid . ' '
                            . $course->shortname);
                    }
                }
            } else {
                $this->debugln("! Did not create a course for $reqkey");
            }

            if (empty($action_ids[$action])) {
                $action_ids[$action] = array();
            }

            $action_ids[$action][$rid] = $rid;
        }

        if ($numdeletedcourses > 0) {
            // Update course count in categories.
            fix_course_sortorder();
        }

        // Mark these entries as failed
        // So far, the only possible actions are
        // 'rebuild' and 'failed'
        foreach ($action_ids as $action => $ids) {
            if (!empty($ids)) {
                list($sql_in, $params) = $DB->get_in_or_equal($ids);

                $sql_where = 'id ' . $sql_in;

                $DB->set_field_select('ucla_request_classes', 'action', 
                    $action, $sql_where, $params);

                $this->debugln('Marked ' . count($params) . ' request(s) '
                    . 'as \'' . $action . '\'.');
            }
        }

        $this->println('-------- Finished ' . $this->get_cron_term() 
            . ' --------');

        if ($done) {
            // Update this other table...
            $this->insert_term_rci();

            // Save this build for the event triggered when course creator
            // is finished
            $this->save_created_courses();

            // Prep stuff for requestors
            $this->queue_requestors();
        }

        return true;
    }

    /** ****************** **/
    /*  Cron Functionality  */
    /** ****************** **/

    /**
     *  Calculates the course requests for a particular term. 
     *  Also maintains the crosslisted relationships.
     *  
     *  Will alter the state of the object.
     **/
    function retrieve_requests() {
        global $DB;

        $term = $this->get_cron_term();
        if (!$term) {
            throw new course_creator_exception('Term not set properly!');
        }

        $sql_params = array(
            'action' => UCLA_COURSE_LOCKED,
            'term' => $term
        );

        $course_requests = $DB->get_records('ucla_request_classes', 
            $sql_params);

        if (empty($course_requests)) {
            return false;
        }

        $this->debugln('--- ' . count($course_requests) . ' requests for ' 
            . $term . ' ---');

        // Figure out crosslists and filter out faulty requests
        foreach ($course_requests as $key => $course_request) {
            $srs = trim($course_request->srs);
           
            if (!ucla_validator('srs', $srs)) {
                $this->debugln('Faulty SRS: ' 
                    . $course_request->course
                    . ' [' . $srs . ']');

                unset($course_requests[$key]);
                continue;
            }
        }

        // Re-index and save the the courses for the rest of the
        // cron run
        $course_sets = array();
        foreach ($course_requests as $cr) {
            $setid = $cr->setid;
            if (empty($course_sets[$setid])) {
                $course_sets[$setid] = array();
            }

            $course_sets[$setid][make_idnumber($cr)] = $cr;
            $this->cron_term_cache['requests']
                [self::cron_requests_key($cr)] = $cr;
        }

        $this->debugln('--- ' . count($course_sets) . ' courses expected'
            . ' ---');

        unset($course_requests);
   
        // Print out the requests that we're going to work with
        foreach ($course_sets as $courseset) {
            $arrcourseset = array();

            foreach ($courseset as $key => $course) {
                $arrcourseset[$key] = get_object_vars($course);
            }

            $hostcoursekey = set_find_host($arrcourseset);
            $hostcourse = $courseset[$hostcoursekey];
            unset($courseset[$hostcoursekey]);

            $course = $this->trim_object($hostcourse);
            $this->debugln('-  ' 
                . self::courseinfoline($course));

            foreach ($courseset as $course) {
                $this->debugln(' + ' 
                    . self::courseinfoline($course));
            }
        }

        $this->println('  Finished processing requests.');

        return true;
    }

    /**
     *  Convenience function.
     **/
    static function courseinfoline($course) {
        return $course->term . ' ' . $course->srs .  ' '
            . $course->department . ' ' . $course->course;
    }

    /**
     *  Convenience wrapper function to use a globalized-seek key for 
     *  cron_term_cache['requests']
     **/
    static function cron_requests_key($course) {
        return make_idnumber($course);
    }

    /**
     *  Trim the requests to term srs.
     *  This is only used for sending data to the Registrar stored procedures.
     *  Also converts these objects to Array();
     *
     *  Will change the state of the object
     **/
    function trim_requests() {
        if (empty($this->cron_term_cache['requests'])) {
            throw new course_creator_exception('Requests does not exist.');
        }

        $trim_requests = array();

        foreach ($this->cron_term_cache['requests'] as $request) {
            $term = $request->term;
            $srs = $request->srs;

            $key = $term . '-' . $srs;

            // Note that ordering is important
            $trim_requests[$key] = array($term, $srs);
        }

        $this->cron_term_cache['trim_requests'] = $trim_requests;
    }

    /**
     *  Take the requests and get the data for the courses from the Registrar.
     *
     *  @see registrar_ccle_getclasses
     **/
    function requests_to_rci() {
        if (!isset($this->cron_term_cache['trim_requests'])) {
            $this->trim_requests();
        }

        $tr = $this->cron_term_cache['trim_requests'];

        $requests =& $this->cron_term_cache['requests'];

        // Run the Stored Procedure with the data
        $return = registrar_query::run_registrar_query('ccle_getclasses',
            $tr, true);

        foreach ($return as $k => $v) {
            $v = (object)$v;
            unset($return[$k]);
            $return[self::cron_requests_key($v)] = $v;
        }

        $this->println('  Fetching course information from registrar...');
        foreach ($requests as $tkey => $request) {
            if (!isset($return[$tkey])) {
                $this->debugln('Registrar did not find a course: '
                    . $tkey);
                continue;
            }

            $ob_re = $return[$tkey];
        }

        $this->println('. ' . count($return) 
            . ' courses exist at Registrar.');

        $this->cron_term_cache['term_rci'] = $return;
    }

    /**
     *  Makes categories.
     **/
    function prepare_categories() {
        if (empty($this->cron_term_cache['term_rci'])) {
            throw new course_creator_exception(
                'No request data obtained from the Registrar.'
            );
        }

        $requests = $this->cron_term_cache['requests'];

        $nesting_order = array();

        $this->debugln('Preparing categories...');
        if ($this->get_config('make_division_categories')) {
            $this->debugln('. Nesting division categories.');
            $nesting_order[] = 'division';
        } 

        $nesting_order[] = 'subj_area';

        $rci_courses =& $this->cron_term_cache['term_rci'];
   
        // Get all categories and index them
        $id_categories = get_categories();
        
        // Add "root" to available categories
        $fakeroot = new stdclass();
        $fakeroot->name = 'Root';
        $fakeroot->id = 0;
        $fakeroot->parent = 'None';
        $id_categories[0] = $fakeroot;

        $name_categories = array();

        $forbidden_names = array();

        foreach ($id_categories as $cat) {
            // Note standard is same here #OOOO
            $catname = $cat->name . '-' . $cat->parent;
            if (isset($name_categories[$catname])) {
                $forbidden_names[$catname] = $catname;
            }

            $name_categories[$catname] = $cat;
        }


        $truecatreferences = array();
        foreach ($rci_courses as $reqkey => $rci_course) {
            if (!isset($requests[$reqkey]) 
                    || $requests[$reqkey]->hostcourse == 0) {
                continue;
            }

            $immediate_parent_catid = 0;

            foreach ($nesting_order as $type) {
                $field = trim($rci_course->$type);

                $function = 'get_' . $type . '_translation';

                if (!method_exists($this, $function)) {
                    // Should never run
                    throw new coding_exception($function
                        . ' does not exist.');
                }

                $trans = $this->$function($field);

                // Note that standard is same here #OOOO
                $namecheck = $trans . '-' . $immediate_parent_catid;

                if (isset($forbidden_names[$namecheck])) {
                    $this->debugln('! Category name: '
                        . $trans . ' is ambiguous as a '
                        . $type);

                    break;
                }

                // Not an existing category
                if (!isset($name_categories[$namecheck])) {
                    $newcategory = $this->new_category($trans,
                        $immediate_parent_catid);

                    // Figure name for display and debugging purposes
                    $parentname = 
                        $id_categories[$immediate_parent_catid]->name;

                    $id_categories[$newcategory->id] = $newcategory;

                    $this->debugln('  Created ' . $type . ' category: '
                         . $trans . ' parent: ' . $parentname);

                    $name_categories[$namecheck] = $newcategory;

                    unset($newcategory);
                }

                // As the loop continues, the parent will be set
                $immediate_parent_catid = $name_categories[$namecheck]->id;
            }

            $truecatreferences[$trans] = $name_categories[$namecheck];
        }

        // Save this for when building courses
        $this->categories_cache = $truecatreferences;

        // creates the category paths, very necessary
        fix_course_sortorder();
    }

    function new_category($name, $parent=0) {
        global $CFG, $DB;

        // This is how moodle creates categories...
        $newcategory = new StdClass();
        $newcategory->name = $name;
        $newcategory->parent = $parent;
        $newcategory->sortorder = 999;

        // This is because we're not going to add a description.
        $newcategory->descriptionformat = 0;

        $newcategory->id = $DB->insert_record('course_categories',
            $newcategory);

        $newcategory->context = get_context_instance(CONTEXT_COURSECAT, 
            $newcategory->id);

        mark_context_dirty($newcategory->context->path);

        return $newcategory;
    }

    /**
     *  Inserts the instructor into our local Arrays.
     *
     *  Used in @see send_emails.
     *  Will modify the state of the object.
     *
     *  @param $entry The entry from the Registrar.
     *  @return string The Array key.
     **/
    function key_field_instructors($entry) {
        $entry = (object) $entry;

        $srs = $entry->srs;

        if (!isset($this->cron_term_cache['instructors'])) {
            $this->cron_term_cache['instructors'] = array();
        }

        if (!isset($this->cron_term_cache['profcodes'])) {
            $this->cron_term_cache['profcodes'] = array();
        }

        if (!isset($entry->ucla_id)) {
            return false;
        }

        // Save the instructor indexed by UID
        $this->cron_term_cache['instructors'][$srs][$entry->ucla_id] = $entry;

        // Save the profcodes of the course
        $profcode = $entry->role;

        $this->cron_term_cache['profcodes'][$srs][$profcode] = $profcode;
    }

    /**
     *  Creates all the courses that were requested.
     **/
    function create_courses() {
        if (!isset($this->cron_term_cache['term_rci']) 
                && empty($this->cron_term_cache['term_rci'])) {
            return false;
        }

        $term = $this->get_cron_term();
        $requests =& $this->cron_term_cache['requests'];

        // this is a hack for assigning course urls to non-host courses
        $nhcourses = array();

        $newcourses = array();
        foreach ($this->cron_term_cache['term_rci'] 
                as $reqkey => $rci_object) {
            unset($req_course);

            $courseobj = clone($this->course_defaults);
            $courseobj->summary_format = FORMAT_HTML;
            $courseobj->summary = $rci_object->crs_desc;

            // See if we can get certain information from the requests
            if (!isset($requests[$reqkey])) {
                throw new moodle_exception('strange request ' . $reqkey);
            } else {
                $req_course = $requests[$reqkey];

                // We don't need to build a site for child courses
                if ($req_course->hostcourse < 1) {
                    $nhcourses[$req_course->setid][$reqkey] = $req_course;
                    continue;
                }

                $courseobj->visible = !$req_course->hidden;
            }

            $courseobj->shortname = self::make_course_shortname($rci_object);

            // Sorry for the inconsistent calling scheme
            $courseobj->fullname = self::make_course_title(
                trim($rci_object->coursetitle), 
                trim($rci_object->sectiontitle)
            );

            // Get the long version of the subject area (for category)
            $subj = rtrim($rci_object->subj_area);
            $category_name = $this->get_subj_area_translation($subj);

            if (isset($this->categories_cache[$category_name])) {
                $category = $this->categories_cache[$category_name];
            } else {
                // Default category (miscellaneous), but this may lead to 
                // the first category displayed in course/category.php
                $category = get_course_category(1);
                $this->println('Could not find category: ' . $CATegory_name
                    . ', putting course into ' . $category->name);
            }

            if ($this->match_summer($term)) {
                // TODO CCLE-2541 make this configurable: number of 
                // section in summer?
                $courseobj->numsections = 6;
            }

            $courseobj->category = $category->id;

            // save course
            $newcourses[$reqkey] = $courseobj;
        }

        $existingcourses = self::match_existings($newcourses, 
            'course', array('shortname'));

        foreach ($existingcourses as $eck => $existingcourse) {
            // Mark these as already built...
            unset($newcourses[$eck]);
            $this->debugln("! $eck built outside of course creator");
        }

        $this->debugln('Creating courses...');
        $builtcourses = $this->bulk_create_courses($newcourses);
        $this->debugln('Created ' . count($builtcourses) . ' courses');

        foreach ($builtcourses as $btk => $course) {
            $req = $requests[$btk];
            $rsid = $req->setid;
            
            $this->println(". $btk built successfully");
            associate_set_to_course($rsid, $course->id);

            // Apply associate all requests to a built course
            if (isset($nhcourses[$rsid])) {
                foreach ($nhcourses[$rsid] as $rk => $rq) {
                    $builtcourses[$rk] = $course;
                }
            }
        }

        $this->cron_term_cache['created_courses'] = $builtcourses;
    }

    /** 
     *  Checks the database for existing entries in table, and returns
     *  those existing entries.
     *  @param  $runners    Array( of Obj ) Existing data to check for
     *  @param  $table      Table to use
     *  @param  $fields     Fields that need to bee in each Obj
     *  @return Array( of Obj ) of entries in the database.
     **/
    static function match_existings($runners, $table, $fields) {
        global $DB;

        $returns = array();

        $sqlparams = array();
        $sqlstates = array();
        foreach ($fields as $fora) {
            // This is the field data
            $fd = array();

            foreach ($runners as $runner) {
                $fd[] = $runner->{$fora};
            }

            list($forasql, $foraparams) = $DB->get_in_or_equal($fd);
            $sqlstates[] = $fora . ' ' . $forasql;

            $sqlparams = array_merge($sqlparams, $foraparams);
        }

        $sqlwhere = implode(' OR ', $sqlstates);

        $existings = $DB->get_records_select($table, $sqlwhere, $sqlparams);

        foreach ($fields as $fora) {
            $optind = array();
            foreach ($existings as $runner) {
                $optind[$runner->{$fora}] = $runner;
            }
            
            foreach ($runners as $ckey => $runner) {
                if (isset($optind[$runner->{$fora}])) {
                    $returns[$ckey] = $runner;
                }
            }
        }

        return $returns;
    }

    /**
     *  Builds a bunch of courses, indexed the keys in the courses sent
     *  in.
     *  TODO optimize
     *  @throws moodle_exception from create_course()
     **/
    function bulk_create_courses($courses) {
        $returns = array();
        // Give public private a chance to install....

        $ppe = $this->publicprivate_enabled();
        foreach ($courses as $key => $course) {
            if ($ppe) {
                $course->enablepublicprivate = 1;
            }

            $returns[$key] = create_course($course);
        }

        return $returns;
    }

    /**
     *  Sends the URLs of the courses to MyUCLA.
     *
     **/
    function update_MyUCLA_urls() {
        if (!isset($this->cron_term_cache['created_courses'])) {
            throw new course_creator_exception(
                'IMS did not seem to create any courses'
            );
        }
        
        // Figure out what to build as the URL of the course
        $relevant_url_info = array();

        $urlupdater = $this->get_myucla_urlupdater();
        if (!$urlupdater) {
            $this->debugln('Could not find urlupdater.');
        }

        $this->println('  Starting MyUCLA URL Hook.');

        // Create references, not copies
        $created =& $this->cron_term_cache['created_courses'];
        $requests =& $this->cron_term_cache['requests'];

        $urlarr = array();

        // For each requested course, figure out the URL
        foreach ($requests as $cronkey => $request) {
            // check_build_requests() should have been run
            if (!isset($created[$cronkey])) {
                continue;
            }

            $url_info = $created[$cronkey];
            $url = $this->build_course_url($url_info);

            $urlobj = array(
                'url' => $url,
                'term' => $request->term,
                'srs' => $request->srs
            );

            if ($urlupdater) {
                if ($request->nourlupdate == 1) {
                    $urlobj['flag'] = myucla_urlupdater::neverflag;
                } else {
                    $urlobj['flag'] = myucla_urlupdater::nooverwriteflag;
                }
            }

            $urlarr[$cronkey] = $urlobj;

            // Store for emails
            $relevant_url_info[$request->term][$request->srs] = $url;
        }

        if ($urlupdater) {
            $urlupdater->sync_MyUCLA_urls($urlarr);
            $skipreasoncounter = array();

            $ks = array('failed', 'successful');
            foreach ($ks as $k) {
                if (!empty($urlupdater->{$k})) {
                    // print set of stuff returned by the myucla
                    // updater module 
                    $this->println('  Url update ' . $k . ': ');
                    foreach ($urlupdater->{$k} as $kid => $ked) {
                        // If it was skipped (not sent on purpose)
                        // then also document that fact
                        if (isset($urlupdater->skipped[$kid])
                                && isset($urlarr[$kid]['flag'])) {

                            $string = get_string($urlarr[$kid]['flag'],
                                'tool_myucla_url');

                            if (!isset($skipreasoncounter[$string])) {
                                $skipreasoncounter[$string] = 0;
                            }

                            $skipreasoncounter[$string]++;

                            $ked .= ' (' . $string . ')';
                        }

                        $this->println('  ' . $kid . ' ' . $ked);
                        
                    }

                    $this->debugln(count($urlupdater->{$k}) 
                        . " URL updates $k");
                }

                foreach ($skipreasoncounter as $str => $cnt) {
                    $this->debugln("$cnt skipped because [$str]");
                }
            }


            $this->println('  Finished MyUCLA URL hook.');
        }

        // This needs to be saved for emails
        $this->cron_term_cache['url_info'] = $relevant_url_info;
    }

    /**
     *  Sends emails to instructors and course requestors.
     *  TODO move this outside the course creator as well..
     *  @throws course_creator_exception
     **/
    function send_emails() {
        if (empty($this->cron_term_cache['url_info'])) {
            $this->debugln(
                'Warning: We have no URL information for E-Mails.'
            );

            return false;
        }   
        
        if (!isset($this->cron_term_cache['trim_requests'])) {
            $this->trim_requests();
        }

        // This should fill the term cache 'instructors' with data from 
        // ccle_CourseInstructorsGet
        $this->println('Getting ' 
            . count($this->cron_term_cache['trim_requests']) 
            . ' instructors from registrar...');

        $results = registrar_query::run_registrar_query(
            'ccle_courseinstructorsget', 
            $this->cron_term_cache['trim_requests'],
            true
        );

        $this->println('Finished fetching from registrar.');

        if (empty($results)) {
            // @TODO Maybe change the default behavior
            $this->debugln('No instructors for this term!');
        }

        foreach ($results as $res) {
            $this->key_field_instructors($res);
        }

        // I think the old version works pretty well...
        // These are read-only, no need to duplicate contents 
        $courses =& $this->cron_term_cache['requests'];
        $rci_objects =& $this->cron_term_cache['term_rci'];
        $instructors =& $this->cron_term_cache['instructors'];
        $profcodes =& $this->cron_term_cache['profcodes'];
        $course_urls =& $this->cron_term_cache['url_info'];

        $created_courses_check =& $this->cron_term_cache['created_courses'];

        // This is to maintain people without reported URSA emails
        $this->cron_term_cache['no_emails'] = array();

        // These are the collection of people we are going to email
        $emails = array();

        // These are non-host-courses 
        $indexed_hc = array();
        foreach ($courses as $cronkey => $course) {
            // TODO make this comparison a function
            if ($course->hostcourse < 1) {
                if (isset($created_courses_check[$cronkey])) {
                    $indexed_hc[$course->setid][$cronkey] 
                        = $rci_objects[$cronkey];
                }
            }
        }

        // Parse through each request
        foreach ($courses as $cronkey => $course) {
            if ($course->hostcourse < 1) {
                continue;
            }

            $csrs = $course->srs;
            $term = $course->term;

            if (!isset($created_courses_check[$cronkey])) {
                continue;
            }

            $rci_course = $rci_objects[$cronkey];

            $pretty_term = ucla_term_to_text($term, 
                $rci_course->session_group);

            // This is the courses to display the email for
            $course_c = array($rci_course);

            $csid = $course->setid;
            if (!empty($indexed_hc[$csid])) {
                foreach ($indexed_hc[$csid] as $nhcronkey => $nhc) {
                    $course_c[] = $nhc;
                }
            }

            $course_d = array();
            foreach ($course_c as $course_info) {
                $course_d[] = $this->make_email_course_name($course_info);
            }

            $course_text = implode(' / ', $course_d);

            $course_dept = $rci_course->subj_area;

            unset($rci_course);

            // The instructors to be emailed
            $show_instructors = array();

            // Determine which instructors to email
            if (!isset($profcodes[$csrs])) {
                $this->debugln('No instructors for ' 
                    . "$term $csrs $course_text.");
            } else {
                $profcode_set = $profcodes[$csrs];

                if (isset($instructors[$csrs])) {
                    foreach ($instructors[$csrs] as $instructor) {
                        $viewable = $this->get_viewable_status($instructor, 
                            $profcode_set, $course_dept);

                        if ($viewable) {
                            $show_instructors[] = $instructor;
                        } else {
                            $this->debugln(
                                'Not emailing ' . $instructor->last_name_person
                                . ' Profcode: ' . $instructor->role
                            );
                        }
                    }
                }

                if (empty($show_instructors)) {
                    $this->debugln("No instructors to email for "
                        . "$term $csrs ($course_text)!");
                }
            }

            if (isset($course_urls[$term][$csrs])) {
                $course_url = $course_urls[$term][$csrs];
            } else {
                $course_url = 'No URL';
            }
    
            // Check if we should email the professors
            // Default to not emailing professors
            $retain_emails = true;
            if (!empty($course->mailinst)) {
                $retain_emails = false;
            }

            foreach ($show_instructors as $instructor) {
                $lastname = ucla_format_name(
                    trim($instructor->last_name_person)
                );

                $email = trim($instructor->ursa_email);

                $uid = $instructor->ucla_id;

                // If they do not have an email from the Registrar, and we did
                // not already find one locally, attempt to find one locally
                if ($email == '' && !isset($this->local_emails[$uid])) {
                    $this->cron_term_cache['no_emails'][$uid] = 
                        $instructor;
                }

                unset($email_ref);

                $email_ref['lastname'] = $lastname;
                $email_ref['to'] = $email;
                $email_ref['coursenum-sect'] = $course_text;
                $email_ref['dept'] = '';
                $email_ref['url'] = $course_url;
                $email_ref['term'] = $term;
                $email_ref['nameterm'] = $pretty_term;

                // These are not parsed
                $email_ref['subjarea'] = $course_dept;
                $email_ref['userid'] = $uid;
                $email_ref['srs'] = $csrs;
                $email_ref['block'] = $retain_emails;
                $emails[] = $email_ref;
            }
        }

        // Try to check out local records for emails
        $local_emails = array();

        if (!empty($this->cron_term_cache['no_emails'])) {
            $this->get_local_emails();

            $local_emails =& $this->cron_term_cache['local_emails'];
        }

        if (!$this->send_mails()) {
            $this->debugln('--- Email sending disabled ---');
        }

        // TODO move the rest of this out
        // Parsed
        // This may take the most memory
        $email_summary_data = array();
        foreach ($emails as $emailing) {
            $add_subject = '';
            $email_to = '';

            // This is going to be used later
            $csrs = $emailing['srs'];
            unset($emailing['srs']);

            // Filter out no emails
            $userid = $emailing['userid'];

            // Preformat the email summary
            if (!isset($email_summary_data[$csrs])) {
                $email_summary_data[$csrs] = array();
            }

            $email_summary_data[$csrs][$userid] = '';

            if ($emailing['to'] == '') {
                // Attempt to find user
                if (!isset($local_emails[$userid])) {
                    // No email, specify that and send to BCCs
                    $this->println("Cannot email $userid " 
                        . $emailing['lastname']);

                    $add_subject = ' (No email)';

                    $email_summary_data[$csrs][$userid] .= "! " 
                        . $emailing['lastname']
                        . "\t $userid \tNo email address.\n";
                } else {
                    $emailing['to'] = $local_emails[$userid];

                    $email_summary_data[$csrs][$userid] .= '* ' 
                        . $emailing['lastname']
                        . "\t $userid \t" . $local_emails[$userid] 
                        . " - Local email ONLY\n";
                }
            } 

            // This is also used later to not send the email...
            $block_email = $emailing['block'];
            unset($emailing['block']);
            
            // Handle special emails to THE STAFF and TA
            if (is_dummy_ucla_user($userid)) {
                $email_to = '';
                $add_subject = ' (' . $emailing['lastname'] . ')';
            } else {
                // Set the destination
                $email_to = $emailing['to'];
            }

            unset($emailing['userid']);

            // Parse the email
            $subj = $emailing['subjarea'];

            // Figure out which email template to use
            if ($this->send_mails() && !isset($this->parsed_param[$subj])) {
                if (!isset($this->email_prefix)) {
                    $this->figure_email_vars();
                }

                $deptfile = $this->email_prefix . $subj . $this->email_suffix;

                if (file_exists($deptfile)) {
                    $this->debugln('Using special template for subject area '
                        . $subj);
                    
                    $file = $deptfile;
                } else {
                    $file = $this->default_email_file;
                }

                $this->parsed_param[$subj] = $this->email_parse_file($file);
            }

            if (!isset($this->parsed_param[$subj])) {
                $headers = '';
                $email_subject = '-not parsed - ' 
                    . $emailing['coursenum-sect'] . ' '
                    . $emailing['url'] 
                    . $add_subject;

                $email_body = '!-not parsed-!';
            } else {
                $used_param = $this->parsed_param[$subj];
                unset($emailing['subjarea']);

                $email_params = 
                    $this->email_fill_template($used_param, $emailing);

                // Setup the email
                $from = $email_params['from'];
                $bcc = $email_params['bcc'];

                // Headers, include the Blind Carbon Copy
                $headers = "From: $from \r\n Bcc: $bcc \r\n";
           
                $email_subject = $email_params['subject'];

                // Append filler user explanations
                $email_subject .= $add_subject;

                $email_body = $email_params['body'];
            }

            $email_summary_data[$csrs][$userid] .= '. ' 
                . $emailing['lastname'] . "\t $userid \t" 
                . $email_to . " \t $email_subject";

            if ($this->send_mails() && !$block_email) {
                $this->println("Emailing: $email_to");

                ucla_send_mail($email_to, $email_subject, 
                    $email_body, $headers);
            } else {
                if ($block_email) {
                    $this->println('Blocked this email - from setting in '
                        . 'course requestor.');
                }

                $this->println("to: $email_to");
                $this->println("headers: $headers");
                $this->println("subj: $email_subject");
            }
        }

        foreach ($email_summary_data as $srs => $course_data) {
            foreach ($course_data as $instr_data) {
                $this->emailln($instr_data);
            }
        }
    }

    /**
     *  A human-readable string that will display the essential course
     *  information in the email that conveys which courses were built
     *  in the current session of course creator.
     **/
    function make_email_course_name($reginfo) {
        return trim($this->get_subj_area_translation(trim($reginfo->subj_area)) 
            . ' ' . trim($reginfo->coursenum) . ' ' . $reginfo->acttype 
            . ' ' . $reginfo->sectnum);
    }

    /**
     *  This will try to see if any instructors without emails from the
     *  Registrar have accounts with emails on our local server.
     *
     *  Changes the state of the object.
     **/
    function get_local_emails() {
        global $DB;
        // Try to check out local records for emails
        $no_emails =& $this->cron_term_cache['no_emails'];

        // This should not happen
        if (empty($no_emails)) {
            return false;
        }

        $local_userids = array();

        foreach ($no_emails as $emailless) {
            // Attempt to find user
            $userid = $emailless->ucla_id;
            $name = trim($emailless->first_name_person) . ' ' 
                . trim($emailless->last_name_person);
            $this->println("$name $userid has no email.");

            $local_userids[] = $userid;
        }

        list($sql_in, $params) = $DB->get_in_or_equal($local_userids);
        $sql_where = 'idnumber ' . $sql_in;

        $this->println("Searching local MoodleDB for idnumbers $sql_in...");

        $local_users = $DB->get_records_select('user', $sql_where, $params);

        if (!empty($local_users)) {
            foreach ($local_users as $local_user) {
                $email = trim($local_user->email);

                if ($email != '') {
                    $idnumber = $local_user->idnumber;
                    $this->println("Found user $idnumber $email");
                    $this->local_emails[$local_user->idnumber] = $email;
                }
            }
        }
    }

    /**
     *  Parses the reference file into an array.
     *  @param The file location.
     *  @return The elements of the email parsed into an array.
     **/
    function email_parse_file($file) {
        $email_params = array();

        $fp = @fopen($file, 'r');

        if (!$fp) {
            $this->debugln("ERROR: could not open email template file: "
                . "$file.\n");
            return ;
        }

        // first 3 lines are headers
        for ($x = 0; $x < 3; $x++) {
            $line = fgets($fp);
            if (preg_match('/'.'^FROM:(.*)'.'/i',$line, $matches)) {
                $email_params['from'] = trim($matches[1]);
            } else if (preg_match('/'.'^BCC:(.*)'.'/i',$line, $matches)) {
                $email_params['bcc'] = trim($matches[1]);
            } else if (preg_match('/'.'^SUBJECT:(.*)'.'/i',$line,$matches)) {
                $email_params['subject'] = trim($matches[1]);
            }
        }
        
        if(sizeof($email_params) != 3) {
            $this->debugln("ERROR: failed to parse headers in $file \n");
            return false;
        }
        
        $email_params['body'] = '';
        
        while (!feof($fp)) { //the rest of the file is the body
            $email_params['body'] .= fread($fp, 8192);
        }
       
        $this->debugln("Parsing $file successful \n");
        fclose($fp);
        
        return $email_params;
    }

    /** 
     *  Replaces values in the email with values provided in arguments.
     *  @param The parsed email.
     *  @param The values to replace the parsed entries with.
     *  @return The reparsed emails.
     **/
    function email_fill_template($params, $arguments) {
        foreach ($params as $key => $value) { 
            // fill in template placeholders
            foreach ($arguments as $akey => $avalue) {
                $params[$key] = str_replace('#=' . $akey . '=#',
                    $avalue, $params[$key]);
            }

            if (preg_match('/#=.*?=#/', $params[$key])) {
                echo $params[$key];
            }
        }

        return $params;
    }

    /**
     *  Fills the {ucla_reg_classinfo} table with the information obtained
     *  from the Registrar for the requests.
     *  Called by @see mark_cron_term().
     *
     *  Changes the state of the function.
     **/
    function insert_term_rci() {
        global $DB;

        if (!$this->get_cron_term()) {
            return false;
        }

        if (!isset($this->cron_term_cache['term_rci'])
          || empty($this->cron_term_cache['term_rci'])) {
            return false;
        }
        
        // Reference
        $term_rci =& $this->cron_term_cache['term_rci'];

        // TODO move the bulk sql functions elsewhere
        $fields = array();
        foreach ($term_rci as $rci_data) {
            foreach ($rci_data as $field => $data) {
                if (!isset($fields[$field])) {
                    $fields[$field] = $field;
                }
            } 
        }

        $params = array();
        foreach ($term_rci as $rci_data) {
            foreach ($fields as $field) {
                if (!isset($rci_data->$field)) {
                    $params[] = '';
                } else {
                    $params[] = $rci_data->$field;
                }
            }
        }

        $filler = array_fill(0, count($fields), '?');

        $builderes = array();

        $drive_size = count($term_rci);

        $fields_string = "(`" . implode("`, `", $fields) . "`)";

        for ($i = 0; $i < $drive_size; $i++) {
            $builderes[] = "(" . implode(", ", $filler) . ")\n";
        }

        $buildline = implode(', ', $builderes);

        // TODO use moodle api better, or extract this functionality out
        $sql = "
            INSERT INTO {ucla_reg_classinfo}
            $fields_string
            VALUES
            $buildline
        ";

        // Fix this
        try {
            $DB->execute($sql, $params);
        } catch (dml_exception $e) {
            $this->debugln('Registrar Class Info mass insert failed.');

            foreach ($term_rci as $rci_data) {
                try {
                    $DB->insert_record('ucla_reg_classinfo',
                        $rci_data);
                } catch (dml_exception $e) {
                    $this->debugln('ucla_reg_classinfo insert failed: '
                        . $rci_data->term . ' ' . $rci_data->srs . ' '
                        . $e->debuginfo);
                }
            }
        }

        $this->println('  Finished dealing with ucla_reg_classinfo.');
    }

    
    /**
     *  Gathers the information needed to mail to the requestors.
     *  Called at the finish of every term by @see mark_cron_term().
     *
     *  Changes the state of the function.
     **/
    function queue_requestors() {
        if (!isset($this->cron_term_cache['requests'])) {
            return false;
        }

        $url_info =& $this->cron_term_cache['url_info'];

        // Gather requestors' courses
        foreach ($this->cron_term_cache['requests'] as $course) {
            if (empty($course->requestoremail)) {
                continue;
            }

            // TODO pluralize, work for csv of emails
            $contact = $course->requestoremail;

            // Validate contact
            if (!isset($this->requestor_emails[$contact])) {
                if (validate_email($contact)) {
                    $this->requestor_emails[$contact] = array();
                } else {
                    $this->emailln("Requestor email $contact not valid "
                        . "for $term $csrs");
                }
            }

            if (isset($this->requestor_emails[$contact])) {
                if (isset($url_info[$course->term][$course->srs])) {
                    $course_url = $url_info[$course->term][$course->srs];
                } else {
                    $course_url = 'Failed to build.';
                }

                $req_key = $course->term . '-' . $course->srs . ' '
                    . $course->course;

                $this->requestor_emails[$contact][$req_key] = 
                    $course_url;
            }
        }
    }

    /**
     *  This will mail the requestors with the information we gathered.
     *  This is called by @see finish_cron().
     **/
    function mail_requestors() {
        if (empty($this->requestor_emails)) {
            return false;
        }

        $requestor_mesg_start = "The courses you've requested:\n";
        $requestor_mesg_end = "\nhave been run through course creator.";

        $requestor_headers = '';

        // Email course requestors
        foreach ($this->requestor_emails as $requestor => $created_courses) {

            $crecou_cnt = count($created_courses);
            if ($crecou_cnt > 1) {
                $req_subj_subj = $crecou_cnt . ' courses';
            } else {
                $req_subj_subj = reset(array_keys($created_courses));
            }

            $req_subj = "Your request for $req_subj_subj has been processed.";

            $created_courses_summary = array();
            foreach ($created_courses as $key => $status) {
                $created_courses_summary[] = $key . " - " . $status;
            }

            $req_summary = implode("\n", $created_courses_summary);

            $req_mes = $requestor_mesg_start 
                . $req_summary . $requestor_mesg_end;

            if ($this->send_mails()) {
                $resp = ucla_send_mail($requestor, $req_subj, $req_mes, 
                    $requestor_headers);

                if ($resp) {
                    $this->debugln("Emailed $requestor");
                } else {
                    $this->println("ERROR: course not email $requestor");
                }
            } else {
                $this->debugln("Would have emailed: $requestor");
            }

            $this->emailln("Requestor: $requestor for $req_summary");
        }
    }

    /**
     *  Saves a set of requests with courseid in the object into
     *  the course creator object.
     *  Uses cron term cache 'requests' 'created_courses'
     *  Sets $this->built_requests
     **/
    function save_created_courses() {
        if (empty($this->cron_term_cache['requests'])) {
            return false;
        }

        $requests =& $this->cron_term_cache['requests'];

        if (empty($this->cron_term_cache['created_courses'])) {
            // No courses created this term, no courses need to be saved
            return false;
        }

        $created_courses =& $this->cron_term_cache['created_courses'];
       
        $counter = 0;
        foreach ($requests as $key => $request) {
            if (!empty($created_courses[$key])) {
                $request->courseid = $created_courses[$key]->id;
                $this->built_requests[$key] = $request;
                $counter++;
            }
        }

        $this->debugln("* Saved $counter courses.");
    }

    /**
     *  Triggers the event with course created data.
     *  Uses $this->built_requests
     **/
    function events_trigger_with_data() {
        if (empty($this->built_requests)) {
            return false;
        } 

        $edata = new stdclass();
        $edata->completed_requests = $this->built_requests;

        $this->println('. Triggering event.');
        events_trigger('course_creator_finished', $edata);
        $this->debugln('Triggered event with ' 
            . count($edata->completed_requests) . ' requests.');
    }

    /** ********************** **/
    /*  More Global Functions   */
    /** ********************** **/

    /**
     *  Check that we have an outpath set, if not, we will use moodledata.
     *  Check that we have write priviledges to the outpath, if not, we will 
     *      use moodledata.
     *
     *  Changes:
     *      shell_date
     *      full_date
     *      output_path
     *
     **/
    function check_write() {
        global $CFG;

        if (isset($this->output_path)) {
            return true;
        }

        // Check if we have a path to write to
        $ccoutpath = $this->get_config('outpath');
        if ($ccoutpath) {
            $this->output_path = $ccoutpath;
        } else {
            // Defaulting to moodledata
            $this->output_path = $CFG->dataroot . '/course_creator';

            // This means we have no write priveledges to moodledata
            if (!file_exists($this->output_path)) {
                if (!mkdir($this->output_path)) {
                    throw new course_creator_exception('Could not make ' 
                        . $this->output_path);
                }
            }
        }

        // Test that we actually can write to the output path
        $test_file = $this->output_path . '/write_test.txt';

        if (!fopen($test_file, 'w')) {
            throw new course_creator_exception('No write permissions to ' 
                . $this->output_path);
        } 

        unlink($test_file);

        // This is saved for creating XML and log files
        $this->shell_date = date('Ymd-Hi');
        $this->full_date = date('r');
    }

    /**
     *  Will determine whether or not we can run this function.
     *  @param boolean $lock true for lock, false for unlock.
     *  @param boolean $warn display a message if unlocking without lock.
     *  @return boolean If we the action was successful or not.
     *  @since Moodle 2.0.
     **/
    function handle_locking($lock, $warn=true) {
        global $DB;

        // Get a unique id for this lock
        $this->make_dbid();
        $this->check_write();

        $cc_lock = $this->output_path . '/' . $this->db_id . '.lock';
        $fe = file_exists($cc_lock);

        // Prevent new requests that come in during course creation from 
        // affecting course creator
        if ($lock) {
            // We sometimes want to do a file lock
            if ($fe) {
                $msg = "Lock file $cc_lock already exists!";

                echo $msg . "\n";
                throw new course_creator_exception($msg);
            }

            $lockfp = fopen($cc_lock, 'x');
            fclose($lockfp);

            $this->println('Lock successful.');
        } else {
            if ($fe) {
                unlink($cc_lock);

                $this->println('Unlock successful');
            } else {
                if ($warn) {
                    $this->debugln(
                        'WARNING: Lock file disappeared during course'
                        . 'creation!'
                    );

                    $this->debugln('!! Your tables MAY be volatile !!');
                }
            }
        }

        return true;
    }


    /**
     *  Temporary wrapper for finishing up cron.
     *  Email admin.
     *  Cleanup certain things?
     **/
    function finish_cron() {
        $this->mail_requestors();

        $this->println(
            '---- Course creator end at ' . date('r') . ' ----'
        );

        if (!empty($this->email_log)) {
            $emailbody = get_string('checklogs', 'tool_uclacoursecreator')
                . ": " . $this->log_file . "\n" . $this->email_log;

            // Email the summary to the admin
            ucla_send_mail($this->get_config('course_creator_email'), 
                'Course Creator Summary ' . $this->full_date, $emailbody);
        }

        $this->close_log_file_pointer();

        // Trigger event
        $this->events_trigger_with_data();

        return true;
    }
    
    /**
     *  Populates $this->db_id with a unique identifier per instance of
     *  Moodle.
     *  Currently just uses dbname
     **/
    function make_dbid() {
        if (!isset($this->db_id)) {
            $this->db_id = get_config(null, 'dbname');
        }
    }

    /**
     *  Sets the terms to be run. 
     *  @deprecated v2011041900 - Renamed to set_term_list()
     *
     *  Changes the state of the function.
     *
     *  @param $terms_list The array of terms to run for.
     **/
    function set_terms($terms_list) {
        $this->set_term_list($terms_list);
    }

    /** *************************** **/
    /*  Non-Variants Initializers    */
    /** *************************** **/

    /**
     *  Will figure out the terms to work for.
     *  Currently only uses the config file as a source.
     *  
     *  Only called by @see get_terms_creating().
     *
     *  Will change the state of the object.
     **/
    function figure_terms() {
        if ($this->get_config('terms')) {
            $terms_list = $this->get_config('terms');
        }

        if (isset($terms_list)) {
            foreach ($terms_list as $term) {
                if (!$this->validate_term($term)) {
                    throw new course_creator_exception(
                        'Improper term ' . $term
                    );
                }
            }

            $this->terms_list = $terms_list;

            return $terms_list;
        }

        return false;
    }

    /**
     *  This will figure out the paths for the email files using the config
     *  variables.
     *
     *  You just need to call this once.
     **/
    function figure_email_vars() {
        if (!$this->send_mails()) {
            return false;
        }

        if (!$this->get_config('email_template_dir')) {
            throw new course_creator_exception(
                'ERROR: email_template_dir not set!'
            );
        }

        $this->email_prefix = 
            $this->get_config('email_template_dir') . '/';

        $this->email_suffix = '_course_setup_email.txt';
        
        $this->default_email_file = $this->email_prefix . 'DEFAULT'
            . $this->email_suffix;
    }

    /** ************************ **/
    /*  Global Function Wrappers  */
    /** ************************ **/

    /**
     *  Will figure out what to interpret as the webpage.
     *
     *  @param $course The course object.
     *  @return string The URL of the course (no protocol).
     **/
    function build_course_url($course) {
        // TODO put this in the proper namespace
        if (get_config('', 'ucla_friendlyurls_enabled')) {
            return new moodle_url('/course/view/' . $course->shortname);
        }

        return new moodle_url('/course/view.php', array('id' => $course->id));
    }

    /**
     *  Checks and caches the fact that public private has been enabled.
     *  Loads public private code if public private is found.
     **/
    function publicprivate_enabled() {
        global $CFG;

        if (!isset($this->publicprivate_enabled)) {
       
            $cv = false;
            $ppfile = $CFG->dirroot . '/lib/publicprivate/course.class.php';
            if (file_exists($ppfile)) {
                require_once($ppfile);
                $cv = true;
            }

            $this->publicprivate_enabled = $cv;
        }

        return $this->publicprivate_enabled;

    }

    /**
     *  Wrapper for {@see get_config()}
     **/
    function get_config($config) {
        return get_config('tool_uclacoursecreator', $config);
    }

    /**
     *  Make sure the term is valid.
     *  @param $term The term.
     *  @return boolean Whether the term is valid or not.
     **/
    function validate_term($term) {
        return ucla_validator('term', $term);
    }

    /**
     *  Build the shortname from registrar information.
     *  @param Object with fields:
     *      term, session_group, subj_area, coursenum, sectnum
     *  @return string The shortname, without the term.
     **/
    static function make_course_shortname($rci_object) {
        $rci = get_object_vars($rci_object);

        foreach ($rci as $k => $v) {
            $rci[$k] = trim($v);
        }

        $course = $rci['term'] . $rci['session_group'] . '-' 
            . $rci['subj_area'] . $rci['coursenum'] . '-' 
            . $rci['sectnum'];

        // Remove spaces and ampersands
        $course = preg_replace('/[\s&]/', '', $course);

        return $course;
    }

    /**
     *  Will make a course title from Registrar course and section title data.
     *
     *  @param $course_title The course title.
     *  @param $section_title The section title.
     *  @return string The combined title.
     **/
    static function make_course_title($course_title, $section_title) {
        if (empty($section_title)) {
            return $course_title;
        }

        return "$course_title: $section_title";
    }

    /**
     *  Recursively trim() fields.
     *  @param $obj The object to trim().
     *  @return Object The object, trimmed.
     **/
    function trim_object($oldobj) {
        foreach ($oldobj as $f => $v) {
            if (is_array($v)) {
                // Do nothing
            } else if (is_object($v)) {
                $oldobj->{$f} = $this->trim_object($v);
            } else {
                $oldobj->{$f} = trim($v);
            }
        }

        return $oldobj;
    }

    /**
     *  Check if the term is summer.
     *
     *  @param $term The term to check.
     *  @return boolean Is the term a summer term?
     **/
    function match_summer($term) {
        return is_summer_term($term);
    }
}

/** End of file **/
