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
require_capability('moodle/course:manageactivities', $context);

$PAGE->set_context($context);

$sections = get_all_sections($course_id);

$sectionnames = array();
foreach ($sections as $section) {
    $sectionnames[] = get_section_name($course, $section);
}

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
        'sectionnames' => $sectionnames
    ));

if ($uploadform->is_cancelled()) {
    redirect($cpurl);
} else if ($data = $uploadform->get_data()) {
    // Pilfered parts from /course/modedit.php
    $modulename = $data->modulename;

    $moddir = $CFG->dirroot . '/mod/' . $modulename;
    $modform = $moddir . '/mod_form.php';
    if (file_exists($modform)) {
        include_once($modform);
    } else {
        print_error('noformdesc');
    }

    $modlib  = $moddir . '/lib.php';
    if (file_exists($modlib)) {
        include_once($modlib);
    } else {
        print_error('modulemissingcode', '', '', $modlib);
    }

    $module = $DB->get_record('modules', array('name' => $modulename),
            '*', MUST_EXIST);

    if (!course_allowed_module($course, $modulename)) {
        print_error('moduledisable');
    }

    $addinstancefn = $modulename . '_add_instance';
    
    $newcm = new stdclass();
    $newcm->course = $course->id;
    $newcm->module = $module->id;
    $newcm->instance = 0;
   
    // TODO Handle some publicprivate here at one point
    $newcm->visible = 1;

    $data->coursemodule = add_course_module($newcm);

    $returnval = $addinstancefn($data, $uploadform);
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
    die('data has been processed');
}

echo $OUTPUT->footer();

// EOF
