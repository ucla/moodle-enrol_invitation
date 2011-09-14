<?php

/**
 *  Rearrange sections and course modules.
 **/

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

$thispath = '/blocks/ucla_rearrange';
require_once($CFG->dirroot . $thispath . '/block_ucla_rearrange.php');

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

$PAGE->set_url('/blocks/ucla_rearrange/rearrange.php', 
        array('course_id' => $course_id));

$sections = get_all_sections($course_id);
$modinfo =& get_fast_modinfo($course);
get_all_mods($course_id, $mods, $modnames, $modnamesplural, $modnamesused);

$sectionnodeshtml = block_ucla_rearrange::get_section_modules_rendered(
    $course_id, $sections, $mods, $modinfo
);

// This enables nested sortables for all objects in the page with the class
// of "nested-sortables"
block_ucla_rearrange::setup_nested_sortable_js($sectionnodeshtml, 
    '.nested-sortables');

var_dump($sectionnodeshtml);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('rearrange_sections', 
    'block_ucla_rearrange'));

echo $OUTPUT->footer();
// EOF
