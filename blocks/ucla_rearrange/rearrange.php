<?php
/**
 *  Rearrange sections and course modules.
 */
require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

// tool requires UCLA format
require_once($CFG->dirroot . '/course/format/ucla/lib.php');

$thispath = '/blocks/ucla_rearrange';
require_once($CFG->dirroot . $thispath . '/block_ucla_rearrange.php');
require_once($CFG->dirroot . $thispath . '/rearrange_form.php');

require_once($CFG->dirroot . '/local/ucla/lib.php');

global $CFG, $PAGE, $OUTPUT;

$course_id = required_param('course_id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);

require_login($course, true);
$context = get_context_instance(CONTEXT_COURSE, $course_id);

// Make sure you can view this page.
require_capability('moodle/course:update', $context);
require_capability('moodle/course:manageactivities', $context);

// see what section we are on
$topic = ucla_format_figure_section($course);
$topic_num = $topic[1];

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
    //CCLE-2930:rearrange tool now shows correct sections by limitimg it
    //with the course numsections.
    if ($section->section > $course->numsections) {
	unset($sections[$section->section]);
	continue;
    }
    $sid = $section->id;
    $sectids[$sid] = $sid;
    $sectnums[$sid] = $section->section;
    $sectionnames[$sid] = get_section_name($course, $section);
    $sectionvisibility[$sid] = $section->visible;
}

$modinfo = & get_fast_modinfo($course);
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

// Make the expand/collapse button
$expandtext = get_string('sectionexpand', 'block_ucla_rearrange');
$collaptext = get_string('sectioncollapse', 'block_ucla_rearrange');
$expand_button = html_writer::tag('div', $collaptext,
                array('class' => 'expand-button'));

$sectionzero = false;

// Hack a wrap around each set of HTML to generate the section wrappers
foreach ($sectionnodeshtml as $section => $snh) {
    $siattr = array(
        'id' => 's-section-' . $section,
        'class' => 'section-item'
    );

    $sectnum = $sectnums[$section];

    if ($sectnum != 0) {
        $siattr['class'] .= ' ' . block_ucla_rearrange::sectionitem;
    } else {
        $sectionzero = $section;
    }

    $is_hidden_text = '';
    if (!$sectionvisibility[$section]) {
        $is_hidden_text = ' ' . html_writer::tag('span',
                        '(' . get_string('hidden', 'calendar') . ')',
                        array('class' => block_ucla_rearrange::hiddenclass));
    }

    $sectionshtml .= html_writer::tag(
                    'li',
                    html_writer::tag(
                            'div',
                            html_writer::tag('span',
                                    $sectionnames[$section] . $is_hidden_text .
                                    $expand_button,
                                    array(
                                'class' => 'sectiontitle'
                                    )
                            ) . $snh, array('class' => 'sub-container')
                    ), $siattr
    );
}

if ($sectionzero === false) {
    debugging(get_string('missing_section_zero', 'block_ucla_rearrange'));
}

$sectionshtml .= html_writer::end_tag('ul');

// Here is the primary setup for sortables
$customvars = array(
    'containerjq' => '#' . block_ucla_rearrange::primary_domnode,
    'expandtext' => $expandtext,
    'collapsetext' => $collaptext,
    'expandalltext' => get_string('allexpand', 'block_ucla_rearrange'),
    'collapsealltext' => get_string('allcollapse', 'block_ucla_rearrange'),
    'expandalljq' => '.expandall'
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
                    'sections' => $sectids,
                    'topic' => $topic_num
                ),
                'post',
                '',
                array('class' => 'ucla_rearrange_form')
);

if ($data = $rearrangeform->get_data()) {
    $sectionnodes = array();

    // Split and sort the input data
    foreach ($sectids as $section) {
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

        // here reset the 0th
        $sectionorder = array_merge(array("$sectionzero"), $sectionorder);

        // Flip the keys.
        $sectiontranslation = array();
        foreach ($sectionorder as $sectnum => $sectid) {
            $sectiontranslation[$sectid] = $sectnum;
        }

        unset($sectionorder);
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
            $sectiontranslation);

    // Now we need to swap all the contents in each section...
    rebuild_course_cache($course_id);
}

// TODO put a title

$restr = get_string('rearrange_sections', 'block_ucla_rearrange');
$restrc = "$restr: {$course->shortname}";

$PAGE->set_title($restrc);
$PAGE->set_heading($restrc);

echo $OUTPUT->header();
echo $OUTPUT->heading($restr, 2, 'headingblock');

if ($data != false) {

    // allow user to either return to section they were on or go to course page
    // if format is UCLA, then this UCLA_FORMAT_DISPLAY_LANDING should exist
    $params = array('id' => $course_id);
    if (defined('UCLA_FORMAT_DISPLAY_LANDING')) {
        $params['topic'] = UCLA_FORMAT_DISPLAY_LANDING;
    }
    $courseurl = new moodle_url('/course/view.php', $params);
    $courseret = new single_button($courseurl, get_string('returntocourse',
                            'block_ucla_rearrange'), 'get');

    $secturl = new moodle_url('/course/view.php',
                    array('id' => $course_id, 'topic' => $topic_num));
    $sectret = new single_button($secturl, get_string('returntosection',
                            'block_ucla_rearrange'), 'get');

    echo $OUTPUT->confirm(get_string('rearrange_success', 'block_ucla_rearrange'),
            $sectret, $courseret);
} else {
    /* for topic < 0, the secid doesnt matter because we will expand all
     * However, if will give warning if we use $secid = ($sections[$topic]->id);
     * as there is no secid for topic < 0.
     */
    if ($topic_num < 0) {
        $secid = ($sections[0]->id);
    } else {
        $secid = ($sections[$topic_num]->id);
    }

    $rearrangeform->display();
    $PAGE->requires->js_init_code(
            "M.block_ucla_rearrange.initialize_rearrange_tool('$topic_num', '$secid')"
    );
}

echo $OUTPUT->footer();

// EOF
