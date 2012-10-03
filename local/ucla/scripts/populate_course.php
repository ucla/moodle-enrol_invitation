<?php
/*
 * CCLE-3532
 * 
 * Script to automatically populate a course with users with the roles:
 *      * editinginstructor
 *      * ta_instructor
 * for the given term.
 * 
 * Usage: php populate_course.php <courseid> <term>
 * 
 * Users that are enrolled are enrolled with a default role: participant
 * 
 */

define('CLI_SCRIPT', true);

 /*
  * SET ROLE FOR ENROLLED USERS
  */
$role = 'participant';

// Requires:
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->dirroot . '/lib/enrollib.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

// Needs two arguments, courseid and term
if ($argc != 3) {
    exit ('Usage: populate_course.php <courseid> <term>' . "\n");
}

$courseid = $argv[1];
$courseid = (int) $courseid;
$term = $argv[2];

// Validate arguments
if (!ucla_validator('term', $term)) {
    exit ('The term parameter is incorrectly formatted.' . "\n");
}
if (!is_int($courseid) || $courseid == 0 || $courseid == $SITE->id) {
    exit ('The courseid parameter is incorrectly formatted.' . "\n");
}

// Check if course has "self-enrollment" plugin enabled
$selfenrol = enrol_selfenrol_available($courseid);

if ($selfenrol == FALSE) {
    exit ('Self-enrollment is not enabled.' . "\n");
}

// Get 'self' enrollment instance for function 'enrol_user'
$enrol_instances = enrol_get_instances($courseid, TRUE);

foreach ($enrol_instances as $enrol_instance) {
    if ($enrol_instance->enrol === 'self') {
        break;
    }
}

// Get enrollment plugin
$enrol_plugin = enrol_get_plugin('self');

// Get roleid from mdl_role.id, given $role
$roleid = $DB->get_record('role', array('shortname' => $role), 'id');
if (empty($roleid)) {
    exit ('Unable to find role to enroll users.' . "\n");
}
$roleid = $roleid->id;

// Find roleid's for roles with instructor priveledges
$id_editinginstructor = $DB->get_record('role', array('shortname' => 'editinginstructor'), 'id');
$id_tainstructor = $DB->get_record('role', array('shortname' => 'ta_instructor'), 'id');
$id_editinginstructor = $id_editinginstructor->id;
$id_tainstructor = $id_tainstructor->id;

// Find the users with instructor priveledges in course
$sql_findusers = "
    SELECT DISTINCT mdl_role_assignments.userid
    FROM mdl_role_assignments
    INNER JOIN mdl_context
        ON mdl_role_assignments.contextid = mdl_context.id
    INNER JOIN mdl_ucla_request_classes
        ON mdl_context.instanceid = mdl_ucla_request_classes.courseid
    WHERE mdl_role_assignments.roleid IN (:id_editinginstructor, :id_tainstructor)
        AND mdl_ucla_request_classes.term = :term
        AND mdl_context.contextlevel = 50
    ";

$params = array('id_editinginstructor' => $id_editinginstructor, 
    'id_tainstructor' => $id_tainstructor,
    'term' => $term);

$coursecontext = context_course::instance($courseid);

$a = $DB->get_recordset_sql($sql_findusers, $params);

$users_added = 0;
if ($a->valid()) {
    foreach($a as $user_id) {
       // For each user, add to course using "self-enrollment" plugin
       $user_id = $user_id->userid;
    
        // If user is already in course, then don't enrol.
        if (!is_enrolled($coursecontext, $user_id, '', true)) {
            $enrol_plugin->enrol_user($enrol_instance, $user_id, $roleid);
            $users_added++;
        }
    }
}

$a->close();
    
if ($users_added == 1) {
    echo($users_added . ' user was added.' . "\n");
} else {
    echo($users_added . ' users were added.' . "\n");
}
// EOF