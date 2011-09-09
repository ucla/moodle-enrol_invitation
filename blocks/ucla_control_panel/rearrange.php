<?php

/**
 *  Rearrange sections and course modules.
 **/

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

$thispath = '/blocks/ucla_control_panel';
require_once($CFG->dirroot . $thispath . '/rearrangelib.php');

global $CFG, $PAGE, $OUTPUT;

$course_id = required_param('course_id', PARAM_INT);

$course = $DB->get_record('course', array('id' => $course_id),
    '*', MUST_EXIST);

require_login($course, true);
$context = get_context_instance(CONTEXT_COURSE, $course_id);

// Make sure you can view this page.
require_capability('moodle/course:update', $context);
require_capability('moodle/course:manageactivities', $context);

// Set up the page.
$PAGE->set_context($context);

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);

$PAGE->set_url('/blocks/ucla_control_panel/rearrange.php', 
        array('course_id' => $course_id));

