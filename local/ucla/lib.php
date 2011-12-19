<?php

defined('MOODLE_INTERNAL') || die();

//require_once($CFG->libdir . '/uclalib.php');

/**
 *  @deprecated
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

function ucla_require_registrar() {
    global $CFG;

    require_once($CFG->dirroot 
        . '/local/ucla/uclaregistrar/registrar_query.class.php');
}

function get_courses_info($courses) {

}

/**
 *  Returns all the courses in the local system for a particular term.
 *  Returns only child courses.
 *  Currently the SRS filter does NOT work.
 **/
function ucla_get_courses($terms=null, $srses=null) {
    global $DB;

    $where_sql = array();
    $where_params = array();

    // TODO - abstract and iterate on this... or not
    $coursef = 'c.`idnumber`';
    $ctfield = 'course_term';

    $ctselect = ', SUBSTRING(' . $coursef . ', 1, 3) AS ' . $ctfield;

    if ($terms !== null) {
        foreach ($terms as $term) {
            // TODO validate terms

            $where_sql[] = $coursef . ' LIKE ?';
            $where_params[] = $term . '-%';
        }
    }

    $csfield = 'course_srs';
    $csselect = ', SUBSTRING(' . $coursef . ', 5, 9) AS ' . $csfield;

    if ($srses !== null) {

        foreach ($srses as $srs) {
            // TODO Validate SRS

            $where_sql[] = $coursef . ' LIKE ?';
            $where_params[] = '%-' . $srs;
        }
    }

    $sql = "SELECT * $ctselect $csselect
        FROM {course} c
        LEFT JOIN {ucla_reg_classinfo} rci 
            ON CONCAT(rci.term, '-', rci.srs) = $coursef
        WHERE " . implode(' OR ', $where_sql);

    $results = $DB->get_records_sql($sql, $where_params);

    $courses_by_term = array();
    // Index results by term, then by srs
    foreach ($results as $result) {
        $term = $result->$ctfield;

        if (!isset($courses_by_term[$term])) {
            $courses_by_term[$term] = array();
        }

        $courses_by_term[$term][$result->$csfield] = $result;
    }

    return $courses_by_term;
}

/**
 *  Populates the reg-class-info cron, the subject areas and the divisions.
 **/
function local_ucla_cron() {
    global $CFG;

    // Do a better job figuring this out
    $terms = $CFG->currentterm;

    include_once($CFG->dirroot . '/local/ucla/cronlib.php');
    include_once($CFG->dirroot 
        . '/local/ucla/uclaregistrar/registrar_query.class.php');

    $terms = array($terms);

    // Fill the ucla_reg_classinfo table
    // This should run often
    $ucrc = new ucla_reg_classinfo_cron();
    $ucrc->run($terms);

    // Fill the ucla_reg_subjeactarea table
    // This should run maybe once a quarter
    $ucsc = new ucla_reg_subjectarea_cron();
    $ucsc->run($terms);

    // Fill the ucla_reg_divisions table
    // This should run maybe once a quarter
}

// EOF
