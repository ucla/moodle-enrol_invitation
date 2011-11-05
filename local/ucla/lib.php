<?php

defined('MOODLE_INTERNAL') || die();

//require_once($CFG->libdir . '/uclalib.php');

/**
 *  This will attempt to access this file from the web.
 *  If that is properly set up, then all directories below this directory
 *  will be web-forbidden.
 **/
function ucla_verify_configuration_setup() {
   global $CFG;

    if (!function_exists('curl_init')) {
        throw new moodle_exception('curl_failure', 'local_ucla');
    }

    ini_set('display_errors', '1');
    ini_set('error_reporting', E_ALL);

    $ch = curl_init();

    $self = $CFG->wwwroot . '/local/ucla/version.php';
    $address = $self;

    // Attempt to get at a file that should not be web-visible
    curl_setopt($ch, CURLOPT_URL, $address);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $res = curl_exec($ch);

    $returner = false;
    if (!$res) {
        throw new moodle_exception(curl_error($ch));
    } else {
        if (preg_match('/HTTP\/[0-9]*\.[0-9]*\s*403/', $res)) {
            $returner = true;
        }
    }

    curl_close($ch);

    return $returner;
}

/**
 *  Convenience function get all the courses for a particular term.
 **/
function get_courses_in_terms($terms) {
    return array();
}

/**
 *  Populates the reg-class-info cron, the subject areas and the divisions.
 **/
function local_ucla_cron() {
    global $CFG;

    // Do a better job figuring this out
    $terms = array('11F');

    include_once($CFG->dirroot . '/local/ucla/cronlib.php');
    include_once($CFG->dirroot 
        . '/local/ucla/uclaregistrar/registrar_query.class.php');

    // Fill the ucla_reg_classinfo table
    // This should run often

    // Fill the ucla_reg_subjeactarea table
    // This should run maybe once a quarter
    $ucsc = new ucla_reg_subjectarea_cron();
    $ucsc->run($terms);
    

    // Fill the ucla_reg_divisions table
    // This should run maybe once a quarter
}

// EOF
