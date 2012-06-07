<?php

/**
 *  Rearrange sections and course modules.
 **/

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/format/ucla/ucla_course_prefs.class.php');
$thispath = '/blocks/ucla_modify_coursemenu';
require_once($CFG->dirroot . $thispath . '/block_ucla_modify_coursemenu.php');
require_once($CFG->dirroot . $thispath . '/modify_coursemenu_form.php');

require_once($CFG->dirroot . '/local/ucla/lib.php');

global $CFG, $PAGE, $OUTPUT;

$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course, true);
$context = get_context_instance(CONTEXT_COURSE, $courseid);

// Make sure you can view this page.
require_capability('moodle/course:update', $context);
require_capability('moodle/course:manageactivities', $context);

// set editing url to be topic or default page
$sections = get_all_sections($courseid);
foreach ($sections as $k => $section) {
    $section->name = get_section_name($course, $section);
    $sections[$k] = $section;
}

$maintableid = block_ucla_modify_coursemenu::maintable_domnode;

$tablestructure = new html_table();
$tablestructure->id = $maintableid;

// Basics
$ts_head = array('', 'section', 'title', 'hide', 'delete');

// This is an add-on
$ts_head[] = 'landing_page';

$ts_headstrs = array();
foreach ($ts_head as $ts_header) {
    if (!empty($ts_header)) {
        $ts_header = get_string($ts_header, 'block_ucla_modify_coursemenu');
    }

    $ts_headstrs[] = $ts_header;
}

$tablestructure->head = $ts_headstrs;

// Set up the page.
$PAGE->set_context($context);

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_url('/blocks/ucla_modify_coursemenu/modify_coursemenu.php', 
        array('courseid' => $courseid));

$PAGE->requires->js('/blocks/ucla_modify_coursemenu/js/jquery-1.3.2.min.js');
$PAGE->requires->js('/blocks/ucla_modify_coursemenu/js/jquery.tablednd_0_5.js');
$PAGE->requires->js('/blocks/ucla_modify_coursemenu/modify_coursemenu.js');

// Provide format information to js
$courseformat = $course->format;
$format_compstr = 'format_' . $courseformat;
$PAGE->requires->string_for_js('section0name', $format_compstr);
$PAGE->requires->string_for_js('section0name', $format_compstr);
$PAGE->requires->string_for_js('newsection', 'block_ucla_modify_coursemenu');
$PAGE->requires->string_for_js('new_sectnum', 'block_ucla_modify_coursemenu');
if ($courseformat == 'ucla') {
    $PAGE->requires->string_for_js('show_all', $format_compstr);
    block_ucla_modify_coursemenu::js_init_code_helper(
            'showallsection', UCLA_FORMAT_DISPLAY_ALL
        );
}

block_ucla_modify_coursemenu::many_js_init_code_helpers(array(
        'course_format'  => $format_compstr,
        'table_id'       => $maintableid,
        'primary_id'     => block_ucla_modify_coursemenu::primary_domnode,
        'newsections_id' => block_ucla_modify_coursemenu::newnodes_domnode,
        'landingpage_id' => 
            block_ucla_modify_coursemenu::landingpage_domnode,
        'sectionsorder_id' => 
            block_ucla_modify_coursemenu::sectionsorder_domnode,
        'serialized_id' => 
            block_ucla_modify_coursemenu::serialized_domnode,
        'sectiondata' => $sections
    ));

$PAGE->requires->js_init_code('M.block_ucla_modify_coursemenu.initialize()');

$courseviewurl = new moodle_url('/course/view.php', array('id' => $courseid));
set_editing_mode_button($courseviewurl);

$modinfo =& get_fast_modinfo($course);

$course_preferences = new ucla_course_prefs($courseid);
$landing_page = $course_preferences->get_preference('landing_page', false);
if ($landing_page === false) {
    $landing_page = 0;
} 

$modify_coursemenu_form = new ucla_modify_coursemenu_form(
    null,
    array(
            'courseid' => $courseid, 
            'sections'  => $sections,
            'landing_page' => $landing_page
        ),
    'post',
    '',
    array('class' => 'ucla_modify_coursemenu_form')
);

$redirector = null;

//extract the data from the form and update the database
if ($modify_coursemenu_form->is_cancelled()) {
    $redirector = $courseviewurl;
} else if ($data = $modify_coursemenu_form->get_data()) {
    // TODO see if some of the fields can be parsed from within the MForm
    parse_str($data->serialized, $unserialized);
    parse_str($data->sectionsorder, $sectionorderparsed);

    // TODO make it consistent IN CODE how section id's are generated
    $sectionorder = array();
    foreach ($sectionorderparsed['sections-order'] as $k => $sectionid) {
        $sectnum = str_replace('section-', '', $sectionid);
        if ($sectnum == UCLA_FORMAT_DISPLAY_ALL 
                || $sectnum == 0) {
            continue;
        }

        $sectionorder[$k] = $sectnum;
    }

    // TODO make it consistent IN CODE how all these fields are generated
    foreach ($unserialized as $fieldid => $value) {
        
    }
    
    // update the section 
    // names, visibility

    // reorder 

    // delete 
    // set landing page
    $course_preferences->set_preference('landing_page', $data->landingpage);
    $course_preferences->commit();
    
    $redirector = new moodle_url(
            '/blocks/ucla_modify_coursemenu/modify_coursemenu.php',
            array('courseid' => $courseid)
        );
}

if ($redirector !== null) {
    redirect($redirector);
}

$restr = get_string('ucla_modify_course_menu', 'block_ucla_modify_coursemenu');
$restrc = "$restr: {$course->shortname}";

//$PAGE->requires->css('/blocks/ucla_modify_coursemenu/styles.css');
$PAGE->set_title($restrc);
$PAGE->set_heading($restrc);

echo $OUTPUT->header();
echo $OUTPUT->heading($restr, 2, 'headingblock');

echo html_writer::table($tablestructure);
$modify_coursemenu_form->display();
 
echo $OUTPUT->footer();

