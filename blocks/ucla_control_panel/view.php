<?php
/**
 *  The control panel section, a collection of several tools.
 **/

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $PAGE;

require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.
    '/blocks/ucla_control_panel/block_ucla_control_panel.php');
require_once($CFG->dirroot.
    '/blocks/ucla_control_panel/ucla_cp_renderer.php');
require_once($CFG->dirroot.
    '/blocks/ucla_control_panel/modules/ucla_cp_myucla_renderer.php');

// Note that the unhiding of the Announcements forum is handled in
// modules/email_students.php

// Note that any logic unrelated to the display of the control panel should 
// be handled within the module itself

$course_id = required_param('course_id', PARAM_INT); // course ID
$module_view = optional_param('module', 'default', PARAM_ALPHANUMEXT);
$edit = optional_param('edit', null, PARAM_BOOL);

if (! $course = $DB->get_record('course', array('id' => $course_id))) {
    print_error('coursemisconf');
}

require_login($course, true);
$context = get_context_instance(CONTEXT_COURSE, $course_id);

// Initialize $PAGE
$PAGE->set_url('/blocks/ucla_control_panel/view.php', 
    array('course_id' => $course_id));

$page_title = $course->shortname.': '.get_string('pluginname',
    'block_ucla_control_panel');

$PAGE->set_context($context);
$PAGE->set_title($page_title);

$PAGE->set_heading($course->fullname);

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-'.$course->format);

if ($PAGE->user_allowed_editing()) {
    // Stolen from course/view.php
    if ($edit != null && confirm_sesskey()) {
        $USER->editing = $edit;

        if ($edit == 0 && !empty($USER->activitycopy) 
          && $USER->activitycopycourse == $course->id) {
            $USER->activitycopy = false;
            $USER->activitycopycourse = NULL;
        }

        redirect($PAGE->url);
    }

    $buttons = $OUTPUT->edit_button(
        new moodle_url(
            '/blocks/ucla_control_panel/view.php', 
            array('course_id' => $course_id)
        )
    );

    $PAGE->set_button($buttons);
}

// Get all the elements, unfortunately, this is where we check whether
// we are supposed to display the elements at all.
$elements = block_ucla_control_panel::load_cp_elements($course, $context);
// We do this here because of a hack for stylesheets.

// using core renderer
echo $OUTPUT->header();
echo '<div class="cpanel-wrapper">';
echo '<div class="cpheading"><h1>Control Panel</h1></div>';


// So here we need to check which tabs we can actually display
$tabs = array();
foreach ($elements as $view => $contents) {
    $tabs[] = new tabobject($view, new moodle_url(
            $PAGE->url,
            array('module' => $view)
        ), get_string(
            $view, 
            'block_ucla_control_panel'
        ));
}

//try set div here!
echo '<div class ="thetabs">';
print_tabs(array($tabs), $module_view);

echo "</div>";

if ($course->format != 'ucla') {
    echo $OUTPUT->box(get_string('formatincompatible', 
        'block_ucla_control_panel'));
}

// This has to be called manually... 
$PAGE->navigation->initialise();

// This is for showing a notice if there are no commands availble
$no_elements = true;

$sm = get_string_manager();
// This is actually printing out each section of the control panel
foreach ($elements as $view => $section_contents) {
    // TODO expand this or optimize this
    if ($module_view != $view) {
        continue;
    }

    $no_elements = false;
 
    foreach ($section_contents as $tags => $modules) {
       
        // Is this group of stuff from elsewhere?
        if ($sm->string_exists($tags, 'block_ucla_control_panel')) {
            $viewstring = get_string($tags, 'block_ucla_control_panel');
        } else {
            // This is for other blocks
            $altblockstr = $tags . '_cp_viewtitle';
            if ($sm->string_exists($altblockstr, $tags)) {
                $viewstring = get_string($altblockstr, $tags);
            } else {
                $viewstring = get_string('unknowntag', 
                    'block_ucla_control_panel');
            }
        }

        echo $OUTPUT->heading($viewstring, 2, 'main copan-title');
  
        if ($tags == 'ucla_cp_mod_common') {
            $section_contents = ucla_cp_renderer::get_content_array(
                $modules, 2
            );

            echo ucla_cp_renderer::control_panel_contents
($section_contents, 
                false, 'row', 'general_icon_link');
        } else if ($tags == 'ucla_cp_mod_myucla') {
            echo ucla_cp_myucla_row_renderer::control_panel_contents($modules);             
        } else {
            $altrend = $tags . '_cp_render';
            if (class_exists($altrend) && method_exists($altrend, 
                    'render_cp_items')) {
                echo $altrend::render_cp_items($modules);
            } else {
                 echo ucla_cp_renderer::control_panel_contents($modules, true);
            }
    
        }
    }

}

if ($no_elements) {
    echo $OUTPUT->box(get_string('nocommands', 'block_ucla_control_panel', 
        $module_view));
}

//this is temporary fix for the bottom border

echo '</div>';
echo $OUTPUT->footer();

/** eof **/
