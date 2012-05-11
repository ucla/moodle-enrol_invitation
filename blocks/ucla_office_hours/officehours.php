<?php

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $PAGE;

require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.
    '/blocks/ucla_office_hours/block_ucla_office_hours.php');
require_once($CFG->dirroot.
        '/blocks/ucla_office_hours/update_officehours_form.php');

require_once($CFG->dirroot . '/local/ucla/lib.php');

$course_id = required_param('course_id', PARAM_INT); 
$edit_id = required_param('edit_id', PARAM_INT); 
$user_edit_id = $DB->get_record('user', array('id'=>$edit_id), '*', MUST_EXIST);

if (! $course = $DB->get_record('course', array('id' => $course_id))) {
    print_error('coursemisconf');
}

require_login($course, true);
$context = get_context_instance(CONTEXT_COURSE, $course_id);

$PAGE->set_url('/blocks/ucla_office_hours/officehours.php', 
    array('course_id' => $course_id));

$page_title = $course->shortname.': '.get_string('pluginname',
    'block_ucla_office_hours');

$PAGE->set_context($context);
$PAGE->set_title($page_title);

$PAGE->set_heading($course->fullname);

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-'.$course->format);

set_editing_mode_button();

echo $OUTPUT->header();

$PAGE->navigation->initialise();

$tform = new officehours_form(NULL, array('edit' => $user_edit_id));
$tform->display();

echo $OUTPUT->footer();

//EOF
