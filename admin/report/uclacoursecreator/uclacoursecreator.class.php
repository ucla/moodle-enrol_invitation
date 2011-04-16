<?php 

// @todo move to some other file
class CourseCreatorException extends Exception {
    // Nothing...
}

/**
 *  Course creator.
 *
 *  @todo Move to admin/report/
 *  @todo Include capability check.
 **/
class uclacoursecreator {
    /** Stuff for Logging **/
    // This is the huge text that will email to admins.
    public $email_log = '';

    // Contains the log file pointer.
    private $log_fp;

    // The path for the output files as parsed from the configuration, 
    // or defaulting to dataroot.
    public $output_path;

    /** Variants for the cron **/
    // Terms to be used by course creator.
    private $terms_list = null;

    // The current term we are working for
    private $cron_term;

    // Contains all the information for a current term
    private $cron_term_cache;

    /** Non Variants **/
    // Contains the information regarding subject area long names.
    private $subj_trans;

    // Email parsing cache
    private $email_params = array();

    // Contains the root path to the MyUCLA URL update webservice.
    private $myucla_login = null;

    // This is the course creator cron
    function cron() {
        global $CFG;

        // @todo Disable cron if wanted

        /** Check for proper configurations **/
        $db_unid = $CFG->dbname;

        $outpath = get_config('course_creator_outpath');

        if (!class_exists('enrol_plugin')) {
            require($CFG->libdir . '/enrollib.php');
        }

        $this->get_enrol_plugin('imsenterprise');
        $this->get_enrol_plugin('meta');
        
        try {
            // We cannot write, we cannot lock
            $this->check_write();
       
            // Pseudo-Lock this process
            $this->handle_locking(true);
        } catch (CourseCreatorException $e) {
            $this->debugln($e->getMessage());

            $this->finish_cron();
            return false;
        }

        $this->shell_date = date('Y-m-d-G-i');
        $this->full_date = date('r');

        /** Run the course creator **/
        $termlist = $this->get_terms_creating();

        if (empty($termlist)) {
            // @todo Empty terms error handler
            $termlist[] = '10F';
        }

        // Wrong comments are worse than no comments.
        $reg_callback_object = new StdClass();
        $reg_callback_object->term_table = 'term_rci';
        $reg_callback_object->key_field = 'srs';

        // This is for the sake of coding.
        $this->reg_callback_object = $reg_callback_object;

        // This uses the fact that $this is a stateful entity,
        // So becareful when calling functions.
        foreach ($termlist as $work_term) {
            try {
                $this->start_cron_term($work_term);

                // Properly generate the names of the IMS files
                $this->prepare_ims_files();

                // Get stuff from course requestor
                $retrieved = $this->retrieve_requests();

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

                    // Send emails to the instructors and the course 
                    // requestors
                    $this->send_emails();
                }

                if ($this->get_debug()) {
                    // Quit early
                    throw new CourseCreatorException('Debugging enabled!');
                }

                $this->mark_cron_term(true);
            } catch (Exception $e) {
                $this->debugln($e->getMessage());
            
                // Since the things were not processed, try to revert the
                // changes in the requestor
                $this->mark_cron_term(false);
            }
        }

        $this->handle_locking(false);

        $this->finish_cron();
    }

    /** ******************* **/
    /*  Debugging Functions  */
    /** ******************* **/
    
    /**
     *  Will print to the designated course creator log.
     *  @param $mesg The message to print.
     **/
    function println($mesg) {
        // @todo
        echo $mesg . "\n";
    }

    /**
     *  Will output to the email log.
     *  @param $mesg The message to output.
     **/
    function emailln($mesg) {
        // @todo
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
        if (!isset($this->subj_trans) || $this->subj_trans == null) {
            $subjareas = get_records('ucla_reg_subjectarea');

            // @todo Index this, most likely

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
        // @todo Use Moodle's debugging
        if (!isset($this->debugmode)) {
            // @todo figure out whether to enable debugging or not
            return true;
        }

        return true;
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
     *  Returns an Array of terms to work for. If {@see set_terms()} 
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
     *  Returns the ADOConnection object for registrar connection.
     *
     *  Wrapper for @see open_registrar_connection().
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
                throw new CourseCreatorException(
                    'Missing ' . $plugin . ' plugin in code.'
                );
            }
        }

        if (!enrol_is_enabled($plugin)) {
            throw new CourseCreatorException(
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
        $ims_plugin = $this->enrol_imsenterprise_plugin;

        // Check the IMS settings 
        $ims_settings = array();
        $ims_settings['createnewcourses'] = true;

        foreach ($ims_settings as $conf_check => $ims_fail) {
            if (!$ims_plugin->get_config($conf_check)) {
                // Check the configurations 
                if ($ims_fail) {
                    throw new CourseCreatorException(
                        "Required IMS setting $conf_check disabled!"
                    );
                }

                $this->debugln("IMS setting $conf_check disabled.");
            }
        }
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
            throw new CourseCreatorException('IMS file created already ' 
                . $this->cron_term_cache['ims_path'] 
                . ', without flushing term cache!');
        }

        $term = $this->get_cron_term();
        if (!$term) {
            throw new CourseCreatorException(
                'No term set, trying to create IMS files.'
            );
        }
        
        // Hmmm... We should already have done write checks.
        $ims_file = $this->output_path . '/integration.'
            . $this->shell_date . ".$term.xml";

        $this->cron_term_cache['ims_path'] = $ims_file;

        $ims_log = $this->output_path . '/ims.' . $this->shell_date 
            . ".$term.log";

        $this->cron_term_cache['ims_log'] = $ims_log;
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

        $this->prepare_ims_files();

        if (!isset($this->cron_term_cache['ims_path'])) {
            throw new Exception('ims_path not set');
        }

        $ims_file = $this->cron_term_cache['ims_path'];
        $fp = fopen($ims_file, 'x');
        
        if (!$fp) {
            // This means that there is an IMS file open, but 
            // that we do not have a file pointer for this file. 
            // That means that:
            // 1. Another process has somehow generated this IMS file.
            // We already should have tested for write permissions.
            // We know that there is logic to prevent the same 
            // term to be worked on.
            throw new CourseCreatorException('IMS file ' . $ims_file 
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
        // @todo
        // get_config('moodlecourse')
        // default_format
        // default_theme
        // child_format = 'uclaredir'
        $term = $this->get_cron_term();

        if (!$term) {
            throw new CourseCreatorException();
        }

        if (isset($this->cron_term_cache['defaults'])) {
            return $this->cron_term_cache['defaults'];
        }

        if (!isset($this->course_defaults)) {
            $this->course_defaults = get_config('moodlecourse');
        }
        
        if ($this->match_summer($term)) {
            // @todo make this configurable
            $numsections = 6;
        } else {
            $numsections = $this->course_defaults->numsections;
        }

        $defredir = 'uclaredir';
        $defformat = $this->course_defualts->format;
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

            $cc_name = $this->get_config('course_creator_name');
            $cc_email = $this->get_config('course_creator_email');

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
        // @todo Use capabilities
        return true;
    }

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
        if (!isset($this->registrar_conn)) {
            return false;
        }

        $this->registrar_conn->Close();

        unset($this->registar_conn);

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
            throw new CourseCreatorException(
                'Could not set the term [' . $term . ']'
            );
        }
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
        if (!$this->get_cron_term()) {
            return false;
        }

        // @todo mark stuff either as finished or unfinished in ucla_request_classes.

        if (!$done) {
            $this->revert_cron_term();
        }

        return true;
    }

    /**
     *  This will attempt to undo all the changes in cron_term_cache.
     *  It should also mark all the requests that are processing as 
     *  pending.
     *      
     **/
     function revert_cron_term() {
        global $DB;

        $this->println("Reverting data for " . $this->get_cron_term() 
            . '...');

        if (isset($this->cron_term_cache['requests'])) {
            $ctcr = $this->cron_term_cache['requests'];

            $ids = array();
            foreach ($ctcr as $req) {
                $ids[] = $req->id;
            }

            list($sql_where, $params) = 
                $DB->get_in_or_equal($ids);

            $sql_where = 'id ' . $sql_where;
        
            $DB->set_field_select('ucla_request_classes', 'status', 
                'reverted', $sql_where, $params);
        }
     }

    /** ****************** **/
    /*  Cron Functionality  */
    /** ****************** **/

    /**
     *  Returns the course requests for a particular term. 
     *  Also maintains the crosslisted relationships.
     *  
     *  Will alter the state of the object.
     *
     *  @param $term The term that is desired.
     **/
    function retrieve_requests() {
        global $DB;

        $term = $this->get_cron_term();
        if (!$term) {
            throw new CourseCreatorException('term not set properly');
        }

        $sql_params = array($term);

        $sql_where = "
            action LIKE '%uild'
                AND
            term = ?
                AND
            status = 'processing'
        ";

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
            throw new CourseCreatorException(
                $srs . ' is crosslisted but no ucla_request_crosslist found!'
            );
        }

        // Build the crosslisted set of courses
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
                throw new CourseCreatorException(
                    'Could not find host course ' . $srs
                    . ' dump: ' . $debug
                );
            }
        }

        $this->insert_requests($course_set);

        return true;
    }

    /**
     *  Formats and inserts the data into our object.
     *
     *  Changes the state of the object.
     *
     *  @param The set of requested courses, with crosslisted hierarchy.
     **/
    function insert_requests($courses) {
        foreach ($courses as $course) {
            $this->trim_object($course);

            // @todo Log this data

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

        // Set default arguments... I have no idea.
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
                    throw new CourseCreatorException(
                        'You need property "function" in  ' 
                        . $func_obj_name
                    );
                }

                // Validate that the function exists
                if (!method_exists($this, $func_obj->function)) {
                    throw new CourseCreatorException(
                        'Sorry, ' . get_class($this) 
                        . ' does not have method ' . ${$func}
                    );
                }
            } else {
                // We can force certain arguments to not be null
                if ($default == null) {
                    throw new CourseCreatorException(
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
            throw new CourseCreatorException(
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

            if (!$recset->EOF) {
                while ($fields = $recset->FetchRow()) {
                    // @todo In the external db enrollment plugin they 
                    // have encoding detection code

                    try {
                        // Handle each row as specified in the 
                        // response function
                        $this->insert_local_entry($fields, $drive_course,
                            $response_func_args);
                    } catch (CourseCreatorException $e) {
                        $this->debugln($e->getMessage());
                        continue;
                    }
                }
            } else {
                throw new CourseCreatorException("$qr returned 0 rows");
            }

            $recset->Close();

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
            throw new CourseCreatorException(
                'sp_term_srs missing srs or term'
            );
        }

        $srs = $entry->srs;
        $term = $entry->term;

        return "EXECUTE $sp '$term' '$srs' ";
    }

    /**
     *  Handles the crosslists for things.
     *
     *  Used as a callback for {@see retrieve_registrar_info()}.
     *
     *  @param $entry 
     *      The entry that has $entry.crosslisted = Array, and
     *      each $entry.crosslisted has term and srs.
     **/
    function retrieve_registrar_crosslists($entry) {
        // I don't think I need to check for bad data...
        if (isset($entry->crosslisted) 
          && !empty($entry->crosslisted)) {

            $e_srs = $entry->srs;
            $ctc_key = 'reg_cls_' . $e_srs;
            $this->cron_term_cache[$ctc_key] = array(); 

            $prototype = clone $entry;
            unset($prototype->crosslisted);

            foreach ($entry->crosslisted as $cl_srs) {
                $cl_entry = clone $prototype;
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

        if (isset($args->test_func)) {
            if (method_exists($this, $args->test_func)) {
                $result = $this->{$args->test_func}($entry);

                if (!$result) {
                    // Eh, do nothing... effin' hell...
                    return;
                }
            }
        }

        // Make sure some basics exist
        foreach ($tests as $test => $o_test) {
            if (!isset($entry[$test]) 
              || $entry[$test] != $original->$o_test) {
                throw new CourseCreatorException(
                    'Incorrect ' . $test . ' from Registrar ' 
                        . $original->$o_test
                );
            }
        }

        $entry_object = (object) $entry; 

        if ($args != null && isset($args->key_field) 
          && method_exists($this, $args->key_field)) {
            // This function should not be oriented this way,
            // but currently, I am way too lazy...
            $this->{$args->key_field}($entry_object);

            // We can just return
            return true;
        } 

        // Insert with just the srs as the key
        $key = $entry_object->srs;
        if (isset($this->cron_term_cache['term_rci'][$key])) {
            throw new CourseCreatorException(
                'Repeated table term_rci key ' . $key
            );
        }

        $this->cron_term_cache['term_rci'][$key] = $entry_object;
    }

    /**
     *  Inserts the instructor into our local Arrays.
     *  @todo duplicate handling?
     *
     *  Used as a callback in {@see insert_local_entry()}.
     *
     *  Will modify the state of the object.
     *
     *  @param $entry The entry from the Registrar.
     *  @return string The Array key.
     **/
    function key_field_instructors($entry) {
        // Save the instructor indexed by UID
        $this->cron_term_cache['instructors'][$entry->srs][$entry->ucla_id] = 
            $entry;

        // Save the profcodes of the course
        $this->cron_term_cache['profcodes'][$entry->srs][$entry->profcode] =
            $entry->profcode;
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
            if (empty($data)) {
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

            $ims_srs = trim($rci_object->srs);

            // See if we can get certain information from the requests
            if (!isset($this->cron_term_cache['requests'][$ims_srs])) {
                // This is a crosslisted child course
                $req_course->visible = 0;
            } else {
                $req_course = 
                    $this->cron_term_cache['requests'][$ims_srs];
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

            $ims_visible = $req_course->visible;

            $ims_title = $this->make_course_title(
                trim($rci_object->coursetitle), 
                trim($rci_object->sectiontitle)
            );

            // Get the long version of the subject area (for category)
            $ims_subj = $this->get_subject_area_translation($subj);

            // This means that we have to build a master course
            if (isset($req_course->crosslisted) 
              && !empty($req_course->crosslisted)) {
                $ims_lines[] = $this->course_IMS($ims_title, 
                    build_idnumber($term, $ims_srs, TRUE), 
                    $ims_sess, $ims_desc, $ims_course, $term,
                    $ims_subj, 1);

                $ims_course = $ims_course . 'c';
            }
           
            // Make the child course or the regular course 
            $ims_lines[] = $this->course_IMS($ims_title, 
                build_idnumber($term, $ims_srs), 
                $ims_sess, $ims_desc, $ims_course, $term,
                $ims_subj, $ims_visible);
        }

        // Write the IMS file
        foreach ($ims_lines as $ims_line) {
            fwrite($ims_fp, $ims_line);
        }

        // Close the damn file
        fclose($ims_fp);
    }

    /**
     *  Run the IMS import.
     *  Wrapper for {@see enrol_imsenterprise_plugin()}.
     **/
    function ims_cron() {
        if (!class_exists('enrol_imsenterprise_plugin')) {
            throw new CourseCreatorException(
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

        $ims_enrol->cron();

        // Let's run this check in here
        $this->activate_courses();
    }

    /**
     *  Checks to make sure that the expected courses were created via IMS.
     *  Also puts the created courses into the cache of the object.
     *
     *  Will change the state of the object.
     **/
    function activate_courses() {
        global $DB;

        // We run through this to make sure we have built all predicted 
        // idnumbers
        $check_srs = array();

        // Local reference hierarched requests
        $ctc_tr = $this->cron_term_cache['requests'];

        // Local reference for RCI
        $rci_courses = $this->cron_term_cache['term_rci'];

        // Foreach course we got courseInfo for, we are going to make sure
        // They are in the courses table
        foreach ($rci_courses as $rci) {
            $srs = $rci->srs;
            $term = $rci->term;

            $idnumber = $this->make_idnumber($term, $srs);
            $check_srs[$idnumber] = $srs;

            $srs_to_idnumber[$srs] = $idnumber;

            $request = $ctc_tr[$srs];
            if (isset($request->crosslisted) 
              && !empty($request->crosslisted)) {
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
                throw new CourseCreatorException(
                    'IMS did not build: ' . $idnumber
                );
            }

            if (!isset($ctc_tr[$srs])) {
                // This should also never happen
                throw new CourseCreatorException(
                    'Entry in RCI not requested: ' . $srs
                );
            }
        }

        // From here, we can revert things, so we want to store things
        // in the cron_term_cache, indexed by idnumber.
        $this->cron_term_cache['created_courses'] = $created_courses_check;

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
        foreach ($ctc_tr as $request) {
            $parent = $request->srs;

            // Get the parent course id
            $master = build_idnumber($term, $parent, TRUE);
            $master_course = $created_courses_check[$master];

            $mcid = $master_course->id;
            $mshn = $master_course->shortname;

            // Sort into three groups, child, meta or regular
            if (isset($request->crosslisted) 
              && !empty($request->crosslisted)) {
                $child_srs = $request->crosslisted;
                $child_srs[$parent] = $parent;

                foreach ($child_srs as $child => $not_used) {
                    $cidn = build_idnumber($term, $child);

                    $child_course = $created_courses_check[$cidn];
                    $cid = $child_course->id;

                    $this->cron_term_cache['activate']['child'][$cid] = 
                        $cid;

                    $crli_cid_summary[$master][$cid] = TRUE;
                }
        
                $this->cron_term_cache['activate']['meta'][$mcid] = $mcid;
            } else {
                $this->cron_term_cache['activate']['regular'][$parent] 
                    = $parent;
            }
        }

        // Coding check
        foreach ($this->cron_term_cache['activate'] as $type => $nothing) {
            if (!in_array($type, $course_types)) {
                throw new CourseCreatorException(
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
                $enrol_id = $meta->add_instance($parent_course, 
                    array('customint1' => $child));
            }

            // Save this so that we can revert if wanted
            $this->cron_term_cache['meta_sets'][] = $master;

            // Make sure the enrolments sync? I dunno, this probably 
            // is not that very important, since there probably are no
            // roles to sync in a newly created course
            //enrol_meta_sync($parent_course->id);
        }

        // @todo We might need to add a self-check to validate that
        // meta courses were added properly

        // Moodle 2 does not have something amazing to do many uploads, but
        // they do have this unimplemented $bulk argument
        $start_time = time();

        foreach ($this->cron_term_cache['activate'] 
                as $course_type => $courses) {

            $course_default = clone $defaults[$course_type];

            foreach ($courses as $course_id) {
                $course_default->id = $course_id;
                
                // Might not be the best methodology, since this is
                // a fairly bulk operation, while update_course is not
                // very bulk.
                //update_course($course_default);

                // This uses the $bulk argument, but for mysql it does not
                // do anything special
                $DB->update_record('course', $course_default, true);
            }
        }
    }

    /**
     *  Sends the URLs of the courses to MyUCLA.
     *
     **/
    function update_MyUCLA_urls() {
        if (!isset($this->cron_term_cache['activate'])) {
            throw new CourseCreatorException(
                'No records for activated courses.'
            );
        }

        if (!isset($this->cron_term_cache['created_courses'])) {
            throw new CourseCreatorException(
                'IMS did not seem to create any courses'
            );
        }
        
        // Figure out what to build as the URL of the course
        $relevant_url_info = array();

        $created = $this->cron_term_cache['created_courses'];
        $requests = $this->cron_term_cache['requests'];

        // For each requested course, figure out the URL
        foreach ($requests as $request) {
            unset($idnumber);

            $srs = $request->srs;
            $term = $request->term;
            
            $crc = false;
            if (isset($request->crosslisted) 
              && !empty($request->crosslisted)) {
                $idnumber = $this->build_idnumber($srs, $term, true);
                $crc = true;
            } else {
                $idnumber = $this->build_idnumber($srs, $term);
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

                if ($this->get_debug()) {
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

                    // @todo enable this when ready to put on production
                    //$myucla_curl = file_get_contents($url_update_push);
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

                        // We can continue still, even if we get some errors
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
     *  @throws CourseCreatorException
     **/
    function send_emails() {
        if (empty($this->cron_term_cache['url_info'])) {
            throw new CourseCreatorException(
                'We have no URL information for E-Mails.'
            );
        }   
        
        // These are the argument sent to insert_local_entry
        $user_callback_object = new StdClass();
        $user_callback_object->term_table = 'instructors';
        $user_callback_object->key_field = 'key_field_instructors';

        // This should fill the term cache 'instructors' with data from 
        // ccle_CourseInstructorsGet
        retrieve_registrar_info('ccle_CourseInstructorGet', 
            $user_callback_object);

        if (empty($this->cron_term_cache['instructors'])) {
            $this->debugln('No instructors for this term!');
            // @todo should we stop building the term in this case?
        }

        // I would parse this out into other functions, but I'm lazy...
        // I think the old version works pretty well...
        $courses = $this->cron_term_cache['requests'];
        $rci_objects = $this->cron_term_cache['term_rci'];
        $instructors = $this->cron_term_cache['instructors'];
        $profcodes = $this->cron_term_cache['profcodes'];
        $course_urls = $this->cron_term_cache['url_info'];

        $requestor_emails = array();
        $no_emails = array();

        foreach ($courses as $course) {
            $csrs = $course->srs;
            $term = $course->term;

            $rci_course = $rci_objects[$csrs];
            $dept = trim($rci_course->subj_area);

            // @todo write term_to_text
            $pretty_term = $this->term_to_text($term, $rci_course->session_group);

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

            // Include the child courses in the email
            if (isset($course->crosslisted) && !empty($course->crosslisted)) {
                foreach ($course->crosslisted as $child) {
                    $rci_course = $rci_objects[$child];

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

            $profcode_set = $profcodes[$csrs];
            foreach ($instructors[$csrs] as $instructor) {
                // @todo figure out which instructors to allow
                $viewable = $this->get_viewable_status($instrutor, $profcode_set);

                if ($viewable) {
                    $show_instructors[] = $instructor;
                }
            }

            if (empty($show_instructors)) {
                $this->println("No instructors to email for $term $srs ($course_text)!");
                continue;
            }

            $course_url = $course_urls[$term][$csrs];
            $course_dept = $course->department;

            foreach ($show_instructors as $instructor) {
                $lastname = $this->format_name(trim($instructor->last_name_person));
                $email = trim($instructor->ursa_email);

                if ($email == '') {
                    $no_emails[$instructor->ucla_id] = $instructor;
                }

                unset($email_ref);

                $email_ref['lastname'] = $lastname;
                $email_ref['to'] = $email;
                $email_ref['coursenum-sect'] = $course_text;
                $email_ref['dept'] = '';
                $email_ref['url'] = 'https://'. $course_url;
                $email_ref['term'] = $term;
                $email_ref['nameterm'] = $pretty_term;

                // These are not parsed
                $email_ref['subjarea'] = $course_dept;
                $email_ref['userid'] = $instructor->ucla_id;
                $email_ref['srs'] = $csrs;
                $emails[] = $email_ref;
            }
            
            if (isset($course->contact) && !empty($course->contact)) {
                $contact = $course->contact;
                if (!isset($requestor_emails[$contact])) {
                    if (validate_email($contact)) {
                        $requestor_emails[$contact] = array();
                    } else {
                        $this->emailln("Requestor email $contact not valid for $term $csrs");
                    }
                }

                if (isset($requestor_emails[$contact])) {
                    $requestor_emails[$contact][$course_url] = $course_url;
                }
            }
        }

        // Try to check out local records for emails
        // @todo move this chunk out into function get_users_idnumber()
        $local_email = array();
        if (!empty($no_emails)) {
            $local_wheres = array();

            foreach ($no_emails as $emailless) {
                // Attempt to find user
                $userid = $emailless->ucla_id;
                $name = trim($emailless->first_name_person) . ' ' 
                    . trim($emailless->last_name_person);
                mylog("$name $userid has no email.");

                $local_wheres[] = $userid;
            }

            // @todo find this index
            $local_where = "('" . implode("', '", $local_wheres) . "')";

            $sql = "
                SELECT id, email, idnumber
                FROM {$CFG->prefix}user
                WHERE
                    idnumber IN $local_where
                ";

            mylog("Searching local MoodleDB for idnumbers $local_where ...");
            $local_users = get_records_sql($sql);

            if (!empty($local_users)) {
                foreach ($local_users as $local_user) {
                    $email = trim($local_user->email);

                    if ($email != '') {
                        $idnumber = $local_user->idnumber;
                        mylog("Found user $idnumber $email");
                        $local_email[$local_user->idnumber] = $email;
                    }
                }
            }

            unset($local_users);
        }

        unset($no_emails);

        // Parsed
        $parsed_params = array();

        $debugmode = $this->get_debug();

        $email_summary_data = array();
        foreach ($emails as $emailing) {
            $add_subject = '';
            $email_to = '';

            // This is going to be used later
            $csrs = $emailing['srs'];
            unset($emailing['srs']);

            // Filter out no emails
            $userid = $emailing['userid'];

            if (!isset($email_summary_data[$csrs])) {
                $email_summary_data[$csrs] = array();
            }

            $email_summary_data[$csrs][$userid] = '';

            if ($emailing['to'] == '') {
                // Attempt to find user
                if (!isset($local_email[$userid])) {
                    $this->println("Cannot email $userid " . $emailing['lastname']);
                    $email_summary_data[$csrs][$userid] .= "! " . $emailing['lastname']
                        . "\t $userid \tFAILED - No email address.\n";
                    continue;
                } else {
                    $emailing['to'] = $local_email[$userid];

                    $email_summary_data[$csrs][$userid] .= '* ' . $emailing['lastname']
                        . "\t $userid \t" . $local_email[$userid] 
                        . " - Local email ONLY\n";
                }
            } 
            
            // Set the destination
            $email_to = $emailing['to'];

            if ($userid == '100399990') {
                $email_to = '';
                $add_subject = ' (THE STAFF)';
            } else if ($userid == '200399999') {
                $email_to = '';
                $add_subject = ' (TA)';
            }

            unset($emailing['userid']);

            // Parse the email
            $subj = $emailing['subjarea'];

            if (!isset($parsed_param[$subj])) {
                $deptfile = $email_short . $subj . $email_suffix;

                if (file_exists($deptfile)) {
                    $file = $deptfile;
                } else {
                    $file = $default_email_file;
                }

                $parsed_param[$subj] = parsefile($file);
            }

            $used_param = $parsed_param[$subj];
            unset($emailing['subjarea']);

            $email_params = filltemplate($used_param, $emailing);

            // Setup the email
            $from = $email_params['from'];
            $bcc = $email_params['bcc'];

            $headers = "From: $from \r\n Bcc: $bcc \r\n";

            $email_subject = $email_params['subject'];
            $email_subject .= $add_subject;

            $email_body = $email_params['body'];

            $email_summary_data[$csrs][$userid] .= '. ' . $emailing['lastname']
                . "\t $userid \t" . $email_to . " \t $email_subject\n";

            if (isset($debugmode) && !$debugmode) {
                $this->println("Emailing: $email_to");
                //mail($email_to, $email_subject, $email_body, $headers);
            } else {
                $this->println("to: $email_to");
                $this->println("headers: $headers");
                $this->println("subj: $email_subject");

                mail($CFG->course_creator_email, $email_subject, $email_body);
            }
        }

        unset($emailing);

        // @todo split this out
        // Email course requestors
        foreach ($requestor_emails as $requestor => $created_courses) {
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

            if (!$debugmode) {
                die;
                if (mail($requestor, $req_subj, $req_mes, $requestor_headers)) {
                    $this->println("Emailed $requestor for $req_summary");
                } else {
                    $this->println("ERROR: course not email $requestor");
                }
            }

            // @todo figure out where to put htis
            $this->emailln("Emailed $requestor for $req_summary");
        }
    }

    /**
     *  Inserts the records into {ucla_reg_classinfo} and updates them as 
     *  finished in {ucla_request_classes}.
     *
     **/
    function finish_term_cron() {

    }

    /**
     *  Check that we have write priviledges, if not, we will use moodledata.
     *
     *  Changes the state of the object.
     **/
    function check_write() {
        global $CFG;
        // @todo check that we have write capability in the IMS output folder,
        // otherwise revert to some moodledata folder
        if (!$this->get_config('course_creator_outpath')) {
            $this->output_path = $CFG->dataroot . '/course_creator';
            if (!file_exists($this->output_path)) {
                if (!mkdir($this->output_path)) {
                    throw new CourseCreatorException('Could not make ' 
                        . $this->output_path);
                }
            }
        } else {
            $this->output_path = $this->get_config('course_creator_outpath');
        }

        $test_file = $this->output_path . '/write_test.txt';

        if (!fopen($test_file, 'w')) {
            throw new CourseCreatorException('No write permissions to ' 
                . $this->output_path);
        } else {
            unlink($test_file);
        }
    }

    /**
     *  Will determine whether or not we can run this function.
     *  @param boolean $lock true for lock, false for unlock.
     *  @param boolean $soft true for no file lock, false for a file lock
     *  @return boolean If we the action was successful or not.
     *  @since Moodle 2.0.
     **/
    function handle_locking($lock, $soft=false) {
        global $DB;

        // @todo prevent more than one instance of this cron from running

        // Prevent new requests that come in during course creation from 
        // affecting course creator
        if ($lock) {
            $sql_where = "
                action LIKE '%uild'
                    AND
                status <> 'done'
            ";

            $DB->set_field_select('ucla_request_classes', 'status', 
                'processing', $sql_where);
        } 

        return true;
    }

    /**
     *  Mails the requestors.
     *
     **/
    function mail_requestors() {
        // @todo
    }

    /**
     *  Temporary wrapper for finishing up cron.
     *  Email admin.
     *  Cleanup certain things?
     *  May not be necessary.
     **/
    function finish_cron() {
        // @todo email admin
        // @todo close open databases
        // @todo close open files

        echo "\ne-Mail:\n" . $this->email_log;

        return true;
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
     *  @todo Should check in the configuration what term to use to build.
     *
     *  Will change the state of the object.
     *
     **/
    function figure_terms() {
        if ($this->get_config('course_creator_terms')) {
            $terms_list = explode(' ', 
                $this->get_config('course_creator_terms'));
        }

        if (isset($terms_list)) {
            foreach ($terms_list as $term) {
                if (!$this->validate_term($term)) {
                    throw new CourseCreatorException('Improper term ' . $term);
                }
            }

            $this->terms_list = $terms_list;

            return $terms_list;
        }

        return false;
    }

    /**
     *  Will figure out the email non-variants.
     *
     *  Will change the state of the object.
     **/
    function figure_email() {
        // @todo
        // course_creator_mailheaders
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
        if ($this->get_config('ucla_friendlyurls_enabled')) {
            return new moodle_url('/course/view/' . $course->shortname);
        }

        return new moodle_url('/course/view.php', array('id' => $course->id));
    }

    /**
     *  Wrapper for {@see get_config()}
     **/
    function get_config($config) {
        return get_config(NULL, $config);
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
            throw new CourseCreatorException('Registrar DB not set!');
        }

        // Manually coded check for odbc functionality, since moodle doesn't 
        // seem to like exceptions
        if (strpos($dbtype, 'odbc') !== false) {
            if (!function_exists('odbc_exec')) {
                throw new Exception('FATAL ERROR: ODBC not installed!');
            }
        }

        // Connect to the external database (forcing new connection)
        $extdb = ADONewConnection($dbtype);
        if (!$extdb) {
            throw new CourseCreatorException(
                'Could not connect to registrar!'
            );
        }

        if ($this->get_debug()) {
            $extdb->debug = true;
            // @todo validate necessity
            // start output buffer to allow later use of the page headers
            //ob_start();
        }

        $extdb->Connect(
            $this->get_config('registrar_dbhost'), 
            $this->get_config('registar_dbuser'), 
            $this->get_config('registrar_dbpass'),
            $this->get_config('registrar_dbname')
        );

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
     *  @todo Handle McDonalds !!! FILLET O FISH
     *  @param $name The name to format.
     *  @return string The name with guessed capitals.
     **/
    function format_name($name) {
        // @todo Actually do smart ones
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
