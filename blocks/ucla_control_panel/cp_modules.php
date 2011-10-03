<?php
/**
 *  UCLA Control Panel content index.
 *  Try not add logic to this file, if your module requires additional logic
 *  it should be a new class in it of itself.
 **/

require_once(dirname(__FILE__) . '/ucla_cp_module.php');
global $CFG, $DB;

// Please note that we should have
// $course - the course that we are currently on
// $context - the context of the course

// Special section for special people
$temp_cap = 'moodle/course:update';

// The container for the special section
$modules[] = new ucla_cp_module('ucla_cp_mod_common', null, null, $temp_cap);

// Saving typing time
$temp_tag = array('ucla_cp_mod_common');

// Capability needed for things that TAs can also do
$ta_cap = 'moodle/course:enrolreview';

// Course Forum Link
if (ucla_cp_module::load('email_students')) {
    $modules[] = new ucla_cp_module_email_students($course);
}

// Office hours TODO
$modules[] = new ucla_cp_module('edit_office_hours', new moodle_url('view.php'), 
    array('ucla_cp_mod_common', 'ucla_cp_mod_other'), $ta_cap);

// For editing, it is a special UI case
$spec_ops = array('pre' => false, 'post' => true);

$modules[] = new ucla_cp_module('turn_editing_on', new moodle_url(
        $CFG->wwwroot . '/course/view.php',
        array('id' => $course->id, 'edit' => 'on', 'sesskey' => sesskey())), 
    $temp_tag, $temp_cap, $spec_ops);

// Other Functions
$modules[] = new ucla_cp_module('ucla_cp_mod_other');

// Saving typing...
$temp_tag = array('ucla_cp_mod_other');

// Edit user profile!
$modules[] = new ucla_cp_module('edit_profile', new moodle_url(
        $CFG->wwwroot . '/user/edit.php'), $temp_tag, null);

// Import from classweb!? TODO
$modules[] = new ucla_cp_module('import_classweb', new moodle_url('view.php'), 
    $temp_tag, $temp_cap);

// Import from existing moodle course
$modules[] = new ucla_cp_module('import_moodle', new moodle_url($CFG->wwwroot
    . '/backup/import.php', array('id' => $course->id)), $temp_tag, $temp_cap);

// Create a TA-Site TODO
$modules[] = new ucla_cp_module('create_tasite', new moodle_url('view.php'), 
    $temp_tag, $ta_cap);

// View moodle participants
$modules[] = new ucla_cp_module('view_roster', new moodle_url(
    $CFG->wwwroot . '/user/index.php', array('id' => $course->id)), 
    $temp_tag, $ta_cap);

// Advanced functions
$modules[] = new ucla_cp_module('ucla_cp_mod_advanced', null, null, $temp_cap);

// Saving typing...again
$temp_tag = array('ucla_cp_mod_advanced');

// Role assignments for particular courses.
if (ucla_cp_module::load('assign_roles')) {
    // This is when you want to make more than one module.
    $enrols = enrol_get_instances($course->id, true);
    $meta_links = array();
    foreach ($enrols as $enrolment) {
        if ($enrolment->enrol == 'meta') {
            $meta_links[] = $enrolment;
        }
    }

    // Load the course we are currently viewing.
    $modules[] = new ucla_cp_module_assign_roles($course, true);

    // Load any other courses we have linked.
    foreach ($meta_links as $meta_link) {
        $meta_course = $DB->get_record('course', array('id' =>
            $meta_link->customint1));

        $modules[] = new ucla_cp_module_assign_roles($meta_course);
    }
}

// Backup
$modules[] = new ucla_cp_module('backup_copy', new moodle_url(
    $CFG->wwwroot . '/backup/backup.php', array('id' => $course->id)),
    $temp_tag, $temp_cap);


// Restore
$modules[] = new ucla_cp_module('backup_restore', new moodle_url(
    $CFG->wwwroot . '/backup/restorefile.php', array('contextid' => 
        $context->id)),
    $temp_tag, $temp_cap);

// Change course settings!
$modules[] = new ucla_cp_module('course_edit', new moodle_url(
        $CFG->wwwroot . '/course/edit.php', array('id' => $course->id)), 
    $temp_tag, $temp_cap);

// This is for course files!
$modules[] = new ucla_cp_module('course_files', new moodle_url(
    $CFG->wwwroot . '/files/coursefilesedit.php', 
        array('contextid' => $context->id)), 
    $temp_tag, $temp_cap);

// Grade viewer
$modules[] = new ucla_cp_module('course_grades', new moodle_url(
    $CFG->wwwroot . '/grade/index.php', array('id' => $course->id)),
    $temp_tag, null);
