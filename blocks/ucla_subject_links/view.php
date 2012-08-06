<?php
/**
 *  The subject area link section, display the content of the htm file.
 **/

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $PAGE;

require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.
    '/blocks/ucla_subject_links/block_ucla_subject_links.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

// Note that the unhiding of the Announcements forum is handled in
// modules/email_students.php

// Note that any logic unrelated to the display of the control panel should 
// be handled within the module itself

$courseid = required_param('course_id', PARAM_INT); // course ID
$subjarea = required_param('subj_area', PARAM_TEXT);// subject area

if (! $course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}

require_login($course, true);
$context = get_context_instance(CONTEXT_COURSE, $courseid);

// Initialize $PAGE
$PAGE->set_url('/blocks/ucla_subject_links/view.php', 
    array('course_id' => $courseid));

$page_title = $course->shortname.': '.get_string('pluginname',
    'block_ucla_control_panel');

$PAGE->set_context($context);
$PAGE->set_title($page_title);

$PAGE->set_heading($course->fullname);

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-'.$course->format);

$location = $CFG->dirroot . '/blocks/ucla_subject_links/content/';
$subjname = block_ucla_subject_links::subject_exist($course, $location);

echo $OUTPUT->header();        
if ($subjname != NULL) {
    foreach ($subjname as $sub) {
        $sub = strtoupper($sub);
        include($location . $sub . '/index.htm');           
    }
}
            
echo $OUTPUT->footer();

/** eof **/

