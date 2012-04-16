<?php
/**
 *  UCLA Global functions.
 **/

defined('MOODLE_INTERNAL') || die();
global $CFG;
//require_once($CFG->libdir . '/uclalib.php');
require_once($CFG->dirroot.'/lib/accesslib.php');
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
 *  Convenience function to include db-helpers.
 **/
function ucla_require_db_helper() {
    global $CFG;

    require_once($CFG->dirroot
        . '/local/ucla/dbhelpers.php');
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
 *  Responder for results from ccle_courseinstructorsget to see if
 *  the user is a dummy user.
 *  @param $ucla_id String of the UID number
 *  @return boolean 
 **/
function is_dummy_ucla_user($ucla_id) {
    // dummy THE STAFF
    if ($ucla_id == '100399990') {
        return true;
    } 

    // dummy TA
    if ($ucla_id == '200399999') {
        return true;
    }

    return false;
}

/**
 *  Checks if an enrol-stat code means a course is cancelled.
 **/
function enrolstat_is_cancelled($enrolstat) {
    return strtolower($enrolstat) == 'x';
}

/** 
 *  Checks if a course should be considered cancelled.
 *  Note that this does require an enrolstat, which means that
 *      the data needs to come from ucla_reg_classinfo.
 *  Note that misformed data will throw an exception.
 *  @param  $courseset  Array( Object->enrolstat, ... )
 *  @return boolean     true = cancelled
 **/
function is_course_cancelled($courseset) {
    // No information, assume not-cancellable
    if (empty($courseset)) {
        return false;
    }

    $cancelled = true;
    foreach ($courseset as $course) {
        if (empty($course->enrolstat)) {
            throw new coding_exception('missing enrolstat');
        } else if (!enrolstat_is_cancelled($course->enrolstat)) {
            $cancelled = false;
        }
    }

    return $cancelled;
}

/**
 *  Builds the URL for the Registrar's finals information page.
 *  TODO Make the URL a configuration variable.
 **/
function build_registrar_finals_url($courseinfo) {
    if (!empty($courseinfo->term) 
            && ucla_validator('term', $courseinfo->term)) {
        $term = $courseinfo->term;
    } else {
        return false;
    }

    if (!empty($courseinfo->srs)
            && ucla_validator('srs', $courseinfo->srs)) {
        $srs = $courseinfo->srs;
    } else {
        return false;
    }

    $regurl = 'http://www.registrar.ucla.edu/schedule/subdet.aspx';

    $params = array(
        'term' => $term,
        'srs' => $srs
    );

    foreach ($params as $param => $value) {
        $paramstrs[] = $param . '=' . $value;
    }

    return $regurl . '?' . implode('&', $paramstrs);
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
 *  Creates a display-ready string for a course.
 *  Slightly similar to shortname...
 *  @param $courseinfo Array with fields
 *      subj_area - the subject area
 *      coursenum - the course number
 *      sectnum   - the number of the section
 *  @param $displayone boolean True to display the sectnum of 1
 **/
function ucla_make_course_title($courseinfo, $displayone=false) {
    $sectnum = '-' . $courseinfo['sectnum'];
    if ($displayone && $courseinfo['sectnum'] == 1) {
        $sectnum = '';
    }

    return $courseinfo['subj_area'] . ' ' . trim($courseinfo['coursenum'])
        . $sectnum;
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
 *  Fetch each corresponding term and srs for a particular courseid.
 *  @param  $courseid   Primary key for course table
 **/
function ucla_map_courseid_to_termsrses($courseid) {
    global $DB;

    return $DB->get_records('ucla_request_classes', 
        array('courseid' => $courseid), '', 'id, term, srs');
}

/**
 *  Fetch the corresponding courseid for a particular term and srs.
 **/
function ucla_map_termsrs_to_courseid($term, $srs) {
    global $DB;

    $dbo = $DB->get_record_sql('
        SELECT courseid
        FROM {ucla_request_classes} rc
        INNER JOIN {course} co ON rc.courseid = co.id
        WHERE rc.term = :term AND rc.srs = :srs
    ', array('term' => $term, 'srs' => $srs), '', 'courseid');

    if (isset($dbo->courseid)) {
        return $dbo->courseid;
    }

    return false;
}

/**
 *  Fetch the corresponding information from the registrar for a 
 *  particular term and srs.
 **/
function ucla_get_reg_classinfo($term, $srs) {
    global $DB;

    $records = $DB->get_record('ucla_reg_classinfo',
            array('term' => $term, 'srs' => $srs));

    return $records;
}

/**
 *  Convenience function to get registrar information for classes.
 **/
function ucla_get_course_info($courseid) {
    $reginfos = array();
    $termsrses = ucla_map_courseid_to_termsrses($courseid);
    foreach ($termsrses as $termsrs) {
        $reginfos[] = ucla_get_reg_classinfo($termsrs->term, $termsrs->srs);
    }

    return $reginfos;
}

/**
 *  Convenience function. 
 *  @param  $requests   
 *      Array of Objects with properties term, srs, and $indexby
 *      if the requests do not have $indexby, then it will not be indexed
 *  @param  $indexby    What you want as the primary index
 **/
function index_ucla_course_requests($requests, $indexby='setid') {
    $reindexed = array();

    if (!empty($requests)) {
        foreach ($requests as $record) {
            if (isset($record->$indexby)) {
                $reindexed[$record->$indexby][make_idnumber($record)] = $record;
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

    return index_ucla_course_requests($records, 'courseid');
}

/**
 *  Returns a pretty looking term in the format of 12S => Spring 2012.
 * 
 * @param string term
 * @param char session      If session is passed, then, assuming the term is 
 *                          summer, will return 121, A => Summer Session A 2012
 **/
function ucla_term_to_text($term, $session=null) {
    $term_letter = strtolower(substr($term, -1, 1));
    $termtext = '';
    if ($term_letter == "f") {
        $termtext = "Fall";
    } else if ($term_letter == "w") {
        // W -> Winter
        $termtext = "Winter";
    } else if ($term_letter == "s") {
        // S -> Spring
        $termtext = "Spring";
    } else {
        // 1 -> Summer
        if (!empty($session)) {
            $termtext = "Summer Session " . strtoupper($session);   
        } else {
            $termtext = "Summer";
        }
    }

    $years = substr($term, 0, 2);
    $termtext .= " 20$years";    
    
    return $termtext;
}

function is_summer_term($term) {
    return ucla_validator('term', $term) && substr($term, -1, 1) == '1';
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
     *  - If name has conjunctions, e.g. "and", "of", "the", "as", "a"
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

    // If name has conjunctions, e.g. "and", "of", "the", "as", "a"
    if (in_array(strtolower($name), array('and', 'of', 'the', 'as', 'a'))) {
        $name = strtolower($name);
    }
    
    return $name;

}

/**
 *  Populates the reg-class-info cron, the subject areas and the divisions.
 **/
function local_ucla_cron() {
    global $DB, $CFG;

    // TODO Do a better job figuring this out
    $terms = array($CFG->currentterm);

    include_once($CFG->dirroot . '/local/ucla/cronlib.php');
    ucla_require_registrar();

    // Customize these times...?
    $works = array('classinfo', 'subjectarea', 'division');

    foreach ($works as $work) {
        $cn = 'ucla_reg_' . $work . '_cron';
        if (class_exists($cn)) {
            $runner = new $cn();
            if (method_exists($runner, 'run')) {
                echo "Running $cn\n";
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
 * 01   any         editingteacher
 * 02	01,02       ta
 * 02	01,02,03    ta
 * 02	02,03       ta_instructor
 * 03	any	    supervising_instructor
 * 22	any	    editingteacher
 * 
 * @param int $profcode        Registrar prof code
 * @param array $other_roles   Other roles a user has
 * 
 * @return string              Returns either: editingteacher, ta,
 *                             ta_instructor, or supervising_instructor
 */
function get_pseudorole($profcode, array $other_roles) {
    $hasrole = array_pad(array(), 23, false);   // need to create 23, because 22 
                                            // needs to be an index    
    foreach ($other_roles as $other_role) {
        $hasrole[intval($other_role)] = true;
    }

    switch (intval($profcode)) {
        case 1:
            return "editingteacher";
        case 2:
            if (!$hasrole[1] && $hasrole[3]) {
                return "ta_instructor";
            } else {
                return "ta";
            }
        case 3:
            return "supervising_instructor";
        case 22:
            return "editingteacher";
    }
}

/**
 *  This is a function to return the pseudoroles for student enrolment
 *  code values.
 *  @return string - pseudorole, false - not enrolled
 **/
function get_student_pseudorole($studentcode) {
    $code = strtolower(trim($studentcode));
    $psrole = false;

    switch($code) {
        case 'w':   // waitlist
        case 'h':   // held (unex)
        case 'p':   // pending
            $psrole = 'waitlisted';
            break;
        case 'e':   // enrolled
        case 'a':   // approved (unex)
            $psrole = 'student';
            break;
        default:
            // This includes codes:
            // d = dropped
            // c = cancelled
            // If they do not have an explicitly declared role code,
            // then they are considered unenrolled
            $psrole = false;
    }

    return $psrole;
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
        // change subject to have divert message
        $subj = "[DIVERTED $to] $subj";      
        // clear out old to
        $to = $CFG->divertallemailsto;
        // clear header variable, because it might contain an email address
        $header = '';        
    }

    if (debugging() && empty($CFG->divertallemailsto)) {
        // if divertallemailsto is set, then send out email even if debugging is 
        // enabled
        debugging("TO: $to\nSUBJ: $subj\nBODY: $body\nHEADER: $header");
    } else {
        debugging("Sending real email to " . $to);
        return @mail($to, $subj, $body, $header);
    }

    return true;
}

/**
 *  Sorts a set of terms.
 *  @param  $terms  Array( term, ... )
 *  @return Array( term_in_order, ... )
 **/
function terms_arr_sort($terms) {
    $ksorter = array();

    // enumerate terms
    foreach ($terms as $k => $term) {
        $ksorter[$k] = term_enum($term);
    }

    // sort
    asort($ksorter);
  
    // denumerate terms
    $sorted = array();
    foreach ($ksorter as $k => $v) {
        $sorted[] = $terms[$k];
    }

    return $sorted;
}

/**
 *  PHP side function to order terms.
 *  @param  $term   term
 *  @return string sortable term
 **/
function term_enum($term) {
    if (!ucla_validator('term', $term)) {
        print_error('improperenum');
    }
    
    $r = array(
        'W' => 0,
        'S' => 1,
        '1' => 2,
        'F' => 3
    );

    return substr($term, 0, -1) . $r[$term[2]];
}

/**
 *  Compare-to function.
 *  @param  $term   The first
 *  @param  $term   The second
 *  @return 
 *      first > second return 1
 *      first == second return 0
 *      first < second return -1
 **/
function term_cmp_fn($term, $other) {
    $et = term_enum($term);
    $eo = term_enum($other);
    if ($et > $eo) {
        return 1;
    } else if ($et < $eo) {
        return -1;
    } else {
        return 0;
    }
}

/**
 * Returns true if given course object is a collabration site, otherwise false.
 * 
 * Until the collab site indicator is implemented for now a course is a collab
 * site if it doesn't exist in the ucla_request_classes table.
 * 
 * @param object $course
 * @return boolean 
 */
function is_collab_site($course) {
    $result = ucla_map_courseid_to_termsrses($course->id);
    if (empty($result)) {
        return true;
    }    
    return false;
}

/**
 *  Returns whether or not the user is the role specified by the role_shortname
 *  in the role table
 * 
 * @param $role_shortname the name of the role's shortname entry in the db table
 * @param $context the context in which to check the roles.
 * 
 * @return boolean true if the user has the role in the context, false otherwise
 **/
function has_role_in_context($role_shortname, $context){
    
    global $DB;
    $does_role_exist = $DB->get_records('role', array('shortname'=>$role_shortname));
    if(empty($does_role_exist)){
        debugging("Role shortname not found in database table.");
        return false;
    }
    
    $roles_result = get_user_roles($context);

    foreach($roles_result as $role){
        if($role->shortname == $role_shortname){
            return true;
        }
    } 
    return false;
}

// EOF
