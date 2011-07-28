<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $PAGE;

require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.
    '/blocks/ucla_control_panel/block_ucla_control_panel.php');
require_once($CFG->dirroot.
    '/blocks/ucla_control_panel/ucla_cp_renderer.php');

// Note that the unhiding of the Announcements forum is handled in
// modules/email_students.php

// Note that any logic unrelated to the display of the control panel should 
// be handled within the module itself

$course_id = required_param('courseid', PARAM_INT); // course ID
$module_view = optional_param('module', 'default', PARAM_ALPHANUMEXT);
$edit = optional_param('edit', -1, PARAM_BOOL);

if (! $course = $DB->get_record('course', array('id' => $course_id))) {
    print_error('coursemisconf');
}

require_login($course, true);
$context = get_context_instance(CONTEXT_COURSE, $course_id);

// Initialize $PAGE
$PAGE->set_url('/blocks/ucla_control_panel/view.php', 
    array('courseid' => $course_id));

$page_title = $course->shortname.': '.get_string('pluginname',
    'block_ucla_control_panel');

$PAGE->set_context($context);
$PAGE->set_title($page_title);

$PAGE->set_heading($course->fullname);

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-'.$course->format);

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

// Get all the elements, unfortunately, this is where we check whether
// we are supposed to display the elements at all.
$elements = block_ucla_control_panel::load_cp_elements($course, $context);

// These are the possible sets of elements that we can permute
$views = block_ucla_control_panel::load_cp_views();

// So here we need to check which tabs we can actually display
$tabs = array();
foreach ($views as $view => $contents) {
    $view_valid = false;
    foreach ($contents as $content) {
        if (isset($elements[$content])) {
            $view_valid = true;
        }
    }

    if ($view_valid) {
        $tabs[] = new tabobject($view, new moodle_url($PAGE->url,
            array('module' => $view)), get_string($view, 
            'block_ucla_control_panel'));
    }
}

print_tabs(array($tabs), $module_view);

if ($course->format != 'ucla') {
    echo $OUTPUT->box(get_string('formatincompatible', 
        'block_ucla_control_panel'));
}

// This has to be called manually... 
$PAGE->navigation->initialise();

// This is for showing a notice if there are no commands availble
$no_elements = true;

// This is actually printing out each section of the control panel
foreach ($elements as $section_title => $section_contents) {
    if (!isset($views[$module_view])
     || !in_array($section_title, $views[$module_view])) {
        continue;
    }

    $no_elements = false;

    echo $OUTPUT->heading(get_string($section_title,
            'block_ucla_control_panel'), 2, 'main copan-title');
    
    if ($section_title == 'ucla_cp_mod_common') {
        $section_contents = ucla_cp_renderer::get_content_array(
            $section_contents, 2);

        echo ucla_cp_renderer::control_panel_contents($section_contents, 
            false, 'row', 'general_icon_link');
        
        continue;
    }

    echo ucla_cp_renderer::control_panel_contents($section_contents, true);
}

if ($no_elements) {
    echo $OUTPUT->box(get_string('nocommands', 'block_ucla_control_panel', 
        $module_view));
}


echo $OUTPUT->footer();

/** eof **/
