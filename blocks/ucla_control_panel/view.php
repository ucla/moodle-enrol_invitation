<?php

require_once(dirname(__FILE__).'/../../config.php');
global $CFG;

require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
    
require_once($CFG->dirroot.
    '/blocks/ucla_control_panel/block_ucla_control_panel.php');

$course_id = required_param('courseid', PARAM_INT); // course ID
$edit = optional_param('edit', -1, PARAM_BOOL);

if (! $course = $DB->get_record('course', array('id'=>$course_id))) {
    print_error('coursemisconf');
}

require_login($course, true);
$context = get_context_instance(CONTEXT_COURSE, $course_id);

$cpb = new block_ucla_control_panel();
$elements = $cpb->load_cp_elements();

// Initialize $PAGE
$PAGE->set_url('/blocks/ucla_control_panel/view.php', 
    array('courseid' => $course_id));

$page_title = $course->shortname.': '.get_string('pluginname',
    'block_ucla_control_panel');

$PAGE->set_context($context);
$PAGE->set_title($page_title);

$PAGE->set_heading($course->fullname);

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);

if ($PAGE->user_allowed_editing()) {
    // Stolen from course/view.php
    if ($edit != -1 && confirm_sesskey()) {
        $USER->editing = $edit;

        if ($edit == 0 && !empty($USER->activitycopy) 
          && $USER->activitycoptycourse == $course->id) {
            $USER->activitycopy = false;
            $USER->activitycopycourse = NULL;
        }

        redirect($PAGE->url);
    }

    $buttons = $OUTPUT->edit_button(
        new moodle_url('/blocks/ucla_control_panel/view.php', array(
            'courseid' => $course_id))
        );

    $PAGE->set_button($buttons);
}

// using core renderer
echo $OUTPUT->header();

// This is actually printing out each section of the control panel
foreach ($elements as $section) {
    $contents = $section->control_panel_contents($course);

    if ($contents != '') {
        echo $OUTPUT->heading(get_string(get_class($section), 
            'block_ucla_control_panel'), 2, 'main copan-title');
        echo $OUTPUT->box($contents);
    }
}

echo $OUTPUT->footer();

/** eof **/
