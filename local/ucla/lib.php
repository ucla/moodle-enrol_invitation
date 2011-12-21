<?php
/**
 *  UCLA Global functions.
 **/

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

// Auto-login if user is guest
function auto_login_as_guest() {
    global $SESSION, $USER;
    if ($USER->username == 'guest') {
        $flag = get_shib_logged_in_cookie();
        if ($flag === false) {
            unset($SESSION->ucla_login_as_guest);
        } else {
            $SESSION->ucla_login_as_guest = $flag;
        }
    }
}

// Return the value of the Shibboleth cookie, or false if it does not exist
function get_shib_logged_in_cookie() {
    global $CFG;
    return isset($_COOKIE[$CFG->shib_logged_in_cookie]) ? $_COOKIE[$CFG->shib_logged_in_cookie] : false;
}

// Check if an Shibboleth cookie exists
function is_shib_logged_in_cookie_set() {
    global $CFG;
    return isset($_COOKIE[$CFG->shib_logged_in_cookie]);
}

// If the user is guest but an Shibboleth cookie exists, we "click" the "login" link for them
function require_user_finish_login() {
    global $CFG, $FULLME, $SESSION;
    if ((!isloggedin() || isguestuser()) && is_shib_logged_in_cookie_set()) {
        
        // If a flag is set in $SESSION indicating that the user has chosen "Guess Access"
        // in the login page, don't redirect her back to the login page
        if (isset($SESSION->ucla_login_as_guest) && $SESSION->ucla_login_as_guest === get_shib_logged_in_cookie())
            return;

        // Now using timeout value in new cookie for semi-lazy session initialization 
        // with Shibboleth cookie documented here:
        // https://spaces.ais.ucla.edu/display/iamuclabetadocs/DetectingShibbolethSession
        $login_cookie_value = get_shib_logged_in_cookie();
        if (strtotime($login_cookie_value) < time())
            return;
        
        // Otherwise, redirect the user to the login page and note in $SESSION->wantsurl that
        // the login page should eventually redirect back to this page
        $SESSION->wantsurl = $FULLME;
        redirect($CFG->wwwroot .'/login/index.php');
        exit();

/**
 * Given a registrar profcode and list of other roles a user has, returns what
 * Moodle role a user should have.
 * 
 * @param int $profcode             Registrar prof code
 * @param array $other_roles        Other roles a user has
 * @param type $subject_area        Default "*SYSTEM*". What subject area we
 *                                  are assigning roles for.
 * @return type 
 */
function role_mapping($profcode, array $other_roles, $subject_area="*SYSTEM*")
{
    // logic to parse profcodes, and return pseudorole
    $pseudorole = get_pseudorole($profcode, $other_roles);
    
    // call to the ucla_rolemapping table
    $moodleroleid = get_moodlerole($pseudorole, $subject_area); 
    
    return $moodleroleid;
}


/**
 * This mapping definition will be used only for instructors
 * Refer to Jira: CCLE-2320
 * 
 * role InstSet     Pseudo Role
 * 01   any         instructor
 * 02	01,02       ta
 * 02	01,02,03    ta
 * 02	02,03       ta_instructor
 * 03	any	        supervising_instructor
 * 22	any	        student_instructor
 * 
 * @param int $profcode        Registrar prof code
 * @param array $other_roles   Other roles a user has
 * 
 * @return string              Returns either: instructor, ta, ta_instructor,
 *                             supervising_instructor, or student_instructor
 */
function get_pseudorole($profcode, array $other_roles) {
    $max = 0;
    for ($other_roles as $other_role) {
        $ivor = intval($other_role);
        $hasrole[$ivor] = true;

        if ($ivor > $max) {
            $max = $ivor;
        }
    }

    // Fill in the rest of these to avoid no-index notifications
    for ($i = 0; $i < $max; $i++) {
        if (!isset($hasrole[$i])) {
            $hasrole[$i] = false;
        }
    }

    switch ($profcode) {
        case 1:
            return "instructor";
        case 2:
            if ($hasrole[1] && $hasrole[2]) {
                return "ta";
            } else if ($hasrole[1] && $hasrole[2] && $hasrole[3]) {
                return "ta_instructor";
            }
        case 3:
            return "supervising_instructor";
        case 22:
            return "student_instructor";
    }
}

/**
 * @param string type   Type can be 'term', 'srs', 'uid'
 * @param mixed value   term: DDC 
 *      (two digit number with C being either F, W, S, 1)
 *                      SRS/UID: (9 digit number, can have leading zeroes)
 * @return boolean      true if the value matches the type, false otherwise.
 * @throws moodle_exception When the input type is invalid.
 */
function ucla_validator($type, $value){
    $result = 0;
    
    switch($type) {
        case 'term':
            $result = preg_match('/^[0-9]{2}[FWS1]$/', $value);
            break;
        case 'srs':
        case 'uid':
            $result = preg_match('/^[0-9]{9}$/', $value);
            break;
        default:
            throw new moodle_exception('invalid type', 'ucla_validator');
            break;
    }
    
    return $result == 1; 
}

/**
 * Given a pseudorole (from get_pseudorole), returns what moodle role a user
 * should be assigned for a given department. First a look-up is done in the 
 * database for a given pseudorole and subject area. Then the function looks
 * at the role mapping config file. If the role mapping is present in that file
 * it will override any values from the database.
 * 
 * @throws moodle_exception         Throws moodle exception if no role mapping 
 *                                  is found
 * 
 * @global type $CFG
 * @global type $DB
 * 
 * @param string $pseudorole
 * @param string $subject_area      Default "*SYSTEM*".
 * 
 * @return int                      Moodle role id. 
 */
function get_moodlerole($pseudorole, $subject_area='*SYSTEM*') {
    global $CFG, $DB;

    require($CFG->dirroot . '/local/ucla/role_mappings.php');

    // if mapping exists in file, then don't care what values are in the db
    if (!empty($role[$pseudorole][$subject_area])) {
        if ($moodlerole = $DB->get_record('role', 
                array('shortname' => $role[$pseudorole][$subject_area]))) {
            return $moodlerole->id;
        }            
    }        
    
    // didn't find role mapping in config file, check database
    if ($moodlerole = $DB->get_record('ucla_rolemapping', 
            array(
                'pseudo_role' => $pseudorole, 
                'subject_area' => $subject_area
            ))) {
        return $moodlerole->moodle_roleid;    
    }
    
    // if no role was found, then use *SYSTEM* default 
    // (should be set in config)
    if ($moodlerole = $DB->get_record('role', 
            array('shortname' => $role[$pseudorole]['*SYSTEM*']))) {
        return $moodlerole->id;
    }              
    
    // oh no... didn't find proper role mapping, stop the presses
    throw new moodle_exception('invalidrolemapping', 'local_ucla', null, 
            sprintf('Params: $pseudorole - %s, $subject_area - %s', 
                    $pseudorole, $subject_area));
}

