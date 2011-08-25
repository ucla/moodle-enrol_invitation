<?php

require_once(dirname(__FILE__) . '/../../config.php');

require_once($CFG->dirroot . '/course/lib.php');

$thispath = '/blocks/ucla_control_panel';
require_once($CFG->dirroot . $thispath . '/upload_form.php');
require_once($CFG->dirroot . $thispath . '/uploadlib.php');

global $CFG, $PAGE, $OUTPUT;

$course_id = required_param('course_id', PARAM_INT);
$type = required_param('type', PARAM_ALPHA);

// Stolen from /course/edit.php
$course = $DB->get_record('course', array('id' => $course_id), 
    '*', MUST_EXIST);

require_login($course, true);
$context = get_context_instance(CONTEXT_COURSE, $course_id);

require_capability('moodle/course:update', $context);
require_capability('moodle/course:manageactivities', $context);

$PAGE->set_context($context);

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);

$PAGE->set_url('/blocks/ucla_control_panel/upload.php', 
        array('course_id' => $course_id, 'type' => $type));

$sections = get_all_sections($course_id);

$sectionnames = array();
foreach ($sections as $section) {
    $sectionnames[] = get_section_name($course, $section);
}

$cpurl = new moodle_url('/blocks/ucla_control_panel/view.php',
        array('course_id' => $course_id));

$courseurl = new moodle_url('/course/view.php',
        array('id' => $course_id));

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

    $coursemoduleid = add_course_module($newcm);
    if (!$coursemoduleid) {
        print_error('cannotaddnewmodule');
    }

    $data->coursemodule = $coursemoduleid;

    $instanceid = $addinstancefn($data, $uploadform);

    if (!$instanceid || !is_number($instanceid)) {
        // "Undo everything we can"
        delete_context(CONTEXT_MODULE, $coursemoduleid);

        $DB->delete_records('course_modules', array('id' => $coursemoduleid));

        print_error('cannotaddnewmodule', '', 
            'view.php?id=' . $course->id . '#section-' . $data->section,
            $coursemoduleid);
    }

    $sectionid = add_mod_to_section($data);

    $DB->set_field('course_modules', 'instance', $instanceid,
        array('id' => $coursemoduleid));

    rebuild_course_cache($course_id);
}

// Display the rest of the page
$title = get_string($typeclass, 'block_ucla_control_panel', $course->fullname);

$PAGE->set_title($title);
$PAGE->set_heading($title);

// Print out the header and blocks
echo $OUTPUT->header();

// Print out a heading
echo $OUTPUT->heading($title);

if (!isset($data) || !$data) {
    $uploadform->display();
} else {
    $message = get_string('successfuladd', 'block_ucla_control_panel', $type);

    $params = array('id' => $course_id);

    // These following lines could be extracted out into a function
    $key = 'topic';
    $format = $course->format;
    $fn = 'callback_' . $format . '_request_key';
    if (function_exists($fn)) {
        $key = $fn();
    }

    $courseurl = new moodle_url('/course/view.php', $params);
    $courseret = new single_button($courseurl, get_string('returntocourse',
            'block_ucla_control_panel'), 'get');

    $params[$key] = $sectionid;

    $secturl = new moodle_url('/course/view.php', $params);
    $sectret = new single_button($secturl, get_string('returntosection', 
            'block_ucla_control_panel'), 'get');

    echo $OUTPUT->confirm($message, $sectret, $courseret);
}

echo $OUTPUT->footer();

// EOF
