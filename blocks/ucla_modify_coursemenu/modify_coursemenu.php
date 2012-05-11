<?php

/**
 *  Rearrange sections and course modules.
 **/

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

$thispath = '/blocks/ucla_rearrange';
require_once($CFG->dirroot . $thispath . '/block_ucla_rearrange.php');
require_once($CFG->dirroot . $thispath . '/rearrange_form.php');

require_once($CFG->dirroot . '/local/ucla/lib.php');

global $CFG, $PAGE, $OUTPUT;

$course_id = required_param('course_id', PARAM_INT);
$topic_num = optional_param('topic', null, PARAM_INT);

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
        array('course_id' => $course_id, 'topic' => $topic_num));

// set editing url to be topic or default page
$go_back_url = new moodle_url('/course/view.php', 
        array('id' => $course_id, 'topic' => $topic_num));
set_editing_mode_button($go_back_url);

$sections = get_all_sections($course_id);

$sectnums = array();
$sectionnames = array();
$sectionvisibility = array();
foreach ($sections as $section) {
    $sid = $section->id;
    $sectids[$sid] = $sid;
    $sectnums[$sid] = $section->section;
    $sectionnames[$sid] = get_section_name($course, $section);
    $sectionvisibility[$sid] = $section->visible;
}

$modinfo =& get_fast_modinfo($course);
get_all_mods($course_id, $mods, $modnames, $modnamesplural, $modnamesused);

$sectionnodeshtml = block_ucla_rearrange::get_section_modules_rendered(
    $course_id, $sections, $mods, $modinfo
);

// TODO put a title

$restr = get_string('ucla_modify_course_menu', 'block_ucla_modify_coursemenu');
$restrc = "$restr: {$course->shortname}";

$PAGE->set_title($restrc);
$PAGE->set_heading($restrc);

echo $OUTPUT->header();
echo $OUTPUT->heading($restr, 2, 'headingblock');

echo $OUTPUT->footer();

?>