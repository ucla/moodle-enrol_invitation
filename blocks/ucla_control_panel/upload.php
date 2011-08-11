<?php

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/blocks/ucla_control_panel/upload_form.php');
require_once($CFG->dirroot . '/course/lib.php');

$course_id = required_param('course_id', PARAM_INT);
$type = required_param('type', PARAM_ALPHA);

$PAGE->set_pagelayout('admin');
$PAGE->set_url('/blocks/ucla_control_panel/upload.php', 
        array('course_id' => $course_id, 'type' => $type));

// Stolen from /course/edit.php
$course = $DB->get_record('course', array('id' => $course_id), 
    '*', MUST_EXIST);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('moodle/course:update', $context);
$PAGE->set_context($context);

$sections = get_all_sections($course_id);

$cpurl = new moodle_url('/blocks/ucla_control_panel/view.php',
        array('course_id' => $course_id));

// Type was not specified, or the form was cancelled...
if (!$type) {
    redirect($cpurl);
}

// Open all types of easy upload forms
$typelib = dirname(__FILE__) . '/upload_types/*.php';
$possibles = glob($typelib);

foreach ($possibles as $typefile) {
    require_once($typefile);
}

// Make sure that the class that we're looking for exists
$typeclass = 'easy_upload_' . $type . '_form';
if (!class_exists($typeclass)) {
    print_error('typenotexists');
}

// Create the upload form
$uploadform = new $typeclass(null, 
    array(
        'course' => $course, 
        'type' => $type, 
        'sections' => $sections
    ));

if ($uploadform->is_cancelled()) {
    redirect($cpurl);
} else if ($data = $uploadform->get_data()) {
    // Each form should process the data that is relevant.
    $uploadform->process_data($data); 
}

// Display the rest of the page
$site = get_site();

$title = get_string($typeclass, 'block_ucla_control_panel', $course->fullname);

$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

if (!isset($data) || !$data) {
    $uploadform->display();
} else {
    var_dump($data);
    // todo option where to redirect
}

echo $OUTPUT->footer();

// EOF
