<?php

require_once(dirname(__FILE__).'/../../config.php');
global $CFG;

require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
    
require_once($CFG->dirroot.
    '/blocks/ucla_control_panel/block_ucla_control_panel.php');

$course_id = required_param('courseid', PARAM_INT); // course ID

if (! $course = $DB->get_record('course', array('id'=>$course_id))) {
    print_error('coursemisconf');
}

$cpb = new block_ucla_control_panel();

$elements = $cpb->load_cp_elements();

// Initialize $PAGE
$PAGE->set_url('/blocks/ucla_control_panel/view.php', 
    array('courseid' => $course_id));

$context = get_context_instance(CONTEXT_SYSTEM);

$page_title = $course->shortname.': '.get_string('pluginname',
    'block_ucla_control_panel');

$PAGE->set_context($context);
$PAGE->set_title($page_title);

$PAGE->set_heading($course->fullname);
$PAGE->set_pagetype('course-*' . $course->format);
$PAGE->set_pagelayout('course');

if ($course_id == SITEID) {
    $PAGE->navbar->add(get_string('pluginname','ucla_links'));
} else {
    $countcategories = $DB->count_records('course_categories');
    if ($countcategories > 1 
      || ($countcategories == 1 
      && $DB->count_records('course') > 200)) {
        $PAGE->navbar->add(get_string('categories'));
    } else {
        $PAGE->navbar->add(get_string('courses'), 
            new moodle_url('/course/category.php?id='.$course->category));
        $PAGE->navbar->add($course->shortname, 
            new moodle_url('/course/view.php?id='.$course_id));
        $PAGE->navbar->add($page_title);
    }
}

// using core renderer
echo $OUTPUT->header();

foreach ($elements as $section) {
    echo $OUTPUT->box($section->control_panel_contents());
}

echo $OUTPUT->footer();

/** eof **/
