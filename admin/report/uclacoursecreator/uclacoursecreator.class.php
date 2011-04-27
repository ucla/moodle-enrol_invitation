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
 *  The course creator class.
 *
 *  This creates courses from the course requestor. It can be configured to 
 *  build a certain subset of courses.
 *
 *  @package ucla
 *  @subpackage course_creator
 *  @copyright 2011 UCLA
 **/

// Require the exception
require_once(dirname(__FILE__) . '/course_creator_exception.class.php');

// Require essential stuff... 
require_once(dirname(__FILE__) . '/../../../course/lib.php');

/**
 *  Course creator.
 *
 *  @todo CCLE-2541 Include capability check? here?
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

    // Used to force debugging
    private $force_debug = null;

    // Private md5'd identifier for this cron task
    private $db_id;

    /** Variants for the cron **/
    // The current term we are working for
    private $cron_term;

    // array Contains all the information for a current term
    /**
     *  ims_fp - the filepointer for the IMS XML file.
     *  ims_path - the filepath for the IMS XML file.
     *  ims_log - the filepath for the IMS log file.
     *  defauls - the course defaults per term
     *  requests - the list of ucla_request_classes
     *  instructors - the set of instructors
     *  profcodes - an Array in an Array of profcodes in a course
     *  created_courses - list of created course objects
     *  activate - courses that we have to activate (all courses) TODO simplify
     *  mastercourses - the courses that are master sites
     *  url_info - the information to send to MyUCLA
     *  local_emails - the local emails from the mdl_user tables
     *  reg_cls_<srs> - these are dynamically created and destroyed, for use in 
     *      @see retrieve_registrar_crosslists()
     *  course_mapper - an Array with SRS => mdl_course.id
     **/
    private $cron_term_cache;

    /** Non Variants **/
    // This is an internal variable used for @link retrieve_registrar_crosslists().
    private $reg_callback_object;

    // These are just simple caches.
    private $shell_date;
    private $full_date;

    // Terms to be used by course creator.
    private $terms_list = null;

    // SRSs to be built by course creator.
    private $srs_list = null;

    // Contains the information regarding subject area long names.
    private $subj_trans;

    // Email parsing cache
    private $parsed_param = array();

    // Email file location cache...
    private $email_prefix;
    private $email_suffix;

    // Email file default
    private $default_email_file;

    // Contains the root path to the MyUCLA URL update webservice.
    private $myucla_login = null;

    // Send out information to MyUCLA, instructors and requestors?
    private $send_informations = null;

    // ODBC
    // Holds onto the registrar connection object.
    private $registrar_conn = null;

    // Note: There are dynamically generated fields for this class, which
    // contain references to the enrollment object.
    // I.E. $this->enrol_meta_plugin

    // This is the course creator cron
    function cron() {
        global $CFG;

        if (!$this->get_config('course_creator_cron_enabled')) {
            echo "Course creator cron job disabled.\n";
            // @todo figure out how to detect between cron and not-cron
        }

        $this->println('Running prerequisite checks...');

        if (!class_exists('enrol_plugin')) {
            require($CFG->libdir . '/enrollib.php');
        }

        $this->get_enrol_plugin('imsenterprise');
        $this->get_enrol_plugin('meta');
        
        // Make sure our email configurations are valid
        if (!$this->get_debug()) {
            $this->figure_email_vars();
        }

        try {
            // if we cannot write, we cannot lock, we cannot do anything
            $this->check_write();
       
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

        // Figure out the config variables, defaulting to quietest behavior
        if ($this->send_informations == null) {
            $this->send_informations = 
                $this->get_config('course_creator_send_informations');
        }

        // This will remember things locally from the Registrar
        $reg_callback_object = new StdClass();
        $reg_callback_object->term_table = 'term_rci';
        $reg_callback_object->key_field = 'srs';

        // This is called later for the instructors, since we do not
        // want to redeclare this object again.
        $this->reg_callback_object = $reg_callback_object;

        $this->println("---- Course Creator run at {$this->full_date} "
            . "({$this->shell_date}) -----");

        foreach ($termlist as $work_term) {
            try {
                // Flush the cache
                $this->start_cron_term($work_term);

                // Properly generate the names of the IMS files
                $this->println('Preparing IMS files...');
                $this->prepare_ims_files();

                // Get stuff from course requestor
                $retrieved = $this->retrieve_requests();

                // If we actually have entries to process
                if ($retrieved) {
                    // Figure out data from Registrar
                    $this->retrieve_registrar_info(
                        'ccle_getClasses',
                        $reg_callback_object,
                        'retrieve_registrar_crosslists'
                    );

                    // Create the IMS entries
                    $this->generate_ims_entries();

                    // From that create the courses, and validate them
                    $this->ims_cron();

                    // Update the URLs for the Registrar
                    $this->update_MyUCLA_urls();

                    // Send emails to the instructors
                    $this->send_emails();
                }

                if ($this->get_debug()) {
                    throw new course_creator_exception(
                        '** Debugging break **'
                    );
                }
                
                // This will mark term as finished, insert entries into 
                // ucla_reg_classinfo, mark the entries in 
                // ucla_request_classes as done
                $this->mark_cron_term(true);
            } catch (Exception $e) {
                $this->debugln($e->getMessage());

                // Since the things were not processed, try to revert the
                // changes in the requestor
                $this->mark_cron_term(false);

                try {
                    throw $e;
                } catch (course_creator_exception $cce) {
                    // Do nothing, this is safe
                }
            }
        }

        $this->handle_locking(false);

        $this->finish_cron();
    }

    /**
     *  Will attempt to build one course, must be course_requested first.
     *  
     *  @param $term The term.
     **/
    function build_one_course($term, $srs) {
        $t = array('term' => $term, 'srs' => $srs);

        $this->set_srs_list(array($t));
        $this->set_term_list(array($term));

        $this->cron();
    }

    /** ******************* **/
    /*  Debugging Functions  */
    /** ******************* **/
   
    /**
     *  Will print to course creator log.
     *  @param $mesg The message.
     **/
    function printl($mesg) {
        // We can do set_debug()
        if (isset($this->force_debug)) {
            echo $mesg;
    
            // to false, to not write to log
            if (!$this->force_debug) {
                return;
            }
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
    
    function get_course_creator_log_fp() {
        if (isset($this->log_fp)) {
            return $this->log_fp;
        }

        // This will set where all our files will be thrown
        // @throws course_creator_exception
        if (!isset($this->output_path)) {
            $this->check_write();
        }

        $log_file = $this->output_path . '/course_creator.' 
            . $this->shell_date . '.' . $this->db_id . '.log';

        $this->log_fp = fopen($log_file, 'a');

        return $this->log_fp;
    }

    /**
     *  Returns the long name of the subject area if found, 
     *  otherwise just the short name.
     *
     *  Will alter the state of the object.
     *
     *  @param $subjarea The short name of the subject area.
     *  @return The long name of the subject area, 
     *      or the short name if no long name was found.
     **/
    function get_subject_area_translation($subjarea, $default=false) {
        global $DB;

        if (!isset($this->subj_trans) || $this->subj_trans == null) {
            $indexed_sa = array();

            try {
                $subjareas = $DB->get_records('ucla_reg_subjectarea');
            } catch (dml_exception $e) {
                $subjareas = $DB->get_records('ucla_subjectarea');
            }

            foreach ($subjareas as $subjarea) {
                $indexed_sa[$subjarea->subjarea] = $subjarea->subj_area_full;
            }

            $this->subj_trans = $indexed_sa;
        }

        if (!isset($this->subj_trans[$subjarea])) { 
            return $subjarea;
        } 

        return $this->subj_trans[$subjarea];
    }

    /**
     *  Returns whether to display debug information.
     *
     *  @return boolean Whether to display debug information.
     **/
    function get_debug() {
        if (isset($this->force_debug)) {
            return $this->force_debug;
        }

        if (debugging()) {
            // @todo CCLE-2541 verify functionality
            $this->debugln('Debugging enabled via Moodle.');

            return true;
        }

        return false;
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
     *  Returns an Array of terms with an Array of SRS's that have been 
     *  designated to be created by the course creator.
     *
     *  @see set_srs_creating().
     *
     *  @return boolean|array The srs's specified, grouped by term or false.
     **/
    function get_srs_creating() {
        if (!isset($this->srs_list)) {
            return false;
        }

        return $this->srs_list;
    }

    /**
     *  Returns an Array of SRS for the current term being created in 
     *  @see cron().
     *
     *  @return boolean|array The srs's specified or false.
     **/
    function get_term_srs_creating($term=null) {
        if ($term == null) {
            $term = $this->get_cron_term();
        }

        if (!$term) {
            return false;
        }

        if (!isset($this->srs_list[$term])) {
            return false;
        }

        return array_keys($this->srs_list[$term]);
    }

    /**
     k  Returns the ADOConnection object for registrar connection.
     *
     *  Wrapper for @see open_registrar_connection().
     *  
     *  May change state of object.
     *
     *  @return ADOConnection The connection to the registrar.
     **/
    function get_registrar_connection() {
        if ($this->registrar_conn == null) {
            $this->println('Opening Registrar Connection...');
            $this->registrar_conn = $this->open_registrar_connection();
        }

        return $this->registrar_conn;
    }

    /**
     *  Check that we enabled and have the enrollment plugin.
     *
     *  May change the state of the object.
     *
     *  @return enrol_plugin New or cached.
     **/
    function get_enrol_plugin($plugin) {
        global $CFG;

        $enrol_class = 'enrol_' . $plugin . '_plugin';

        if (!class_exists($enrol_class)) {
            // Attempt to find the class
            $pred_path = $CFG->dirroot . '/enrol/' . $plugin . '/lib.php';
            require($pred_path);

            // Still not here, gonna party without you
            if (!class_exists($enrol_class)) {
                throw new course_creator_exception(
                    'Missing ' . $plugin . ' plugin in code.'
                );
            }
        }

        if (!enrol_is_enabled($plugin)) {
            throw new course_creator_exception(
                'Plugin ' . $plugin . ' is disabled.'
            );
        }

        if (isset($this->$enrol_class)) {
            return $this->$enrol_class;
        }

        // This is not an economic blowjob, but an enrol-class object
        $ecobj = new $enrol_class();
        $this->$enrol_class = $ecobj;

        $callback = 'post_' . $enrol_class;
        if (method_exists(get_class($this), $callback)) {
            $this->$callback();
        }

        return $ecobj;
    }

    /**
     *  Callback after loading the imsenterprise library.
     *  This will check to make sure all the configurations for the plugin
     *  is valid.
     * 
     *  Called by {@see get_enrol_plugin()}.
     **/
    function post_enrol_imsenterprise_plugin() {
        // IMS plugin reference
        if (!isset($this->enrol_imsenterprise_plugin)) {
            throw new moodle_exception('IMS Enterprise plugin load failed!');
        }

        $ims_plugin = $this->enrol_imsenterprise_plugin;

        // Check the IMS settings 
        $ims_settings = array();
        $ims_settings['createnewcourses'] = true;
        $ims_settings['createnewcategories'] = false;

        foreach ($ims_settings as $conf_check => $ims_fail) {
            if (!$ims_plugin->get_config($conf_check)) {
                // Check the configurations 
                if ($ims_fail) {
                    throw new course_creator_exception(
                        "Required IMS setting $conf_check disabled!"
                    );
                }

                $this->debugln(
                    "NOTICE: IMS setting $conf_check disabled - "
                    . "Please take heed."
                );
            }
        }
    }

    /**
     *  Returns a file pointer to the IMS file.
     *
     *  May change the state of the object.
     *
     *  @return int The file pointer to the IMS file.
     **/
    function get_ims_file_pointer() {
        if (isset($this->cron_term_cache['ims_fp'])) {
            return $this->cron_term_cache['ims_fp'];
        }

        // This should not occur
        if (!isset($this->output_path)) {
            return false;
        }

        if (!isset($this->cron_term_cache['ims_path'])) {
            $this->prepare_ims_files();
        }

        if (!isset($this->cron_term_cache['ims_path'])) {
            throw new Exception('ims_path not set');
        }

        $ims_file = $this->cron_term_cache['ims_path'];

        if (file_exists($ims_file)) {
            throw new course_creator_exception('IMS file ' . $ims_file 
                . ' already exists.');
        }

        $fp = fopen($ims_file, 'x');
        
        if (!$fp) {
            // This means that there is an IMS file open, but 
            // that we do not have a file pointer for this file. 
            // That means that:
            // Another process has somehow generated this IMS file.
            // We already should have tested for write permissions.
            // We know that there is logic to prevent the same 
            // term to be worked on.
            throw new course_creator_exception('Could not open '
                . $ims_file
                . ' already exists.');
        }

        $this->cron_term_cache['ims_fp'] = $fp;

        return $fp;
    }

    /**
     *  Returns the IMS pathname for the particular term.
     *
     *  May change the state of the object.
     *
     *  @return string The absolute path to the IMS file.
     **/
    function get_ims_file_path() {
        if (!isset($this->cron_term_cache['ims_path'])) {
            // Maybe we should throw an exception...
            $this->prepare_ims_files();
        }
        
        return $this->cron_term_cache['ims_path'];
    }

    /**
     *  Returns the pathname for the IMS logs for a particular term.
     *
     *  @return string The absolute path to the IMS log.
     **/
    function get_ims_log_path() {
        if (!isset($this->cron_term_cache['ims_log'])) {
            $this->prepare_ims_files();
        }

        return $this->cron_term_cache['ims_log'];
    }

    /**
     *  Will figure out the defaults to use when setting up courses.
     *
     *  Will change the state of the object.
     *
     *  @return Array The course default settings.
     **/
    function get_course_defaults() {
        $term = $this->get_cron_term();

        if (!$term) {
            throw new course_creator_exception();
        }

        if (isset($this->cron_term_cache['defaults'])) {
            return $this->cron_term_cache['defaults'];
        }

        if (!isset($this->course_defaults)) {
            $this->course_defaults = get_config('moodlecourse');
        }
        
        if ($this->match_summer($term)) {
            // @todo CCLE-2541 make this configurable: number of section in summer?
            $numsections = 6;
        } else {
            $numsections = $this->course_defaults->numsections;
        }

        $defredir = 'uclaredir';
        $defformat = $this->course_defaults->format;
        $deftheme = get_config('core', 'theme');
        
        $defaults = array();

        $child = new StdClass();
        $child->format = $defredir; 
        $child->numsections = $numsections;
        $child->theme = $deftheme;
        $child->visible = 0;
        $defaults['child'] = $child;

        $meta = clone $child;
        $meta->format = $defformat;
        $meta->visible = $this->course_defaults->visible;
        $defaults['meta'] = $meta;

        $regular = clone $meta;
        $defaults['regular'] = $regular;

        $this->cron_term_cache['defaults'] = $defaults;

        return $defaults;
    }

    /**
     *  Builds the MyUCLA URL update webservice URL.
     *
     *  Changes the state of the object (does caching...).
     *
     *  @param $term The term to upload.
     *  @param $srs The SRS of the course to upload.
     *  @param $url The url to update to. Can be null.
     *  @return string The URL for the MyUCLA update.
     **/
    function get_MyUCLA_service($term, $srs, $url=null) {
        if (!isset($this->myucla_login) || $this->myucla_login == null) {

            $cc_url = $this->get_config('course_creator_url_service');

            $cc_name = $this->get_config('course_creator_myucla_name');
            $cc_email = $this->get_config('course_creator_myucla_email');

            $mu_url = $cc_url 
                . '?name=' . urlencode($cc_name)
                . '&email=' . $cc_email;
            
            $this->myucla_login = $mu_url;
        }
        
        $returner = $this->myucla_login . '&term=' . $term
            . '&srs=' . $srs;

        if ($url != null) {
            $returner .= '&url=' . urlencode($url);
        }

        return $returner;
    }

    /**
     *  Return if the instructor should be emailed to people.
     *
     *  @param mixed $instructor The instructor from ccle_CourseInstructorGet
     *  @param array $profcode_set A set of profcodes for the course.
     **/
    function get_viewable_status($instructor, $profcode_set) {
        // @todo CCLE-2541 Use capabilities
        return true;
    }

    /**
     *  Returns whether we should send URL updates and eMails.
     *
     *  @return boolean Whether we should send. Default is true.
     **/
    function get_send_informations() {
        return $this->send_informations;
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
     *  Closes the ADOConnection object for Registrar connection.
     *
     *  May change the state of object.
     **/
    function close_registrar_connection() {
        if ($this->registrar_conn == null) {
            return false;
        }

        $this->println('Closing Registrar Connection...');

        $this->registrar_conn->Close();

        $this->registrar_conn = null;

        return true;
    }
    
    /**
     *  This will close the IMS XML file pointer.
     *  Once this is called, you need to flush the term to open the 
     *  file again...
     * 
     *  Maybe this design should be changed...
     *  
     *  Will change the state of the object.
     **/
    function close_ims_file_pointer() {
        if (!isset($this->cron_term_cache['ims_fp'])) {
            return false;
        }

        fclose($this->cron_term_cache['ims_fp']);
        unset($this->cron_term_cache['ims_fp']);

        return true;
    }

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
        }
    }

    /**
     *  Will append the list of specified term-srs objects to the internal 
     *  queue of things to build.
     *
     *  @param Array $srsobjects An a Array of objects with ->srs and ->term fields,
     *      or an Array of Arrays with 'srs' and 'term' keys. 
     *  @return boolean|int How many records were newly inserted.
     **/
    function set_srs_list($srsobjects) {
        if (empty($srsobjects)) {
            return false;
        }

        $counter = 0;

        // Techincally not an srs"object" but whatever
        foreach ($srsobjects as $srsobject) {
            if (is_object($srsobject)) {
                $srsobject = get_object_vars($srs_object);
            }

            if (!isset($srsobject['term']) || !isset($srsobject['srs'])) {
                continue;
            }

            if ($this->srs_list == null) {
                $this->srs_list = array();
            }

            $term = $srsobject['term'];
            if (!isset($this->srs_list[$term])) {
                $this->srs_list[$term] = array();
            }

            $srs = $srsobject['srs'];
            if (!isset($this->srs_list[$term][$srs])) {
                $this->srs_list[$term][$srs] = true;
                $counter++;
            }
        }

        return $counter;
    }

    /**
     *  Forces debug to turn on / off.
     *
     *  @param $bool boolean Force debug on, or turn off logging.
     *  
     **/
    function set_debug($bool) {
        $this->force_debug = $bool;
    }

    /**
     *  Default debugging mode back to Moodle Debugging.
     **/
    function unset_debug() {
        $this->force_debug = null;
    }

    /**
     *  Set whether to send information to MyUCLA, Instructors and Requestors.
     *
     *  @param $bool boolean Send information out?
     **/
    function set_send_informations($bool) {
        $this->send_informations = $bool;
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
        $this->flush_cron_term();

        if (!$this->set_cron_term($term)) {
            throw new course_creator_exception(
                'Could not set the term [' . $term . ']'
            );
        }

        $this->debugln("-------- Starting $term ---------");
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

        if (!$this->get_cron_term()) {
            return false;
        }

        if ($done) {
            // Figure out what to email the requestors.
            $this->queue_requestors();

            // Just mark the ones we've processed as done.
            $ids = array();
            foreach ($this->cron_term_cache['requests'] as $request) {
                $ids[] = $request->id;
            }

            list($sql_in, $params) = $DB->get_in_or_equal($ids);
            $sql_where = 'id ' . $sql_in;

            $DB->set_field_select('ucla_request_classes', 'status',
                'done', $sql_where, $params);

            $this->debugln('Successfully processed ' . count($params)
                . ' requests.');
        } else {
            $this->revert_cron_term();
        }
        
        // Close files that may be open
        $this->close_ims_file_pointer();

        $this->debugln('-------- Finished ' . $this->get_cron_term() 
            . ' --------');

        return true;
    }

    /**
     *  This will attempt to undo all the changes in cron_term_cache.
     *  It should also mark all the requests that are processing as 
     *  reverted.
     *  
     *      
     **/
     function revert_cron_term() {
        global $DB;

        $this->debugln("Attempting to revert requests for " 
            . $this->get_cron_term() . '...');

        // Do something with these requests
        $action_ids = array();

        // These are courses that were successfuly built, but were not deleted
        // even though the course creator reverted.
        $action_ids['failed'] = array();

        // These are courses that were sucessfully built, and successfully 
        // deleted when the course creator reverted.
        $action_ids['rebuild'] = array();

        // Save a config setting
        $reverting = $this->get_config('course_creator_revert_failed_cron');

        // We're going to attempt to delete a course, and if we fail,
        // save it somewhere.
        $course_mapper =& $this->cron_term_cache['course_mapper'];
        $requests =& $this->cron_term_cache['requests'];

        // If there were failed courses, since we enabled revert failed 
        // cron, we're going to fix things
        if (isset($this->cron_term_cache['created_courses'])) {
            $this->debugln('Attempting to revert created courses...');

            $delete_these = $this->cron_term_cache['created_courses'];

            $failed = 0;

            // There's a lot to delete...
            // Well, per course actually, there should be nothing...
            foreach($delete_these as $course) {
                $this->debugln('Deleting ' . $course->shortname . ' '
                    . $course->id . ' ----');

                if ($reverting) {
                    // Try to catch the output of delete_course()
                    ob_start();

                    $result = delete_course($course->id);

                    $delete_results = ob_get_clean();
                } else {
                    $result = false;

                    $delete_results = 'Reverting off.';
                }

                // Save the request id for marking
                unset($request_id);
                if (isset($course_mapper[$course->idnumber])) {
                    $srs = $course_mapper[$course->idnumber];
                    $request_id = $requests[$srs->srs]->id;
                }

                if (!$result) {
                    $action = 'failed';
                    $failed++;
                } else {
                    $action = 'rebuild';
                }

                if (isset($request_id)) {
                    $action_ids[$action][$request_id] = $request_id;
                }

                $this->println($delete_results);
                $this->println(' Done ----' . "\n");
            }

            // Update course count in categories.
            fix_course_sortorder();

            $this->debugln('Reverted ' . (count($delete_these) - $failed)
                . ' courses. ' . $failed . ' failed.');
        }

        // Mark these entries as failed
        foreach ($action_ids as $action => $ids) {
            if (!empty($ids)) {
                list($sql_in, $params) = $DB->get_in_or_equal($ids);

                $sql_where = 'id ' . $sql_in;

                // Hehe, two dee-bee-queue
                $DB->set_field_select('ucla_request_classes', 'action', 
                    $action, $sql_where, $params);

                // This is just because I am lazy
                $DB->set_field_select('ucla_request_classes', 'status', 
                    'pending', $sql_where, $params);

                $this->debugln('Marked ' . count($params) . ' requests for ' 
                    . $action . '.');
            }
        }

        $this->debugln('Finished reverting.');
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

        $sql_params = array($term);
        $sql_where = "
            action LIKE '%uild'
                AND
            term = ?
                AND
            status = 'processing'
        ";
        
        // See if we want to do certain SRS's only...
        // There is a better way of doing this, that is to set the entries
        // in ucla_request_classes status = 'deferred' if it is not within
        // one of the SRS's we want to create
        $srs_list = $this->get_term_srs_creating();
        if (!empty($srs_list)) {
            list($sql_in, $srs_params) = $DB->get_in_or_equal($srs_list);

            $sql_where .= "
                    AND
                srs IN $sql_in
            ";

            $sql_params = array_merge($sql_params, $srs_params); 
        }

        // These are the regular and host courses
        $course_requests = $DB->get_records_select(
            'ucla_request_classes', $sql_where, $sql_params
        );

        if (empty($course_requests)) {
            $mesg = "No courses for $term.";
            $this->debugln($mesg);

            return false;
        }

        // Maintain the crosslists that have been requested for later
        $crosslisted_courses = array();

        // Figure out crosslists and filter out faulty requests
        foreach ($course_requests as $key => $course_request) {
            $srs = trim($course_request->srs);

            if (strlen($srs) != 9) {
                $this->debugln('Faulty SRS: ' . $course_request->course);
                unset($line);

                unset($course_requests[$key]);
                continue;
            }

            if ($course_request->crosslist == '1') {
                $this->get_enrol_plugin('meta');

                $crosslisted_courses[$key] = $srs;

                $course_requests[$key]->crosslisted = array();
            }
        }

        // Re-index
        $course_set = array();
        foreach ($course_requests as $cr) {
            $course_set[$cr->srs] = $cr;
        }

        unset($course_requests);
    
        if (empty($crosslisted_courses)) {
            $this->insert_requests($course_set);

            return true;
        }

        // Select * from {ucla_request_crosslist} ...
        list($sql_in, $params) = 
            $DB->get_in_or_equal($crosslisted_courses);

        $sql_where = "
            srs $sql_in
                AND
            term = ?
        ";

        $sql_params = $params;
        $sql_params[] = $term;

        $crosslisted_requests = 
            $DB->get_records_select('ucla_request_crosslist', 
                $sql_where, $sql_params);

        if (empty($crosslisted_requests)) {
            throw new course_creator_exception(
                $srs . ' is crosslisted but no ucla_request_crosslist found!'
            );
        }

        // Attach the crosslisted requests to the original request classes
        foreach ($crosslisted_requests as $crosslisted_request) {
            $srs = trim($crosslisted_request->srs);

            if (isset($course_set[$srs])) {
                $crosslisted_srs = trim($crosslisted_request->aliassrs);
    
                // Attach the crosslisted course to the host course
                $course_set[$srs]->crosslisted[$crosslisted_srs] = 
                    $crosslisted_request;
            } else {
                $debug = '';
                foreach ($course_set as $c) {
                    $debug .= $c->srs . ' ';
                }

                throw new course_creator_exception(
                    'Could not find host course ' . $srs
                    . ' dump: ' . $debug
                );
            }
        }

        $this->insert_requests($course_set);

        $this->println('Finished processing requests.');

        return true;
    }

    /**
     *  Formats and inserts the data into our object.
     *  Called by @see retrieve_requests().
     *
     *  Changes the state of the object.
     *  @todo This CAN be abstracted out with insert_local_entry,
     *      and key_field_instructors().
     *
     *  @param The set of requested courses, with crosslisted hierarchy.
     **/
    function insert_requests($courses) {
        foreach ($courses as $course) {
            $this->trim_object($course);

            $this->println('Received request: ' . $course->term . ' ' 
                . $course->srs . ' ' . $course->course);

            if ($course->crosslist == '1') {
                foreach ($course->crosslisted as $cl_course) {
                    $this->println('   X-listed with: '
                        . $cl_course->term . ' ' . $cl_course->aliassrs); 
                }
            }

            $this->cron_term_cache['requests'][$course->srs] = $course;
        }
    }

    /**
     *  Will call a stored procedure and respond to each entry recieved 
     *  from the stored procedure with a specified function from this object.
     *  
     *  I apologize beforehand for the complexity of this function
     *
     *  Will change the state of the object.
     *
     *  @param $stored_proc The stored procedure to call.
     *  @param $response_func_args
     *      Sent to {@see insert_local_entry()}.
     *      Can be null, see defaults for {@see insert_local_entry()}.
     *  @param $additional_func_obj
     *      ->function to call per row from the driving table.
     *      ->arguments to send as arguments.
     *      Can be null, can be just a string (no arguments).
     *  @param $stored_proc_func_obj
     *      ->function to call to generate the stored procedure statement.     
     *      ->arguments to send as arguments.
     *      null defaults to @see sp_term_srs().
     *  @param $driving_data
     *      The table to drive the stored procedures with.
     *      null defaults to 'requests'.
     *  @return boolean If the thing actually completed.
     **/
    function retrieve_registrar_info(
        $stored_proc, 
        $response_func_args=null,
        $additional_func_obj=null,
        $stored_proc_func_obj=null,
        $driving_data='requests'
    ) {
        $this->println('Prepping for ' . $stored_proc . '...');

        // Default arguments are complicated for this function :(
        $stored_proc_def = new StdClass();
        $stored_proc_def->function = 'sp_term_srs';

        $additional_func_def = new StdClass();
        $additional_func_def->function = null;

        // Swimmingly fantastic and convoluted and terrible 
        $lazy = array(
            'additional_func_obj' => $additional_func_def, 
            'stored_proc_func_obj' => $stored_proc_def
        );

        // Set default arguments... and flatten them :(
        foreach ($lazy as $func_obj_name => $default) {
            // Figure out to use defaults or not
            if (${$func_obj_name} != null) {
                // The argument was passed
                $func_obj = ${$func_obj_name};

                // Validate that the object was passed properly
                // We can pass jsut a string as the function name
                if (is_string($func_obj)) {
                    $func_name = $func_obj;
                    $func_obj = new StdClass();
                    $func_obj->function = $func_name;

                    ${$func_obj_name} = $func_obj;
                } else if (!isset($func_obj->function)) {
                    throw new course_creator_exception(
                        'You need property "function" in  ' 
                        . $func_obj_name
                    );
                }

                // Validate that the function exists
                if (!method_exists($this, $func_obj->function)) {
                    throw new course_creator_exception(
                        'Sorry, ' . get_class($this) 
                        . ' does not have method ' . ${$func}
                    );
                }
            } else {
                // We can force certain arguments to not be null
                if ($default == null) {
                    throw new course_creator_exception(
                        $func_obj_name . ' cannot be null'
                    );
                }

                // Set a default object
                ${$func_obj_name} = $default;
            }

            // Flatten out function call
            $flat_func_name = preg_replace(
                '/(.*)_obj/', '$1', $func_obj_name
            );

            ${$flat_func_name} = ${$func_obj_name}->function;

            // Flatten out function argument
            $flat_func_arg_name = $flat_func_name . '_args';
            if (isset(${$func_obj_name}->arguments)) {
                ${$flat_func_arg_name} = ${$func_obj_name}->arguments;
            } else {
                ${$flat_func_arg_name} = null;
            }
        }
        
        // Make sure we have set ourselves up correctly
        if (!isset($this->cron_term_cache[$driving_data]) 
          || empty($this->cron_term_cache[$driving_data])) {
            throw new course_creator_exception(
                'retrieve_registrar_info ' . $stored_proc 
                . ' driving data empty!'
            );
        }

        // Open the Registrar connection, automatically maintained
        $db_reg = $this->get_registrar_connection();

        foreach ($this->cron_term_cache[$driving_data] as $drive_course) {
            // Using the callback function, figure out our EXECUTE 
            // statement
            $qr = $this->$stored_proc_func($drive_course, $stored_proc, 
                $stored_proc_func_args);

            $recset = $db_reg->Execute($qr);

            $deferred = array();
            if (!$recset->EOF) {
                while ($fields = $recset->FetchRow()) {

                    // @todo CCLE-2541 In the external db enrollment plugin 
                    // they have encoding detection code
                    $deferred[] = array($fields, $drive_course, 
                        $response_func_args);
                }
            } else {
                throw new course_creator_exception("$qr returned 0 rows");
            }

            $recset->Close();

            if (!empty($deferred)) {
                try {
                    foreach ($deferred as $func_call) {
                        list($fields, $drive_course, $response_func_args)
                            = $func_call;

                        // Handle each row as specified in the 
                        // response function
                        $this->insert_local_entry($fields, $drive_course,
                            $response_func_args);
                    }
                } catch (course_creator_exception $e) {
                    $this->debugln($e->getMessage());
                    continue;
                }
            }

            // Do more stuff if necessary
            if ($additional_func !== null) {
                $this->$additional_func($drive_course, 
                    $additional_func_args);
            }
        }

        $this->close_registrar_connection();

        return true;
    }

    /**
     *  Will generate a simple EXECUTE statement with term then SRS.
     *
     *  Used as a callback to {@see retrieve_registrar_info()}.
     *
     *  @param $entry The object to use, must contain term and srs.
     *  @param $sp The stored procedure to use.
     *  @return string The EXECUTE statement.
     **/
    function sp_term_srs($entry, $sp) {
        if (!isset($entry->srs) || !isset($entry->term)) {
            throw new course_creator_exception(
                'sp_term_srs missing srs or term'
            );
        }

        $srs = $entry->srs;
        $term = $entry->term;

        return "EXECUTE $sp '$term', '$srs'";
    }

    /**
     *  Checks if a request requires crosslists, then calls
     *  @see retrieve_registrar_info() with that particular class.
     *
     *  Used as a callback for {@see retrieve_registrar_info()}.
     *
     *  @param $entry 
     *      The entry that has $entry.crosslisted = Array, and
     *      each $entry.crosslisted has term and srs.
     **/
    function retrieve_registrar_crosslists($entry) {
        // I don't think I need to check for bad data...
        if ($entry->crosslist == '1') {
            $e_srs = $entry->srs;
            $ctc_key = 'reg_cls_' . $e_srs;
            $this->cron_term_cache[$ctc_key] = array(); 

            $prototype = clone $entry;
            unset($prototype->crosslisted);

            foreach ($entry->crosslisted as $req_cl) {
                $cl_entry = clone $prototype;
                $cl_srs = $req_cl->aliassrs;
                $cl_entry->srs = $cl_srs;

                $this->cron_term_cache[$ctc_key][$cl_srs] = $cl_entry;
            }

            // Nydus canal!
            $this->retrieve_registrar_info('ccle_getClasses',
                $this->reg_callback_object, null, null, $ctc_key);

            unset($this->cron_term_cache[$ctc_key]);
        }
    }

    /**
     *  Does a quick check on the entry and then inserts the data 
     *  into the object.
     *
     *  This function should NEVER be called directly.
     *
     *  Callback for {@see retrieve_registrar_info()}.
     *
     *  Will change the state of the object.
     *
     *  @param $entry The information from the Registrar.
     *  @param $original The corresponding request entry.
     *  @param $args Additional arguments.
     *      ->tests Array of tests to validate entry with the original.
     *          defaults to checking srs and term.
     *      ->term_table string The table to insert into the term cache.
     *          defaults to requests
     *      ->key_field string The field of the entry to use as a key.
     *          defaults to srs
     **/
    function insert_local_entry($entry, $original, $args) {
        $entry = array_change_key_case($entry, CASE_LOWER);

        // Default fallback...
        $tests = array(
            'srs' => 'srs',
            'term' => 'term'
        );

        if (isset($args->tests)) {
            $tests = $args->tests;
        }

        // Run the specified test_func on the local row
        if (isset($args->test_func)) {
            if (method_exists($this, $args->test_func)) {
                $result = $this->{$args->test_func}($entry);

                if (!$result) {
                    // If our test fails, do nothing 
                    return;
                }
            }
        }

        // Make sure some basics exist
        foreach ($tests as $test => $o_test) {
            if (!isset($entry[$test]) 
              || $entry[$test] != $original->$o_test) {
                throw new course_creator_exception(
                    'Incorrect ' . $test . ' from Registrar ' 
                        . $original->$o_test
                );
            }
        }

        $entry_object = (object) $entry; 

        if ($args != null && isset($args->key_field) 
          && method_exists($this, $args->key_field)) {
            $this->{$args->key_field}($entry_object);

            // We can just return
            return true;
        } 

        // Insert with just the srs as the key
        $key = $entry_object->srs;
        if (isset($this->cron_term_cache['term_rci'][$key])) {
            throw new course_creator_exception(
                'Repeated table term_rci key ' . $key
            );
        }

        $this->cron_term_cache['term_rci'][$key] = $entry_object;
    }

    /**
     *  Inserts the instructor into our local Arrays.
     *
     *  Used as a callback in {@see insert_local_entry()}.
     *
     *  Will modify the state of the object.
     *
     *  @param $entry The entry from the Registrar.
     *  @return string The Array key.
     **/
    function key_field_instructors($entry) {
        $srs = $entry->srs;

        // Just kind of make things pretty
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
     *  Checks that the object passed by has no empty fields.
     *
     *  Callback for {@see insert_local_entry()}.
     *
     *  @param $entry The object to check.
     *  @return boolean If the object is not empty or not not empty.
     **/
    function no_empty($entry) {
        foreach ($entry as $key => $data) {
            if (trim($data) == '') {
                return false;
            }
        }

        return true;
    }

    /**
     *  Generates IMS file for a term.
     *  
     *  Writes to an output file, which is dynamically determined.
     **/
    function generate_ims_entries() {
        if (!isset($this->cron_term_cache['term_rci']) 
                && empty($this->cron_term_cache['term_rci'])) {
            return false;
        }

        // Get the IMS XML file pointer
        $ims_fp = $this->get_ims_file_pointer();

        $ims_lines = array();

        // Turn each entry from the Registrar into an XML entry
        foreach ($this->cron_term_cache['term_rci'] as $rci_object) {
            unset($req_course);

            $ims_term = trim($rci_object->term);
            $ims_srs = trim($rci_object->srs);

            // See if we can get certain information from the requests
            if (!isset($this->cron_term_cache['requests'][$ims_srs])) {
                // This is a crosslisted child course
                $ims_visible = 0;
                $req_course->crosslist = 0;
            } else {
                $req_course = 
                    $this->cron_term_cache['requests'][$ims_srs];
                $ims_visible = 1;

                $req_course->visible = 1;
            }

            $ims_type = rtrim($rci_object->acttype);
            $ims_sess = rtrim($rci_object->session_group);

            $ims_desc = $rci_object->crs_desc;

            $subj = rtrim($rci_object->subj_area);
            $ims_num  = rtrim($rci_object->coursenum);
            $ims_sect = rtrim($rci_object->sectnum);

            // Get latter part of the shortname
            $ims_course = $this->make_course_name($subj, $ims_num, 
                $ims_sect);

            $ims_title = $this->make_course_title(
                trim($rci_object->coursetitle), 
                trim($rci_object->sectiontitle)
            );

            // Get the long version of the subject area (for category)
            $ims_subj = $this->get_subject_area_translation($subj);

            // This means that we have to build a master course
            if ($req_course->crosslist == '1') {
                $ims_lines[] = $this->course_IMS($ims_title, 
                    $this->make_idnumber($ims_term, $ims_srs, TRUE), 
                    $ims_sess, $ims_desc, $ims_course, $ims_term,
                    $ims_subj, 1);

                $ims_course = $ims_course . 'c';
            }
           
            // Make the child course or the regular course 
            $ims_lines[] = $this->course_IMS($ims_title, 
                $this->make_idnumber($ims_term, $ims_srs), 
                $ims_sess, $ims_desc, $ims_course, $ims_term,
                $ims_subj, $ims_visible);
        }

        $this->printl('Generating IMS file...');

        // Write the IMS file
        foreach ($ims_lines as $ims_line) {
            fwrite($ims_fp, $ims_line);
        }

        $this->println('Done');

        // Close the damn file
        $this->close_ims_file_pointer();
    }

    /**
     *  Run the IMS import.
     *  Wrapper for {@see enrol_imsenterprise_plugin()}.
     **/
    function ims_cron() {
        if (!class_exists('enrol_imsenterprise_plugin')) {
            throw new course_creator_exception(
                'Missing IMS plugin in code.'
            );
        }

        // Get the object
        $ims_enrol = $this->get_enrol_plugin('imsenterprise');

        // Prepare to configure
        $ims_filepath = $this->get_ims_file_path();
        $ims_logpath = $this->get_ims_log_path();

        $ims_configs = array();
        $ims_configs['imsfilelocation'] = $ims_filepath;
        $ims_configs['logtolocation'] = $ims_logpath;

        $ims_configs['mailadmins'] = '';
        $ims_configs['prev_path'] = '';

        // Just in case, although the logic looks as though it should be 
        // fine because of prev_path
        $ims_configs['prev_time'] = 0;
        $ims_configs['prev_md5'] = NULL;

        foreach ($ims_configs as $ims_config_name => $ims_config_value) {
            $ims_enrol->set_config($ims_config_name, $ims_config_value);
        }

        $this->println('IMS configured, running...');
        $ims_enrol->cron();
        $this->println('IMS finished running, check ' . $ims_logpath
            . ' for cron log.');

        // Let's run this check in here
        $this->activate_courses();
    }
    
    /**
     *  Figures out the location and creates the IMS related file 
     *  paths and pointers.
     *
     *  Will change the state of the object.
     **/
    function prepare_ims_files() {
        if (isset($this->cron_term_cache['ims_path'])) {
            // This will prevent someone from opening the IMS file 
            // once they've decided to close it.
            throw new course_creator_exception('IMS file created already ' 
                . $this->cron_term_cache['ims_path'] 
                . ', without flushing term cache!');
        }

        $term = $this->get_cron_term();
        if (!$term) {
            throw new course_creator_exception(
                'No term set, trying to create IMS files.'
            );
        }

        if (!isset($this->output_path)) {
            $this->check_write();
        }
        
        $ims_file = $this->output_path . '/integration.'
            . $this->shell_date . ".$term.xml";

        $this->debugln('IMS File: ' . $ims_file);
        $this->cron_term_cache['ims_path'] = $ims_file;

        $ims_log = $this->output_path . '/ims-log.' . $this->shell_date 
            . ".$term.log";

        $this->debugln('IMS Log : ' . $ims_log);
        $this->cron_term_cache['ims_log'] = $ims_log;
    }

    /**
     *  Checks to make sure that the expected courses were created via IMS.
     *  Also puts the created courses into the cache of the object.
     *
     *  Will change the state of the object.
     **/
    function check_built_requests() {
        global $DB;

        // We run through this to make sure we have built all predicted 
        // idnumbers
        $check_srs = array();

        // Local reference hierarched requests
        $ctc_tr =& $this->cron_term_cache['requests'];

        // Local reference for RCI
        $rci_courses =& $this->cron_term_cache['term_rci'];

        // Foreach course we got courseInfo for, we are going to make sure
        // They are in the courses table
        foreach ($rci_courses as $rci) {
            $srs = $rci->srs;
            $term = $rci->term;

            $idnumber = $this->make_idnumber($term, $srs);
            $check_srs[$idnumber] = $srs;

            $srs_to_idnumber[$srs] = $idnumber;

            // This means that we are building a child course,
            // so no need to check for a crosslist
            if (!isset($ctc_tr[$srs])) {
                continue;
            }

            $request = $ctc_tr[$srs];
            if ($request->crosslist == '1') {
                $master_idnumber = $this->make_idnumber($term, $srs, true);

                $check_srs[$master_idnumber] = $srs;
            }
        }

        $check_idnumbers = array_keys($check_srs);
        list($sql_in, $params) = $DB->get_in_or_equal($check_idnumbers);

        $where_sql = "
            idnumber $sql_in
        ";

        $created_courses = $DB->get_records_select(
            'course', $where_sql, $params
        );

        // re-index by idnumber
        $created_courses_check = array();
        foreach ($created_courses as $cc) {
            $created_courses_check[$cc->idnumber] = $cc;
        }

        // We are checking that we created all our courses
        foreach ($check_srs as $idnumber => $srs) {
            if (!isset($created_courses_check[$idnumber])) {
                throw new course_creator_exception(
                    'IMS did not build: ' . $idnumber
                );
            } else {
                // This is used when reverting a failed term
                if (isset($ctc_tr[$srs])) {
                    $this->cron_term_cache['course_mapper'][$idnumber] = 
                        $ctc_tr[$srs];
                } 
            }

            if (!isset($rci_courses[$srs])) {
                throw new course_creator_exception(
                    'Entry in RCI not requested: ' . $srs
                );
            }
        }

        // From here, we can revert things, so we want to store things
        // in the cron_term_cache, indexed by idnumber.
        $this->cron_term_cache['created_courses'] = $created_courses_check;
    }

    function activate_courses() {
        if (!isset($this->cron_term_cache['created_courses'])) {
            $this->check_built_requests();
        }

        $created_courses_check =& $this->cron_term_cache['created_courses'];

        // This might be used later to validate metacourse relationships 
        // got built.
        $crli_cid_summary = array();

        // This is the courses to update using our default settings
        $this->cron_term_cache['activate'] = array();

        // Reference to the defaults
        $defaults = $this->get_course_defaults();

        foreach ($defaults as $course_type => $stuff) {
            // This stores [course_id] = course_id
            $this->cron_term_cache['activate'][$course_type] = array();
        }

        // We are going to link child to parent courses
        // Here we are going to send stuff in the email summary
        foreach ($this->cron_term_cache['requests'] as $request) {
            $course_srs = $request->srs;
            $term = $request->term;

            // Sort into three groups, child, meta or regular
            if ($request->crosslist == '1') {
                // Get the course_srs course id
                $master = $this->make_idnumber($term, $course_srs, true);
                $master_course = $created_courses_check[$master];

                $this->emailln('Created Master course: ' 
                    . $master_course->shortname);

                $mcid = $master_course->id;

                $child_srs = $request->crosslisted;
                $child_srs[$course_srs] = $course_srs;

                foreach ($child_srs as $child => $not_used) {
                    $cidn = $this->make_idnumber($term, $child);

                    $child_course = $created_courses_check[$cidn];
                    $cid = $child_course->id;

                    $this->emailln('Crosslisted with: '
                        . $child_course->shortname);

                    $this->cron_term_cache['activate']['child'][$cid] = 
                        $cid;

                    $crli_cid_summary[$master][$cid] = $cid;
                }
        
                $this->cron_term_cache['activate']['meta'][$mcid] = $mcid;
            } else {
                $regular = $this->make_idnumber($term, $course_srs);
                $reg_course = $created_courses_check[$regular];

                $this->emailln('Created regular course: '
                    . $reg_course->shortname);

                $rid = $reg_course->id;

                $this->cron_term_cache['activate']['regular'][$course_srs] 
                    = $rid;
            }
        }

        // Nice space
        $this->emailln("\n");

        // Coding check
        foreach ($this->cron_term_cache['activate'] as $type => $nothing) {
            if (!isset($defaults[$type])) {
                throw new course_creator_exception(
                    'Course type ' . $type . ' is not handled.'
                );
            }
        }

        // Assign the meta courses
        $meta = $this->get_enrol_plugin('meta');

        foreach ($crli_cid_summary as $master_idnumber => $children) {
            $parent_course = $created_courses_check[$master_idnumber];
            $master = $parent_course->id;

            $parent_instances = enrol_get_instances($master, false);

            foreach ($children as $child) {
                // Stolen from enrol/meta/addinstance.php
                $this->println("Adding metacourse enrollment to "
                    . $parent_course->id . ', for course ' . $child);

                $enrol_id = $meta->add_instance($parent_course, 
                    array('customint1' => $child));
            }

            // Save this so that we can revert if wanted
            $this->cron_term_cache['mastercourses'][] = $master;

            // Make sure the enrolments sync? I dunno, this probably 
            // is not that very important, since there probably are no
            // roles to sync in a newly created course
            //enrol_meta_sync($parent_course->id);
        }

        // @todo CCLE-2541 We might need to add a self-check to validate that
        // meta courses were added properly

        // Moodle 2 does not have something amazing to do many uploads, but
        // they do have this unimplemented $bulk argument
        $start_time = time();

        foreach ($this->cron_term_cache['activate'] 
                as $course_type => $courses) {

            $course_default = clone ($defaults[$course_type]);

            foreach ($courses as $course_id) {

                $course_default->id = $course_id;

                // This uses the $bulk argument, but for mysql it does not
                // do anything special
                //$DB->update_record('course', $course_default, true);
                // This function does not return anything...
                update_course($course_default);
                $this->println('Setup ' . $course_default->id 
                    . ' as ' . $course_type . ', format '
                    . $course_default->format . ' sections '
                    . $course_default->numsections);
            }
        }
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

        // Create references, not copies
        $created =& $this->cron_term_cache['created_courses'];
        $requests =& $this->cron_term_cache['requests'];

        // For each requested course, figure out the URL
        foreach ($requests as $request) {
            unset($idnumber);

            $srs = $request->srs;
            $term = $request->term;
            
            $crc = false;
            if ($request->crosslist == '1') {
                $idnumber = $this->make_idnumber($term, $srs, true);
                $crc = true;
            } else {
                $idnumber = $this->make_idnumber($term, $srs);
            }

            $url_info = $created[$idnumber];
            $url = $this->build_course_url($url_info);

            if ($crc) {
                // For crosslisted courses, update the term-srs combo with
                // the same url as the master course
                foreach ($request->crosslisted as $asrs => $crosslist) {
                    // Technically, all of these should have the same term
                    $relevant_url_info[$term][$asrs] = $url;
                }
            }

            $relevant_url_info[$term][$srs] = $url;
        }

        foreach ($relevant_url_info as $term => $srses) {
            foreach ($srses as $srs => $url) {

                $url_update_pull = $this->get_MyUCLA_service($term, $srs);
                $url_update_push = $this->get_MyUCLA_service($term, $srs, 
                    $url);

                if ($this->get_debug() || !$this->get_send_informations()) {
                    // Just print the statements
                    $this->println($url_update_pull);
                    $this->println($url_update_push);
                } else {
                    $myucla_curl = file_get_contents($url_update_pull);
                    $myucla_curl = $this->trim_strip_tags($myucla_curl);

                    if (strlen($myucla_curl) > 0 
                      && strpos($myucla, 'http://') !== 0) {
                        $myucla_old = "http://$myucla_curl";
                    }

                    $this->println(
                        "Updating [$myucla_old] to [$url] on MyUCLA"
                    );

                    // Let MyUCLA take a nap
                    sleep(1);

                    // If quiet mode, we will not send the update
                    if ($this->get_send_informations()) {
                        $myucla_curl = file_get_contents($url_update_push);
                    } else {
                        $this->println('Quiet mode, skipping.');
                        continue;
                    }

                    $myucla_curl = $this->trim_strip_tags($myucla_curl);

                    $this->println(
                        "MyUCLA responded: $myucla_curl"
                    );

                    if (strpos($myucla_curl, 'Update Successful') === 
                      false) {
                        $this->debugln(
                            "Warning: Could not update URL for $term-$srs:"
                                . $course_url
                        );
                    }
                }
            }
        }

        // This needs to be saved for emails
        $this->cron_term_cache['url_info'] = $relevant_url_info;
    }

    /**
     *  Sends emails to instructors and course requestors.
     *
     *  @throws course_creator_exception
     **/
    function send_emails() {
        if (empty($this->cron_term_cache['url_info'])) {
            throw new course_creator_exception(
                'We have no URL information for E-Mails.'
            );
        }   
        
        // These are the argument sent to insert_local_entry
        $user_callback_object = new StdClass();
        $user_callback_object->term_table = 'instructors';
        $user_callback_object->key_field = 'key_field_instructors';

        // This should fill the term cache 'instructors' with data from 
        // ccle_CourseInstructorsGet
        $this->retrieve_registrar_info('ccle_CourseInstructorsGet', 
            $user_callback_object, null, null, 'term_rci');

        if (empty($this->cron_term_cache['instructors'])) {
            // @TODO Maybe change the default behavior
            $this->debugln('No instructors for this term!');
        }

        // I think the old version works pretty well...
        // These are read-only, no need to duplicate contents 
        $courses =& $this->cron_term_cache['requests'];
        $rci_objects =& $this->cron_term_cache['term_rci'];
        $instructors =& $this->cron_term_cache['instructors'];
        $profcodes =& $this->cron_term_cache['profcodes'];
        $course_urls =& $this->cron_term_cache['url_info'];

        // This is to maintain people without reported URSA emails
        $this->cron_term_cache['no_emails'] = array();

        // These are the collection of people we are going to email
        $emails = array();
        foreach ($courses as $course) {
            $csrs = $course->srs;
            $term = $course->term;

            $rci_course = $rci_objects[$csrs];
            $dept = trim($rci_course->subj_area);

            $pretty_term = $this->term_to_text($term, 
                $rci_course->session_group);

            // Fall-back Look-up (if not in subj_trans, return dept)
            $dept_full = $this->get_subject_area_translation($dept);

            // Clean course number
            $coursenum = trim($rci_course->coursenum);

            // The course displayed in the email
            $course_disp = trim($dept_full . ' ' . $coursenum . ' ' 
                . $rci_course->acttype . ' ' . $rci_course->sectnum);

            // This is the courses to display the email for
            $course_c = array();
            $course_c[] = $course_disp;

            $course_dept = $rci_course->subj_area;

            // Include the child courses in the email
            if ($course->crosslist == '1') {
                foreach ($course->crosslisted as $child) {
                    $child_srs = $child->aliassrs;
                    $rci_course = $rci_objects[$child_srs];

                    $dept = trim($rci_course->subj_area);
                    $dept_full = $this->get_subject_area_translation($dept);

                    $coursenum = trim($rci_course->coursenum);

                    $course_disp = trim($dept_full . ' ' . $coursenum . ' ' 
                        . $rci_course->acttype . ' ' . $rci_course->sectnum);

                    $course_c[] = $course_disp;
                }
            }

            unset($rci_course);

            $course_text = implode(' / ', $course_c);

            // The instructors to be mailed email
            $show_instructors = array();

            if (!isset($profcodes[$csrs])) {
                $this->debugln('No instructors for ' 
                    . "$term $csrs $course_text.");
            } else {
                $profcode_set = $profcodes[$csrs];

                if (isset($instructors[$csrs])) {
                    foreach ($instructors[$csrs] as $instructor) {
                        $viewable = $this->get_viewable_status($instructor, 
                            $profcode_set);

                        if ($viewable) {
                            $show_instructors[] = $instructor;
                        }
                    }
                }

                if (empty($show_instructors)) {
                    $this->debugln("No instructors to email for "
                        . "$term $csrs ($course_text)!");
                }
            }

            $course_url = $course_urls[$term][$csrs];

            foreach ($show_instructors as $instructor) {
                $lastname = $this->format_name(
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
                $emails[] = $email_ref;
            }
        }

        // Try to check out local records for emails
        $local_emails = array();

        if (!empty($this->cron_term_cache['no_emails'])) {
            $this->get_local_emails();

            $local_emails = $this->cron_term_cache['local_emails'];
        }

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
                    $this->println("Cannot email $userid " 
                        . $emailing['lastname']);

                    $email_summary_data[$csrs][$userid] .= "! " 
                        . $emailing['lastname']
                        . "\t $userid \tFAILED - No email address.\n";
                    continue;
                } else {
                    $emailing['to'] = $local_emails[$userid];

                    $email_summary_data[$csrs][$userid] .= '* ' 
                        . $emailing['lastname']
                        . "\t $userid \t" . $local_emails[$userid] 
                        . " - Local email ONLY\n";
                }
            } 
            
            // Set the destination
            $email_to = $emailing['to'];

            // Handle special emails to THE STAFF and TA
            // These are filler users 
            if ($userid == '100399990') {
                $email_to = '';
                $add_subject = ' (THE STAFF)';
            } else if ($userid == '200399999') {
                $email_to = '';
                $add_subject = ' (TA)';
            }

            unset($emailing['userid']);

            // Parse the email
            // @todo normalize this value?
            $subj = $emailing['subjarea'];

            // Figure out which email template to use
            if (!isset($this->parsed_param[$subj])) {
                if (!isset($this->email_prefix)) {
                    $this->figure_email_vars();
                }

                $deptfile = $this->email_prefix . $subj . $this->email_suffix;

                if (file_exists($deptfile)) {
                    $file = $deptfile;
                } else {
                    $file = $this->default_email_file;
                }

                $this->parsed_param[$subj] = $this->email_parse_file($file);
            }

            // Still failing, or catch for debugging
            if (!isset($this->parsed_param[$subj])) {
                $this->debugln('Emails failed for subject area ' 
                    . $subj . ', most likely because we do not '
                    . 'have a DEFAULT email template.');

                $this->println();

                $headers = '-not parsed-';
                $email_subject = '-not parsed - ' 
                    . $emailing['coursenum-sect'] . ' '
                    . $emailing['url'];

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
                . $email_to . " \t $email_subject\n";

            if (!$this->get_debug()) {
                $this->println("Emailing: $email_to");

                if ($this->get_send_informations()) {
                    mail($email_to, $email_subject, $email_body, $headers);
                }
            } else {
                $this->println("to: $email_to");
                $this->println("headers: $headers");
                $this->println("subj: $email_subject");

                $this->println();

                // If debugging, send to the admin
                mail($this->get_config('course_creator_email'), 
                    $email_subject, $email_body);
            }
        }

        foreach ($email_summary_data as $srs => $course_data) {
            foreach ($course_data as $instr_data) {
                $this->emailln($instr_data);
            }
        }
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
        $sql_where = 'idnumber IN ' . $sql_in;

        $this->println("Searching local MoodleDB for idnumbers $sql_in...");

        $local_users = $DB->get_records_select('users', $sql_where, $params);

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

        $fp = fopen($file, 'r');

        if (!$fp) {
            echo "ERROR: could not open email template file $file \n";
            return ;
        }

        echo "Parsing $file ...\n";
        // first 3 lines are headers
        for ($x = 0; $x < 3; $x++) {
            $line = fgets($fp);
            if (preg_match('/'.'^FROM:(.*)'.'/i',$line, $matches)) {
                $email_params['from'] = trim($matches[1]);
            } else if (preg_match('/'.'^BCC:(.*)'.'/i',$line, $matches)) {
                $email_params['bcc'] = trim($matches[1]);
            } else if (preg_match('/'.'^SUBJECT:(.*)'.'/i',$line,$matches)) {
                $email_params['subject'] = $matches[1];
            }
        }
        
        if(sizeof($email_params) != 3) {
            echo "ERROR: failed to parse headers in $file \n";
            return false;
        }
        
        $email_params['body'] = '';
        
        while (!feof($fp)) { //the rest of the file is the body
            $email_params['body'] .= fread($fp, 8192);
        }
       
        echo "Parsing $file successful \n";
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
     *  Gathers the information needed to mail to the requestors.
     *
     *  Changes the state of the function.
     **/
    function queue_requestors() {
        if (!isset($this->cron_term_cache['requests'])) {
            throw new course_creator_exception(
                'No requests to email requestors!'
            );
        }

        $url_info =& $this->cron_term_cache['url_info'];

        // Gather requestors' courses
        foreach ($this->cron_term_cache['requests'] as $course) {
            if (!isset($url_info[$course->term][$course->srs])) {
                continue;                
            }

            $course_url = $url_info[$course->term][$course->srs];

            if (isset($course->contact) && !empty($course->contact)) {

                $contact = $course->contact;

                // Gather contacts, so we do not email requestors more than
                // once
                if (!isset($this->requestor_emails[$contact])) {

                    if (validate_email($contact)) {
                        $this->requestor_emails[$contact] = array();
                    } else {
                        $this->emailln("Requestor email $contact not valid "
                            . "for $term $csrs");
                    }
                }

                if (isset($this->requestor_emails[$contact])) {
                    $this->requestor_emails[$contact][$course_url] = 
                        $course_url;
                }
            }
        }
    }

    /**
     *  This will mail the requestors with the information we gathered.
     *
     **/
    function mail_requestors() {
        if (empty($this->requestor_emails)) {
            return false;
        }

        // Email course requestors
        foreach ($this->requestor_emails as $requestor => $created_courses) {
            $req_mes = $requestor_mesg_start 
                . implode("\n", $created_courses) . $requestor_mesg_end;

            $crecou_cnt = count($created_courses);
            if ($crecou_cnt > 1) {
                $req_subj_subj = $crecou_cnt . ' courses';
            } else {
                $req_subj_subj = reset($created_courses);
            }

            $req_subj = "Your request for $req_subj_subj has been processed.";

            $req_summary = implode(',', $created_courses);

            if (!$this->get_debug() && $this->get_send_informations()) {
                $resp = mail($requestor, $req_subj, $req_mes, 
                    $requestor_headers);

                if ($resp) {
                    $this->println("Emailed $requestor for $req_summary");
                } else {
                    $this->println("ERROR: course not email $requestor");
                }
            }

            $this->emailln("Emailed $requestor for $req_summary");
        }
    }

    /** ********************** **/
    /*  More Global Functions   */
    /** ********************** **/

    /**
     *  Check that we have an outpath set, if not, we will use moodledata.
     *  Check that we have write priviledges to the outpath, if not, we will 
     *      use moodledata.
     *
     *  Changes the state of the object.
     **/
    function check_write() {
        global $CFG;

        // Check if we have a path to write to
        // Default to moodledata otherwise
        if (!$this->get_config('course_creator_outpath')) {
            $this->output_path = $CFG->dataroot . '/course_creator';

            // This means we have no write priveledges to moodledata
            if (!file_exists($this->output_path)) {
                if (!mkdir($this->output_path)) {
                    throw new course_creator_exception('Could not make ' 
                        . $this->output_path);
                }
            }
        } else {
            $this->output_path = $this->get_config('course_creator_outpath');
        }

        // Test that we actually can write to the output path
        $test_file = $this->output_path . '/write_test.txt';

        if (!fopen($test_file, 'w')) {
            throw new course_creator_exception('No write permissions to ' 
                . $this->output_path);
        } 

        unlink($test_file);

        // This is saved for creating XML and log files
        $this->shell_date = date('Y-m-d-G-i');
        $this->full_date = date('r');
    }

    /**
     *  Will determine whether or not we can run this function.
     *  @param boolean $lock true for lock, false for unlock.
     *  @param boolean $hard true for file lock, false for no file lock
     *  @return boolean If we the action was successful or not.
     *  @since Moodle 2.0.
     **/
    function handle_locking($lock, $hard=true) {
        global $DB;

        // Get a unique id for this lock
        $this->db_id = $this->cryptify($this->get_config('dbname'));

        if (!isset($this->output_path)) {
            $this->check_write();
        }

        $cc_lock = $this->output_path . '/.lock-' . $this->db_id;

        // Prevent new requests that come in during course creation from 
        // affecting course creator
        if ($lock) {
            // We sometimes want to do a file lock
            if ($hard) {
                if (file_exists($cc_lock)) {
                    throw new course_creator_exception(
                        'Lock file already exists!
                    ');
                }

                $this->lockfp = fopen($cc_lock, 'x');
            }

            // this will let both build and rebuild be built
            $sql_where = "
                action LIKE '%uild'
                    AND
                status = 'pending'
            ";

            $DB->set_field_select('ucla_request_classes', 'status', 
                'processing', $sql_where);
        } else {
            if (!file_exists($cc_lock)) {
                // By here, we've already marked everything as finished, so
                // spit out a warning and just go about our day.
                if ($hard) {
                    $this->debugln(
                        'WARNING: Lock file disappeared during course'
                        . 'creation!'
                    );

                    $this->debugln('!! Your tables MAY be volatile !!');
                }
            } else {
                unlink($cc_lock);
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

        $this->println('---- Course creator end at ' . date('r') 
            . ' ----');

        // Email the summary to the admin
        mail($this->get_config('course_creator_email'), 
            'Course Creator Summary ' . $this->shell_date, $this->email_log);

        $this->close_log_file_pointer();

        return true;
    }
    
    /**
     *  Quick wrapper for a cryptography function.
     **/
    function cryptify($string) {
        return substr(md5($string), 0, 8);
    }

    /**
     *  Sets the terms to be run. Still here since I am lazy.
     *  @deprecated v2011041900
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
        if ($this->get_config('course_creator_terms')) {
            $terms_list = explode(' ', 
                $this->get_config('course_creator_terms'));
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
        if (!$this->get_config('course_creator_email_template_dir')) {
            throw new course_creator_exception('No email directory set!');
        }

        $this->email_prefix = 
            $this->get_config('course_creator_email_template_dir');

        $this->email_suffix = '_course_setup_email.txt';
        
        $this->default_email_file = $this->email_prefix . 'DEFAULT'
            . $this->email_suffix;
    }

    /** ************************ **/
    /*  Global Function Wrappers  */
    /** ************************ **/
    function course_IMS($title, $idnumber, $session, $description, $course,
            $term, $subject, $visible) {
    
        return "
    <group recstatus=1>
    <sourcedid>
    <source>$term$session-$course</source>
    <id>$idnumber</id>
    </sourcedid>
    <description>
    <short>$term$session-$course</short>
    <long><![CDATA[$title]]></long>
    <full><![CDATA[$description]]></full>
    </description>
    <org>
    <id><![CDATA[$term]]></id>
    <orgunit>$subject</orgunit>
    </org>
    <extension>
    <visible>$visible</visible>
    </extension>
    </group>
    ";

    }

    /**
     *  Will figure out what to interpret as the webpage.
     *
     *  @param $course The course object.
     *  @return string The URL of the course (no protocol).
     **/
    function build_course_url($course) {
        if ($this->get_config('ucla_friendlyurls_enabled')) {
            return new moodle_url('/course/view/' . $course->shortname);
        }

        return new moodle_url('/course/view.php', array('id' => $course->id));
    }

    /**
     *  Wrapper for {@see get_config()}
     **/
    function get_config($config) {
        $ucc_config = get_config('uclacoursecreator', $config);

        if (!$ucc_config) {
            return get_config(NULL, $config);
        }

        return $ucc_config;
    }

    /**
     *  Make sure the term is valid.
     *  @param $term The term.
     *  @return boolean Whether the term is valid or not.
     **/
    function validate_term($term) {
        return preg_match('/[0-9]{2}[1FWS]/', $term);
    }

    /**
     *  Build the shortname from registrar information.
     *  @param $subjarea The subject area short name.
     *  @param $coursenum The number of the course.
     *  @param $coursesect The section of the course.
     *  @return string The shortname, without the term.
     **/
    function make_course_name($subjarea, $coursenum, $coursesect) {
        $course = $subjarea . $coursenum . '-' . $coursesect;
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
    function make_course_title($course_title, $section_title) {
        if ($section_title == '') {
            return $course_title;
        }

        return "$course_title: $section_title";
    }

    /**
     *  Will make an idnumber based on certain rules.
     *
     *  @param $term The term.
     *  @param $srs The SRS.
     *  @param $master If the course is a master course.
     *  @return string The ID Number.
     **/
    function make_idnumber($term, $srs, $master=false) {
        if ($master) {
            return "$term-Master_$srs";
        }

        return "$term-$srs";
    }
    
    /**
     *  Recursively trim() fields.
     *  @param $obj The object to trim().
     *  @return StdClass The object, trimmed.
     **/
    function trim_object($oldobj) {
        $obj = array();
        foreach ($oldobj as $f => $v) {
            if (is_array($v)) {
                // Do nothing
            } else if (is_object($v)) {
                $obj[$f] = $this->trim_object($v);
            } else {
                $obj[$f] = trim($v);
            }
        }

        return $obj;
    }

    /**
     *  Create a Registrar connection object.
     *
     *  Stolen from enrol/database/lib.php:enrol_database_plugin.init_db()
     *  @return ADOConnection 
     */
    function open_registrar_connection() {
        global $CFG;

        require_once($CFG->libdir . '/adodb/adodb.inc.php');

        $dbtype = $this->get_config('registrar_dbtype');
        if ($dbtype == '') {
            throw new course_creator_exception('Registrar DB not set!');
        }

        // Manually coded check for odbc functionality, since moodle doesn't 
        // seem to like exceptions
        if (strpos($dbtype, 'odbc') !== false) {
            if (!function_exists('odbc_exec')) {
                $this->handle_locking(false);

                throw new Exception('FATAL ERROR: ODBC not installed!');
            }
        }

        // Connect to the external database 
        $extdb = ADONewConnection($dbtype);

        if (!$extdb) {
            throw new course_creator_exception(
                'Could not connect to registrar!'
            );
        }

        if ($this->get_debug()) {
            $extdb->debug = true;
        }

        // If the stored procedures are not working, uncomment this line
        //$extdb->curmode = SQL_CUR_USE_ODBC;

        $status = $extdb->Connect(
            $this->get_config('registrar_dbhost'), 
            $this->get_config('registrar_dbuser'), 
            $this->get_config('registrar_dbpass'),
            $this->get_config('registrar_dbname')
        );

        if ($status == false) {
            throw new course_creator_exception('Registrar connection failed!');
        }

        $extdb->SetFetchMode(ADODB_FETCH_ASSOC);

        return $extdb;
    }

    /**
     *  Check if the term is summer.
     *
     *  @param $term The term to check.
     *  @return boolean Is the term a summer term?
     **/
    function match_summer($term) {
        return preg_match('/1$/', $term);
    }

    /**
     *  Quick wrapper for @see strip_tags and @see trim.
     *
     *  @param string The string to trim and strip_tags.
     *  @return string The string, without HTML tags and with leading and 
     *      trailing spaces removed.
     **/
    function trim_strip_tags($string) {
        return trim(strip_tags($string), " \r\n\t");
    }

    /**
     *  Format certain names properly.
     *
     *  @todo CCLE-2541 Handle McDonalds !!! FILLET O FISH, etc.
     *  @param $name The name to format.
     *  @return string The name with guessed capitals.
     **/
    function format_name($name) {
        return ucwords(strtolower($name));
    }

    /**
     *  Format the term to look pretty.
     *
     *  @param $term The term.
     *  @return string The pretty term.
     **/
    function term_to_text($term) {
        $term_letter = substr($term, -1, 1);
        $years = substr($term, 0, 2);

        if ($term_letter == "F" || $term_letter == "f") {
            $termtext = "20" . $years . " Fall";
        } else if ($term_letter == "W" || $term_letter == "w") {
            // W -> Winter
            $termtext = "20" . $years . " Winter";
        } else if ($term_letter == "S" || $term_letter == "s") {
            // S -> Spring
            $termtext = "20" . $years . " Spring";
        } else {
            // 1 -> Summer
            $termtext = "20" . $years . " Summer Session " . $session;            
        }

        return $termtext;
    }
}

/** End of file **/
