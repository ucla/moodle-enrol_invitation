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

$courseid = required_param('course_id', PARAM_INT); // course ID
$subjarea = required_param('subj_area', PARAM_TEXT);// subject area

if (! $course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('coursemisconf');
}

$context = get_context_instance(CONTEXT_COURSE, $courseid);

// Initialize $PAGE
$PAGE->set_url('/blocks/ucla_subject_links/view.php', 
    array('course_id' => $courseid, 'subj_area' => $subjarea));

$page_title = $course->shortname.': '.get_string('pluginname',
    'block_ucla_subject_links');

$PAGE->set_context($context);
$PAGE->set_title($page_title);
$PAGE->set_course($course);
$PAGE->set_heading($course->fullname);

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-'.$course->format);

$location = $CFG->dirroot . '/blocks/ucla_subject_links/content/';

echo $OUTPUT->header();
 
include($location . $subjarea . '/index.htm');           
            
echo $OUTPUT->footer();

/** eof **/
