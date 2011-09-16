<?php

/**
 *  Rearrange sections and course modules.
 **/

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

$thispath = '/blocks/ucla_rearrange';
require_once($CFG->dirroot . $thispath . '/block_ucla_rearrange.php');
require_once($CFG->dirroot . $thispath . '/rearrange_form.php');

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

$sectionids = array();
foreach ($sections as $section) {
    $sectionids[$section->section] = $section->section;
}

$modinfo =& get_fast_modinfo($course);
get_all_mods($course_id, $mods, $modnames, $modnamesplural, $modnamesused);

$sectionnodeshtml = block_ucla_rearrange::get_section_modules_rendered(
    $course_id, $sections, $mods, $modinfo
);

$sectionlist = block_ucla_rearrange::sectionlist;

// Consolidate into a single thingee
$sectionshtml = html_writer::start_tag(
    'ul', 
    array(
        'class' => block_ucla_rearrange::sectionlistclass,
        'id' => $sectionlist
    )
);

foreach ($sectionnodeshtml as $section => $snh) {
    $sectionshtml .= html_writer::tag(
        'li', 
        html_writer::tag(
            'div', 
            $snh, 
            array('class' => 'sub-container')
        ),
        array(
            'class' => block_ucla_rearrange::sectionitem,
            'id' => 's-section-' . $section
        )
    );
}

$sectionshtml .= html_writer::end_tag('ul');

// Here is the primary setup for sortables
$customvars = array(
    'containerjq' => '#' . block_ucla_rearrange::primary_domnode
);

// This enables nested sortables for all objects in the page with the class
// of "nested-sortables"
block_ucla_rearrange::setup_nested_sortable_js($sectionshtml, 
    '.' . block_ucla_rearrange::pagelistclass, $customvars);

// All prepped, now we need to add the actual rearrange form
// The form is useful since it lets us maintain serialized data and
// helps us filter stuff.
$rearrangeform = new ucla_rearrange_form(
    null,
    array(
        'course_id' => $course_id, 
        'sections' => $sectionids
    )
);

if ($data = $rearrangeform->get_data()) {
    $sectionnodes = array();

    // Split and sort the input data
    foreach ($sectionids as $section) {
        $field = 'serialized-section-' . $section;

        if (isset($data->$field)) {
            $sectionnodes[$section] = block_ucla_rearrange::parse_serial(
                $data->$field
            );

        } else {
            print_error(get_string('error_missing_section',
                'block_ucla_rearrange'));
        }
    }

    // Get the ordering of the sections
    if (isset($data->serialized)) {
        $uncleansectionorder 
            = block_ucla_rearrange::parse_serial($data->serialized);

        $sectionorder 
            = block_ucla_rearrange::clean_section_order(
                reset($uncleansectionorder)
            );
    } else {
        print_error(get_string('error_missing_section_ordering',
            'block_ucla_rearrange'));
    }

    // Redirect eventually?
    $sectioncontents = array();

    // TODO maybe integrate this loop with the one above?
    foreach ($sectionnodes as $section => $nodes) {
        $flattened = array();

        if (!empty($nodes)) {
            // We cannot use [0] notation because we need the first and only
            if (count($nodes) != 1) {
                // We need to send an error report here.
                print_error(get_string('error_multiple_nodes',
                    'block_ucla_rearrange'));
            }

            $flattened = modnode::flatten(reset($nodes));
        }

        $sectioncontents[$section] = $flattened;
    }

    // We're going to skip the API calls because it uses too many DBQ's
    block_ucla_rearrange::move_modules_section_bulk($sectioncontents, 
        $sectionorder, $sections);

    // Now we need to swap all the contents in each section...
    rebuild_course_cache($course_id);
} 

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('rearrange_sections', 
    'block_ucla_rearrange'));

if ($data == false) {
    $rearrangeform->display();
    $PAGE->requires->js_init_code(
        'M.block_ucla_rearrange.initialize_rearrange_tool()'
    );
}

echo $OUTPUT->footer();

// EOF
