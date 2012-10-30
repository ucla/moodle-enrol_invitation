<?php
require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $PAGE;

require_once($CFG->dirroot . '/blocks/ucla_alert/locallib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/format/ucla/lib.php');

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($courseid);
$context = get_context_instance(CONTEXT_COURSE, $courseid);

// Make sure you can view this page.
require_capability('moodle/course:update', $context);

// Set up the page.
$PAGE->set_context($context);

if($courseid == SITEID) {
    $PAGE->set_course($SITE);
    $PAGE->set_pagetype('site-index');
    $PAGE->set_pagelayout('coursecategory');
} else {
    $PAGE->set_pagelayout('course');
    $PAGE->set_pagetype('course-view-' . $course->format);
}

$PAGE->set_url('/blocks/ucla_alert/edit.php', array('id' => $courseid));

// Keep the site 'edit' button
$go_back_url = new moodle_url('/course/view.php',
                array('id' => $courseid));
set_editing_mode_button($go_back_url);

$PAGE->set_title(get_string('edit_alert_heading', 'block_ucla_alert'));
$PAGE->navbar->add(get_string('edit_alert_heading', 'block_ucla_alert'));

// I have no idea when this is used...
$PAGE->set_heading($SITE->fullname);

// We load a separate alert block for the main site
if(intval($courseid) == SITEID) {
    $alertedit = new ucla_alert_block_editable_site($courseid);
} else {
    if($DB->record_exists(ucla_alert::DB_TABLE, array('courseid' => $courseid))) {
        $alertedit = new ucla_alert_block_editable($courseid);    
    } else {
        print_error('alert_block_dne', 'block_ucla_alert');
    }
}

if($alertedit) {
    // Load YUI script
    $PAGE->requires->js('/blocks/ucla_alert/alert.js');
    $PAGE->requires->js_init_call('M.alert_block.init', array($courseid));
}

echo $OUTPUT->header();
echo $alertedit->render();
echo $OUTPUT->footer();
