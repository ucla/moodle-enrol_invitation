<?php

global $CFG, $PAGE, $USER, $DB;

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/admin/tool/uclabulkcoursereset/bulkcoursereset_form.php');
require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');
require_once($CFG->dirroot . '/course/reset_form.php');

require_login();

// Set up $PAGE
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_heading(get_string('pluginname', 'tool_uclabulkcoursereset'));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');
$PAGE->set_url($CFG->dirroot . '/admin/tool/uclabulkcoursereset/index.php');

admin_externalpage_setup('uclabulkcoursereset');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'tool_uclabulkcoursereset'), 2, 'headingblock');

// Get collab sites of type "test" from site indicator
$collab_sites = siteindicator_manager::get_sites();

$courses = array();
foreach ($collab_sites as $site) {
    if ($site->type == 'test') {
        $courses[] = $site;
    }
}

$course_list = array();
foreach ($courses as $course) {
    $course_list[$course->id] = $course->fullname;
}

// Create the form for selecting collab sites to reset
$selectform = new bulkcoursereset_form(NULL, array('course_list' => $course_list));
if ($selectform->is_cancelled()) {
    
} else if ($data = $selectform->get_data()) {
    
}
$selectform->display();

// Create the form deletion options
// Should probably go in the else if() statement above
$resetform = new course_reset_form();
if ($resetform->is_cancelled()) {
    
} else if ($data = $resetform->get_data()) {
    print_object($data);
}
$resetform->display();

echo $OUTPUT->footer();
