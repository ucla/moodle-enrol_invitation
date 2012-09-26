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
 * Users that are enrolled are enrolled with a default role: student
 * 
 */

define('CLI_SCRIPT', true);

 /*
  * SET ROLE FOR ENROLLED USERS
  * 
  * Available options (full list in: mdl_role.shortname)
  *     manager
  *     manager_limited
  *     editinginstructor
  *     nonediting_instructor
  *     supervising_instructor
  *     ta_instructor
  *     ta_admin
  *     ta
  *     sh_quiz_creator
  *     student
  *     project_lead
  *     project_member
  * 
  */
$role = student;

// Requires:
$path = dirname(__FILE__) . '/../../../config.php';
require_once($path);
$path = $CFG->dirroot . '/lib/enrollib.php';
require_once($path);

global $DB, $CFG;

// Needs two arguments, courseid and term
if ($argc != 3) {
    exit ('Usage: xxx.php <courseid> <term>' . "\n");
}

$courseid = $argv[1];
$term = $argv[2];

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
$roleid = $roleid->id;

// Get the course's context_id
$context_id = context_course::instance($courseid);
$context_id = $context_id->id;

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

$a = $DB->get_recordset_sql($sql_findusers, $params);

$users_added = 0;

foreach($a as $user_id) {
    // For each user, add to course using "self-enrollment" plugin
    $user_id = $user_id->userid;
    
    // If user is already in course, then don't enrol.
    $coursecontext = context_course::instance($courseid);
    if (!is_enrolled($coursecontext, $user_id, '', true)) {
        $enrol_plugin->enrol_user($enrol_instance, $user_id, $roleid);
        $users_added++;
    }
}

$a->close();

if ($users_added == 1) {
    echo($users_added . ' user was added.' . "\n");
} else {
    echo($users_added . ' users were added.' . "\n");
}
// EOF