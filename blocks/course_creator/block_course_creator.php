<?php 

class block_course_creator extends block_base {
    /** Stuff for Logging **/

    // This is the huge text that will email to admins.
    private $email_log = '';

    // Contains the log file pointer.
    private $log_fp;

    /** Variants for the cron **/
    // Terms to be used by course creator.
    private $terms_list = null;

    // Contains the requests for the current term
    private $term_requests;

    // Contains the registrar information for the current term
    private $term_rci;

    /** Non Variants **/

    // Contains the information regarding subject area long names.
    private $subj_trans;

    // Email parsing cache
    private $email_params = array();

    // Checks if metacourses are enabled
    static $metacourses_enabled = null;

    // Called every time this module is loaded
    function init() {
        $this->title = get_string('pluginname', 'block_course_creator');
        
        if (!class_exists('enrol_plugin')) {
            require($CFG->libdir . '/enrollib.php');
        }
    }

    // This function is called for each instance of this block.
    function specialization() {

    }

    // This is the course creator cron
    function cron() {
        global $CFG;

        // TODO Disable cron if wanted

        /** Check for proper configurations **/
        $db_unid = $CFG->dbname;

        $outpath = get_config('course_creator_outpath');
       
        // Pseudo-Lock this process
        $this->handle_locking(true);

        if (!class_exists('enrol_plugin')) {
            require($CFG->libdir . '/enrollib.php');
        }

        if (!class_exists('enrol_imsenterprise_plugin')) {
            $ims_plugin = $CFG->dirroot . '/enrol/imsenterprise/lib.php';
            
            if (file_exists($ims_plugin)) {
                require($ims_plugin);
            } else {
                // TODO Throw an error
                echo "Could not find $ims_plugin\n";
                die();
            }
        }

        // Check the IMS settings 
        $ims_settings = array();
        foreach ($ims_settings as $conf_check => $ims_check) {
            if (!get_config('', $conf_check)) {
               // Check the configurations 
            }
        }

        $shell_date = date('Y-m-d-G-i');

        $full_date = date('r');

        /** Run the course creator **/
        $termlist = $this->get_terms_creating();

        if (empty($termlist)) {
            // TODO Empty terms error handler
            $termlist[] = '10F';
        }

        // This uses the fact that $this is a stateful entity,
        // So becareful when calling functions.
        foreach ($termlist as $work_term) {
            $this->flush_term_cron();

            try {
                $this->retrieve_term_requests($work_term);
            } catch (Exception $e) {
                // TODO
                $this->debugln('');
                continue;
            }
        }
    }

    /** ****************** **/
    /*  Unique Functions    */
    /** ****************** **/
    
    /**
     *  Will print to the designated course creator log.
     *  @param $mesg The message to print.
     **/
    function println($mesg) {
        // TODO
        echo $mesg . "\n";
    }

    /**
     *  Will output to the email log.
     *  @param $mesg The message to output.
     **/
    function emailln($mesg) {
        // TODO
        $this->email_log = $this->email_log . $mesg . "\n";
    }
    
    /**
     *  Shortcut function to print to both the log and the email.
     *  @param $mesg The message to print to log and email.
     **/
    function debugln($mesg) {
        $this->println($mesg);
        $this->emailln($mesg);
    }

    /** ************************ **/
    /*  Accessor Functions        */
    /** ************************ **/

    /**
     *  Returns the long name of the subject area if found, otherwise just the short name.
     *
     *  Will alter the state of the object.
     *
     *  @param $subjarea The short name of the subject area.
     *  @return The long name of the subject area, or the short name if no long name was found.
     **/
    function get_subject_area_translation($subjarea, $default=false) {
        if (!isset($this->subj_trans) || $this->subj_trans == null) {
            $subjareas = get_records('ucla_reg_subjectarea');

            // TODO Index this?

            $this->subj_trans = $subjareas;
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
        // TODO Use Moodle's debugging
        if (!isset($this->debugmode)) {
            // TODO figure out whether to enable debugging or not
            return true;
        }

        return true;
    }

    /**
     *  Returns an Array of terms to work for. If {@link set_terms} is used, then it will
     *  return whatever has been set already.
     *
     *  Wrapper for {@link figure_terms}.
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
     *  Returns the ADOConnection object for registrar connection.
     *
     *  Wrapper for {@link open_registrar_connection}.
     *  
     *  May change state of object.
     *
     *  @return ADOConnection The connection to the registrar.
     **/
    function get_registrar_connection() {
        if (!isset($this->registrar_conn)) {
            $this->registrar_conn = $this->open_registrar_connection();
        }

        return $this->registrar_conn;
    }

    /**
     *  Closes the ADOConnection object for Registrar connection.
     *
     *  May change the state of object.
     **/
    function close_registrar_connection() {
        if (!isset($this->registrar_conn)) {
            return false;
        }

        $this->registrar_conn->Close();

        unset($this->registar_conn);

        return true;
    }

    /** ************************ **/
    /*  Cron-Specific Functions   */
    /** ************************ **/

    /**
     *  Returns the course requests for a particular term. 
     *  Also maintains the crosslisted relationships.
     *  
     *  Will alter the state of the object.
     *
     *  @param $term The term that is desired.
     **/
    function retrieve_term_requests($term) {
        // TODO use a global term function
        if ($term == '') {
            return FALSE;
        }

        global $DB;

        $sql_params = array($term);

        $sql_where = "
            action LIKE '%uild'
                AND
            term = ?
                AND
            status = 'processing'
        ";

        // These are the regular and host courses
        $course_set = $DB->get_records_select('ucla_request_classes', $sql_where, $sql_params);

        if (empty($course_set)) {
            $mesg = "No courses for $term.";
            $this->debugln($mesg);

            return true;
        }

        // Maintain the crosslists that have been requested for later
        $crosslisted_courses = array();

        // Figure out crosslists and filter out faulty requests
        foreach ($course_set as $key => $course_request) {
            $srs = trim($course_request->srs);

            if (strlen($srs) != 9) {
                $this->debugln('Faulty SRS: ' . $course_request->course);
                unset($line);

                unset($course_set[$key]);
                continue;
            }

            if ($course_request->crosslist == '1') {
                if (!$this->validate_metacourses()) {
                    // TODO
                    throw new Exception('Meta required!');
                }

                $crosslisted_courses[$key] = $srs;

                $course_set[$key]->crosslisted = array();
            }
        }
    
        if (empty($crosslisted_courses)) {
            $this->insert_term_requests($course_set);

            return true;
        }

        // Select * from {ucla_request_crosslist} ...
        list($sql_in, $params) = $DB->get_in_or_equal($crosslisted_courses);

        $sql_where = "
            srs $sql_in
                AND
            term = ?
        ";

        $sql_params = $params;
        $sql_params[] = $term;

        $crosslisted_requests = $DB->get_records_select('ucla_request_crosslist', $sql_where, $sql_params);

        // Build the crosslisted set of courses
        foreach ($crosslisted_requests as $crosslisted_request) {
            $srs = trim($crosslisted_request->srs);

            if (isset($course_set[$srs])) {
                $crosslisted_srs = trim($crosslisted_request->aliassrs);
    
                // Attach the crosslisted course to the host course
                $course_set[$srs]->crosslisted[$crosslisted_srs] = $crosslisted_request;
            } else {
                // TODO
                throw new Exception('Could not find host course');
            }
        }

        $this->insert_term_requests($course_set);

        return true;
    }

    /**
     *  Formats and inserts the data into our object.
     *
     *  Changes the state of the object.
     *
     *  @param The set of requested courses, with crosslisted hierarchy.
     **/
    function insert_term_requests($courses) {
        foreach ($courses as $course) {
            $this->trim_object($course);

            // Log this data and figure it out
            $this->term_requests[$course->srs] = $course;
        }
    }

    /**
     *  Will take the contents of $this->term_requests and retrieve their corresponding
     *  ccle_getClasses entries.
     *
     *  Will change the state of the object.
     **/
    function retrieve_registrar_info() {
        if (!isset($this->term_requests) || empty($this->term_requests)) {
            return false;
        }

        $db_reg = $this->get_registrar_connection();

        foreach ($this->term_requests as $req_course) {
            $srs = $req_course->srs;
            $term = $req_course->term;

            $recset = $db_reg->Execute("EXECUTE ccle_getClasses '$term' '$srs'");

            if (!$recset->EOF) {
                while ($fields = $recset->FetchRow()) {
                    // TODO In the external db enrollment plugin they have encoding detection code

                    try {
                        $this->insert_registrar_entry($fields, $req_course);
                    } catch (Exception $e) {
                        $this->debugln($e->getMessage());

                        continue;
                    }
                }
            } else {
                throw new Exception("No Registrar Course $term $srs");
            }

            $recset->Close();

            // Get the crosslisted courses RCI
            if (isset($req_course->crosslisted) && !empty($req_course->crosslisted)) {
                $cl_req_course = clone $req_course;
                $cl_req_course->srs = $cl_srs;

                unset($cl_req_course->crosslisted);

                foreach ($req_course->crosslisted as $cl_srs) {
                    $cl_recset = $db_req->Execute("EXECUTE ccle_getClasses '$term' '$cl_srs'");

                    if (!$cl_recset->EOF) {
                        while ($cl_fields = $cl_recset->FetchRow()) {
                            try {
                                $this->insert_registrar_entry($fields, $cl_req_course);
                            } catch (Exception $e) {
                                // TODO Actually we might want to handle this differently

                                throw new Exception('Crosslisted failed with ' . $e->getMessage());
                            }
                        }
                    } else {
                        throw new Exception("No Registrar Course $term $cl_srs");
                    }

                    $cl_recset->Close();
                }
            }
        }

        $this->close_registrar_connection();

        return true;
    }

    /**
     *  Validates the entry and then inserts the data into the object.
     *
     *  Will change the state of the object.
     *
     *  @param $entry The information from the Registrar.
     *  @param $original The corresponding request entry.
     **/
    function insert_registrar_entry($entry, $original) {
        $entry = array_change_key_case($fields, CASE_LOWER);

        if (!isset($entry['srs']) || $entry['srs'] != $original->srs) {
            throw new Exception('Incorrect SRS from Registrar ' . $original->srs);   
        }

        if (!isset($entry['term']) || $entry['term'] != $original->term) {
            throw new Exception('Incorrect term from Registrar ' . $original->term);
        }

        $entry_object = (object) $entry; 

        $rci_srs = $entry_object->srs;

        if (isset($this->term_rci[$rci_srs])) {
            throw new Exception('Repeated SRS ' . $rci_srs);
        }

        // TODO Log this data

        $this->term_rci[$rci_srs] = $entry_object;
    }

    /**
     *  Generates IMS file for a term.
     *  
     **/
    function generate_ims_entries() {
        if (!isset($this->term_rci) && empty($this->term_rci)) {
            return false;
        }

        $ims_lines = array();

        foreach ($this->term_rci as $rci_object) {
            unset($req_course);

            $rci_srs = trim($rci_object->srs);

            if (!isset($this->term_requests[$rci_srs])) {
                // This is a crosslisted course
                $req_course->visible = 0;
            } else {
                $req_course = $this->term_requests[$rci_srs];
            }

            $rci_type = rtrim($rci_object->acttype);
            $rci_sess = rtrim($rci_object->session_group);

            $rci_desc = $rci_object->crs_desc;

            $subj = rtrim($rci_object->subj_area);
            $rci_num  = rtrim($rci_object->coursenum);
            $rci_sect = rtrim($rci_object->sectnum);

            $rci_course = $this->make_course_name($subj, $rci_num, $rci_sect);

            $rci_visible = $req_course->visible;

            $rci_title = $this->make_course_title(
                trim($rci_object->coursetitle), 
                trim($rci_object->sectiontitle)
            );

            $rci_subj = $this->get_subject_area_translation($subj);

            // Not elegant
            if (isset($req_course->crosslisted) && !empty($req_course->crosslisted)) {
                $ims_lines[] = course_IMS($rci_title, build_idnumber($term, $rci_srs, TRUE), 
                    $rci_sess, $rci_desc, $rci_course, $term,
                    $rci_subj, 1);


                $rci_course = $rci_course . 'c';
            }
            
            $ims_lines[] = course_IMS($rci_title, build_idnumber($term, $rci_srs), 
                    $rci_sess, $rci_desc, $rci_course, $term,
                    $rci_subj, $rci_visible);

            unset($req_course);
        }

        foreach ($ims_lines as $ims_line) {
            fwrite($ims_fp, $ims_line);
        }
    }

    /**
     *  Will remove all previously set term information.
     *
     *  Will change the state of the object.
     **/
    function flush_term_cron() {
        unset($this->term_requests);
        $this->term_requests = array();

        unset($this->term_rci);
        $this->term_rci = array();
    }

    /**
     *  Will determine whether or not we can run this function.
     *  @param $lock true for lock, false for unlock.
     *  @return boolean If we the action was successful or not.
     *  @since Moodle 2.0.
     **/
    function handle_locking($lock) {
        // TODO prevent more than one instance of this cron from running

        // Prevent new requests that come in during course creation from affecting 
        // course creator
        $sql_where = "
            action LIKE '%uild'
                AND
            status <> 'done'
        ";

        if (!$this->get_debug()) {
            $DB->set_field_select('ucla_request_classes', 'status', 'processing', $sql_where);
        }

        return true;
    }

    /**
     *  Mails the requestors.
     *
     **/
    function mail_requestors() {
        // TODO
    }

    /**
     *  Temporary wrapper for finishing up cron.
     *  Email admin.
     *  Cleanup certain things?
     *  May not be necessary.
     **/
    function finish_cron() {
        // TODO email admin
        // TODO close open databases
        // TODO close open files
    }

    /** ****************** **/
    /*  External Modifiers  */
    /** ****************** **/

    /**
     *  Sets the terms to be run.
     *
     *  Changes the state of the function.
     *
     *  @param $terms_list The array of terms to run for.
     **/
    function set_terms($terms_list) {
        if ($terms_list != null && !empty($terms_list)) {
            $this->terms_list = $terms_list;
        }
    }

    /** *************************** **/
    /*  Non-Variants Initializers    */
    /** *************************** **/

    /**
     *  Will figure out the terms to work for.
     *  Currently only uses the config file as a source.
     *  TODO Should check in the configuration what term to use to build.
     *
     *  Will change the state of the object.
     *
     **/
    function figure_terms() {
        if (isset($CFG->course_creator_terms)) {
            $terms_list = explode(' ', $CFG->course_creator_terms);
        }

        if (isset($terms_list)) {
            foreach ($terms_list as $term) {
                if (!$this->validate_term($term)) {
                    throw new Exception('Improper term ' . $term);
                }
            }

            $this->terms_list = $terms_list;

            return $terms_list;
        }

        return false;
    }

    /**
     *  Will figure out the defaults to use when setting up courses.
     *
     *  Will change the state of the object.
     **/
    function figure_course_defaults() {
        // TODO
        // get_config('moodlecourse')
        // default_format
        // default_theme
        // child_format = 'uclaredir'
    }

    /**
     *  Will figure out what to interpret as the webpage.
     *
     *  Will change the state of the object.
     **/
    function figure_url() {
        // TODO
        // url_field = 'id' | 'shortname'
    }

    /**
     * Will figure out MyUCLA URL update non-variants.
     *
     * Will change the state of the object.
     **/
    function figure_myucla_url_login() {
        // TODO
    }

    /**
     *  Will figure out the email non-variants.
     *
     *  Will change the state of the object.
     **/
    function figure_email() {
        // TODO
        // course_creator_mailheaders
        // 'The following courses are now available at' . $CFG->wwwroot . "/\n\n";
        // "\n\n\n" . course_creator_contact
    }

    /** ************************ **/
    /*  Global Function Wrappers  */
    /** ************************ **/

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
        $course = $subj . $coursenum . '-' . $coursesect;
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
    function make_idnumber($term, $srs, $master) {
        if ($master) {
            return "$term-Master_$srs";
        }

        return "$term-$srs";
    }
    
    /**
     *  Will validate that metacourses have been enabled for this instance.
     *  @return boolean Whether metacourses can be utilized on this site.
     **/
    function validate_metacourses() {
        if ($this->metacourses_enabled === true) {
            return true;
        }

        $this->metacourses_enabled = enrol_is_enabled('enrol_meta');

        return $this->metacourses_enabled;
    }

    /**
     *  Recursively trim() fields.
     *  @param $obj The object to trim().
     *  @return Object The object, trimmed.
     **/
    function trim_object($obj) {
        foreach ($obj as $f => $v) {
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
     */
    function open_registrar_connection() {
        global $CFG;

        require_once($CFG->libdir . '/adodb/adodb.inc.php');

        // Connect to the external database (forcing new connection)
        $extdb = ADONewConnection($this->get_config('registrar_dbtype'));

        if ($this->get_config('debugdb')) {
            $extdb->debug = true;
            ob_start(); //start output buffer to allow later use of the page headers
        }

        $extdb->Connect(
            $this->get_config('registrar_dbhost'), 
            $this->get_config('registar_dbuser'), 
            $this->get_config('registrar_dbpass')
        );

        $extdb->SetFetchMode(ADODB_FETCH_ASSOC);

        return $extdb;
    }
}

/** End of file **/
