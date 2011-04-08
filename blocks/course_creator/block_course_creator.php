<?php 

class block_course_creator extends block_base {
    /** Stuff for Logging **/

    // This is the huge text that will email to people
    private $email_log = '';

    // Contains the log file pointer
    private $log_fp;

    /** Variants for the cron **/
    // Terms to be used by course creator
    private $terms_list = null;

    // Contains information for the current term course creator is running for.
    private $term_session;

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

        /** Check for proper configurations **/
        $db_unid = $CFG->dbname;

        $outpath = get_config('course_creator_outpath');
        
        // TODO Locking
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
            $this->get_term_requests($work_term);
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
        echo $mesg;
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

    /** ************************ **/
    /*  Cron-Specific Functions   */
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
            $subjarea_query = "
                SELECT
                    subj_area_full,
                    subjarea
                FROM {ucla_reg_subjectarea}
            ";
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
     *  @return Array of terms ([0-9]{2}[1WSF])
     **/
    function get_terms_creating() {
        if (!isset($this->terms_list)) {
            $this->figure_terms();
        }

        return $this->terms_list;
    }

    /**
     *  Returns the course requests for a particular term.
     *  
     *  Will alter the state of the object.
     *
     *  @param $term The term that is desired.
     *  @return Array The crosslisted courses reference.
     **/
    function get_term_requests($term) {
        if ($term == '') {
            return FALSE;
        }

        global $DB;

        $debugmode = $this->get_debug();

        $sql_conditions = array(
            'action' => '%uild',
            'term' => $term
        );
        
        $where_start = "
            WHERE
                action LIKE '%uild'
                AND
                term = ?
            ";

        $where_not_done = "
                AND
                status <> 'done'
            ";

        $where_processing = "
                AND
                status = 'processing'
            ";

        $order_by = "
            ORDER BY course
            ";

        $req_sql = $select;

        if ($debugmode) {
            $req_sql .= $where_start . $where_not_done . $order_by;
        } else {
            
            $DB->execute("
                UPDATE {ucla_request_classes}
                SET
                    status = 'processing'
                " . $where_start . $where_not_done, $pdobj);

            $req_sql .= $where_start . $where_processing . $order_by;
        }

        // These are the regular and host courses
        $course_set = $DB->get_records_sql($req_sql, $pdobj);

        // Maintain the crosslists that have been requested for later
        $crosslisted_courses = array();

        // Figure out crosslists and filter out faulty requests
        foreach ($course_set as $key => $course_request) {
            $srs = $course_request->srs;

            if (strlen($srs) != 9) {
                
                $line = 'Faulty SRS: ' . $course_request->course;
                $this->println($line);
                $this->emailln($line);

                unset($line);
                unset($course_set[$key]);

                continue;
            }

            if ($course_request->crosslist == '1') {
                if (!$this->validate_metacourses()) {

                    $mesg = 'Need metacourse functionality enabled.';
                    $this->println($mesg);
                    $this->emailln($mesg);

                    $this->finish_cron();
                    die();
                }

                $crosslisted_courses[$key] = $course_request;

                $course_set[$key]->crosslisted = array();
            }
        }

        $this->term_session = $course_set;
    
        if (empty($crosslisted_courses)) {
            return true;
        }

        $alias_sql = "
            SELECT
                DISTINCT
                    RTRIM(aliassrs) AS aliassrs,
                    RTRIM(srs) AS srs
            FROM {ucla_request_crosslist}
            WHERE
                srs IN ?
                AND term = ?
            ";

        foreach ($crosslisted_courses as $host_key => $course_request) {
            
        }
    }

    /**
     *  Will remove all previously set term information.
     *
     *  Will change the state of the object.
     **/
    function flush_term_session() {
        $this->term_session = array();
    }

    /**
     *  Will determine whether or not we can run this function.
     *  @param $lock true for lock, false for unlock.
     *  @return boolean If we the action was successful or not.
     *  @since Moodle 2.0.
     **/
    function handle_locking($lock) {
        // TODO
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
        // TODO
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
}

/** End of file **/
