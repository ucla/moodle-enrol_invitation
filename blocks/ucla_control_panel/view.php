<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir.'/blocklib.php');

$course_id = required_param('courseid', PARAM_INT); // course ID

if (! $course = $DB->get_record('course', array('id'=>$course_id))) {
    print_error('coursemisconf');
}

// Initialize $PAGE
$PAGE->set_url('/blocks/ucla_control_panel/view.php', array('courseid' => $courseid));

$context = get_context_instance(CONTEXT_SYSTEM);

$PAGE->set_context($context);
$PAGE->set_title($course->shortname.': '.get_string('pluginname', 'blocks_ucla_control_panel'));

$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-*' . $course->format);
$PAGE->set_pagelayout('course');

if ($courseid == SITEID) {
    $PAGE->navbar->add(get_string('pluginname','ucla_links'));
} else {
    $countcategories = $DB->count_records('course_categories');
    if ($countcategories > 1 || ($countcategories == 1 && $DB->count_records('course') > 200)) {
        $PAGE->navbar->add(get_string('categories'));
    } else {
        $PAGE->navbar->add(get_string('courses'), new moodle_url('/course/category.php?id='.$course->category));
        $PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php?id='.$course_id));
        $PAGE->navbar->add(get_string('pluginname','block_ucla_control_panel'));
    }
}

// using core renderer
echo $OUTPUT->header();

echo $OUTPUT->box("Stuff");

echo $OUTPUT->footer();

/** eof
