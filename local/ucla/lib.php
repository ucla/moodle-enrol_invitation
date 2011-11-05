<?php
/**
 * UCLA specific functions should be defined here.
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . "/../../config.php");

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
 * 03	any	    supervising_instructor
 * 22	any	    student_instructor
 * 
 * @param int $profcode        Registrar prof code
 * @param array $other_roles   Other roles a user has
 * 
 * @return string              Returns either: instructor, ta, ta_instructor,
 *                             supervising_instructor, or student_instructor
 */
function get_pseudorole($profcode, array $other_roles)
{
    $max = count($other_roles);
    for ($i = 0; $i < $max; $i++) {
        $hasrole[$other_roles[$i]] = 'true';
    }

    switch ($profcode) {
        case 1:
            return "instructor";
        case 2:
            if ($hasrole[1] == 'true' && $hasrole[2] == 'true') {
                return "ta";
            } else if ($hasrole[1] != 'true' && $hasrole[2] == 'true' && $hasrole[3] == 'true') {
                return "ta_instructor";
            }
        case 3:
            return "supervising_instructor";
        case 22:
            return "student_instructor";
    }
}

/**
 * Given a pseudorole (from get_pseudorole), returns what moodle role a user
 * should be assigned for a given department. First a look-up is done in the 
 * database for a given pseudorole and subject area. Then the function looks
 * at the role mapping config file. If the role mapping is present in that file
 * it will override any values from the database.
 * 
 * @global type $CFG
 * @global type $DB
 * 
 * @param string $pseudorole
 * @param string $subject_area      Default "*SYSTEM*".
 * 
 * @return int                      Moodle role id. 
 */
function get_moodlerole($pseudorole, $subject_area='*SYSTEM*') //call to the ucla_rolemapping table
{
    require_once($CFG->dirroot . '/local/ucla/role_mappings.php');
    global $CFG, $DB;
    $moodle_roleid = null;
    
    $moodleroleobject = $DB->get_record('ucla_rolemapping', 
            array('pseudo_role' => $pseudorole, 'subject_area' => $subject_area));
    if (!empty($moodleroleobject)) {
        $moodle_roleid = $moodleroleobject->moodle_roleid;    
    }

    // if role mappings file exists, then overrides what values are in the db
    if (!empty($role[$pseudorole][$subject_area])) {
        // found role mapping in file, use this instead
        if ($moodlerole = $DB->get_record('role', 
                array('shortname' => $role[$pseudorole][$subject_area]))) {
            $moodle_roleid = $moodlerole->id;
        }            
    }        
    
    // if no role was found, then use *SYSTEM* default (should be set in config)
    if (empty($moodle_roleid)) {
        if ($moodlerole = $DB->get_record('role', 
                array('shortname' => $role[$pseudorole]['*SYSTEM*']))) {
            $moodle_roleid = $moodlerole->id;
        }              
    }
    
    return $moodle_roleid;
}
