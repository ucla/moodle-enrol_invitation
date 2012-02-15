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

/** 
 *  Convenience function to include all the Registrar connection 
 *  functionality.
 **/
function ucla_require_registrar() {
    global $CFG;

    require_once($CFG->dirroot 
        . '/local/ucla/uclaregistrar/registrar_query.class.php');
}

/**
 *  Checks if an enrol-stat code means a course is cancelled.
 **/
function enrolstat_is_cancelled($enrolstat) {
    return strtolower($enrolstat) == 'x';
}

/**
 *  Translate the single-character enrollment code to a word.
 *  There is an assumption here that case does not matter for these
 *  enrollment codes.
 **/
function enrolstat_string($enrolstat) {
    $sm = get_string_manager();
    $ess = 'enrolstat_' . strtolower($enrolstat);
    $rs = '';
    if ($sm->string_exists($ess, 'local_ucla')) {
        $rs = get_string($ess, 'local_ucla');
    } else {
        $rs = get_string('enrolstat_unknown', 'local_ucla');
    }

    return $rs;
}

/** 
 *  Serialize/hashes courses.
 **/
function make_idnumber($courseinfo) {
    if (is_object($courseinfo)) {
        $courseinfo = get_object_vars($courseinfo);
    }

    if (empty($courseinfo['term']) || empty($courseinfo['srs'])) {
        debugging('No key from object: ' . print_r($courseinfo, true));
        return false;
    }

    return $courseinfo['term'] . '-' . $courseinfo['srs'];
}

/**
 *  Convenience function for returning a single course info.
 *  @param  $courseid   Primary key for course table
 **/
function ucla_get_course_info($courseid) {
    global $DB;

    $many = ucla_get_courses_info(array($courseid));

    return reset($many);
}

/**
 *  Returns a set of courses based on the courseid provided.
 *  @author Yangmun Choi
 *  @param  
 *      $courseids   `id` field of {course} table.
 *  @return 
 *      Array (
 *          setid => 
 *              Array (
 *                  make_idnumber() => reg_info_object
 *                  ...
 *              )
 *          )
 **/
function ucla_get_courses_info($courseids) {
    global $DB;

    list($sql, $param) = $DB->get_in_or_equal($courseids);
    $where = '`courseid` ' . $sql;
    
    $requests = $DB->get_records_select('ucla_request_classes',
        $where, $param);

    // Index... this seems like it can be abstracted

    return index_ucla_course_requests($requests);
}

/**
 *  Convenicence function.
 **/
function index_ucla_course_requests($requests) {
    $reindexed = array();

    if (!empty($requests)) {
        foreach ($requests as $record) {
            if (isset($record->setid)) {
                $reindexed[$record->setid][make_idnumber($record)] = $record;
            } else {
                throw new moodle_exception('faulty ucla request');
            }
        }
    }

    return $reindexed;
}

/**
 *  Gets the course sets for a particular term.
 *  @param  $terms  Array of terms that we want to filter by.
 *  @param  $filterwithoutcourses   boolean do not display requests without
 *      courses associated with them.
 *  @return
 *      Array (
 *          {course}.`id` => ucla_get_course_info()
 *      )
 **/
function ucla_get_courses_by_terms($terms) {
    global $DB;
   
    list($sqlin, $params) = $DB->get_in_or_equal($terms);
    $where = 'term ' . $sqlin;

    $records = $DB->get_records_select('ucla_request_classes',
        $where, $params);

    return index_ucla_course_requests($records);
}

/**
 *  Gets the course sets for a particular set of term-srs's.
 *  @param  $termsrses  
 *      Array(
 *          Array(
 *              'term' => term,
 *              'srs' => srs
 *          ),
 *          ...
 *      )
 *    
 **/
function ucla_get_courses($termsrses) {
    global $DB;
   
    $termsrssql = array();
    $params = array();

    foreach ($termsrses as $termsrs) {
        $param = array($termsrs['srs'], $termsrs['term']);
        $sql = '`srs` = ? AND `term` = ?';

        $termsrssql[] = $sql;
        $params = array_merge($params, $param);
    }

    $where = implode(' OR  ', $termsrssql);

    $records = $DB->get_records_select('ucla_request_classes',
        $where, $params);

    if (!$records) {
        return array();
    }

    $reindexed = array();
    foreach ($records as $record) {
        $reindexed[$record->setid][make_idnumber($record)] = $record;
    }

    return $reindexed;
}

/**
 *  Returns a pretty looking term.
 *  TODO replace with termcaching
 *  TODO work with different millenia
 **/
function ucla_term_to_text($term) {
    $term_letter = strtolower(substr($term, -1, 1));
    $years = substr($term, 0, 2);

    $termtext = "20$years ";
    if ($term_letter == "f") {
        $termtext .= " Fall";
    } else if ($term_letter == "w") {
        // W -> Winter
        $termtext .= " Winter";
    } else if ($term_letter == "s") {
        // S -> Spring
        $termtext .= " Spring";
    } else {
        // 1 -> Summer
        $termtext .= " Summer Session " . $session;            
    }

    return $termtext;
}

/**
 * Properly format a given string so it is suitable to be used as a name. Name 
 * might include the following characters ' or - or a space. Need to properly 
 * uppercase the first letter and lowercase the rest.
 *
 * NOTE: 
 *  - Special case added if the last name starts with "MC". Assuming that
 * next character should be uppercase.
 *  - Special case: If a name as 's, like Women's studies, then the S shouldn't
 * be capitalized. 
 * 
 * @author Rex Lorenzo
 * @param string name   fname, mname, or lname
 * @return string       name in proper format
 **/
function ucla_format_name($name=null) {
    $name = ucfirst(strtolower(trim($name)));    

    if (empty($name)) {
        return '';
    }

    /* the way to handle special cases in a person's name is to recurse on
     * the following cases:
     *  - If name has a space
     *  - If name has a hypen
     *  - If name has an aprostrophe
     *  - If name starts with "MC"
     */    

    // has space? 
    $name_array = explode(' ', $name);
    if (count($name_array) > 1) {   
        foreach ($name_array as $key => $element) {
            $result = ucla_format_name($element);   // recurse
            if (!empty($result)) {
                $name_array[$key] = $result;
            } else {
                unset($name_array[$key]);   // don't use element if it is blank
            }
        }
        $name = implode(' ', $name_array);  // combine elements back        
    }

    // has hypen?
    $name_array = explode('-', $name);
    if (count($name_array) > 1) {   
        foreach ($name_array as $key => $element) {
            $name_array[$key] = ucla_format_name($element);   // recurse
        }
        $name = implode('-', $name_array);  // combine elements back        
    }    

    // has aprostrophe?
    $name_array = explode("'", $name);
    if (count($name_array) > 1) {  
        foreach ($name_array as $key => $element) {
            /*
            * Special case: If a name as 's, like Women's studies, then the S 
            * shouldn't be capitalized. 
            */         
            if (preg_match('/^[s]{1}\\s+.*/i', $element)) {
                // found a single lowercase s with a space and maybe something 
                // following, that means you found a possessive s, so make sure
                // it is lowercase and do not recuse
                $element[0] = 's'; 
                $name_array[$key] = $element;
            } else {
                // found a ' that is part of a name
                $name_array[$key] = ucla_format_name($element);   // recurse
            }
        }
        $name = implode("'", $name_array);  // combine elements back        
    }    

    // starts with MC (and is more than 2 characters)?
    if (strlen($name)>2 && (0 == strncasecmp($name, 'mc', 2))) {
        $name[2] = strtoupper($name[2]);    // make 3rd character uppercase
    }

    return $name;

}

/**
 *  Populates the reg-class-info cron, the subject areas and the divisions.
 **/
function local_ucla_cron() {
    global $CFG;

    // TODO Do a better job figuring this out
    $terms = $CFG->currentterm;

    include_once($CFG->dirroot . '/local/ucla/cronlib.php');
    ucla_require_registrar();

    $terms = array($terms);
    
    // Customize these times...?
    $works = array('classinfo', 'subjectarea', 'division');

    foreach ($works as $work) {
        $cn = 'ucla_reg_' . $work . '_cron';
        if (class_exists($cn)) {
            $runner = new $cn();
            if (method_exists($runner, 'run')) {
                $result = $runner->run($terms);
            } else {
                echo "Could not run() for $cn\n";
            }
        } else {
            echo "Could not run cron for $cn, class not found.\n";
        }

        if (!$result) {
            // Something?
        }
    }

    return true;
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
    return isset($_COOKIE[$CFG->shib_logged_in_cookie]) ? 
        $_COOKIE[$CFG->shib_logged_in_cookie] : false;
}

// Check if an Shibboleth cookie exists
function is_shib_logged_in_cookie_set() {
    global $CFG;
    return isset($_COOKIE[$CFG->shib_logged_in_cookie]);
}

// If the user is guest but an Shibboleth cookie exists, we "click" 
// the "login" link for them
function require_user_finish_login() {
    global $CFG, $FULLME, $SESSION;
    if ((!isloggedin() || isguestuser()) && is_shib_logged_in_cookie_set()) {
        
        // If a flag is set in $SESSION indicating that the user has 
        // chosen "Guess Access" in the login page, don't redirect her 
        // back to the login page
        if (isset($SESSION->ucla_login_as_guest) 
                && $SESSION->ucla_login_as_guest 
                    === get_shib_logged_in_cookie()) {
            return;
        }

        // Now using timeout value in new cookie for semi-lazy session 
        // initialization with Shibboleth cookie documented here:
        // https://spaces.ais.ucla.edu/display/iamuclabetadocs/DetectingShibbolethSession
        $login_cookie_value = get_shib_logged_in_cookie();
        if (strtotime($login_cookie_value) < time()) {
            return;
        }
        
        // Otherwise, redirect the user to the login page and note 
        // in $SESSION->wantsurl that the login page should eventually 
        // redirect back to this page
        $SESSION->wantsurl = $FULLME;
        redirect($CFG->wwwroot .'/login/index.php');
        exit();
    }
}

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
function role_mapping($profcode, array $other_roles, 
        $subject_area="*SYSTEM*") {

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

    foreach ($other_roles as $other_role) {
        $ivor = intval($other_role);
        $hasrole[$ivor] = true;

        if ($ivor > $max) {
            $max = $ivor;
        }
    }

    // Fill in the rest of these to avoid no-index notifications
    for ($i = 1; $i < $max; $i++) {
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
            } else if ($hasrole[1] && !$hasrole[2] && $hasrole[3]) {
                return "ta_instructor";
            }
        case 3:
            return "supervising_instructor";
        case 22:
            return "student_instructor";
    }
}

/**
 * @param string $type   
 *      Type can be 'term', 'srs', 'uid'
 * @param mixed $value   
 *      term: DDC (two digit number with C being either F, W, S, 1)
 *      SRS/UID: (9 digit number, can have leading zeroes)
 * @return boolean      true if the value matches the type, false otherwise.
 * @throws moodle_exception When the input type is invalid.
 */
function ucla_validator($type, $value) {
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

    require($CFG->dirroot . '/local/ucla/rolemappings.php');

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
    if (!empty($role[$pseudorole]['*SYSTEM*'])) {
        if ($moodlerole = $DB->get_record('role', 
                array('shortname' => $role[$pseudorole]['*SYSTEM*']))) {
            return $moodlerole->id;
        } else {
            debugging('pseudorole mapping found, but local role not found');
        }
    }
    
    // oh no... didn't find proper role mapping, stop the presses
    throw new moodle_exception('invalidrolemapping', 'local_ucla', null, 
            sprintf('Params: $pseudorole - %s, $subject_area - %s', 
                    $pseudorole, $subject_area));
}

/**
 *  Wrapper with debugging and diverting controls for PHP's mail.
 **/
function ucla_send_mail($to, $subj, $body='', $header='') {
    global $CFG;

    if (!empty($CFG->divertallemailsto)) {
        $to = $CFG->divertallemailsto;
        // clear header variable, because it might contain an email address
        $header = '';   
    }

    if (debugging() && !empty($CFG->divertallemailsto)) {
        // if divertallemailsto is set, then send out email even if debugging is 
        // enabled
        debugging("TO: $to\nSUBJ: $subj\nBODY: $body\nHEADER: $header");
    } else {
        debugging("Sending real email to " . $to);
        return @mail($to, $subj, $body, $header);
    }

    return true;
}

// EOF
