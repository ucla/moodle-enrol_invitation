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

// Make sure you can view this page.
require_capability('moodle/course:update', $context);
require_capability('moodle/course:manageactivities', $context);

// Set up the page.
$PAGE->set_context($context);

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);

$PAGE->set_url('/blocks/ucla_control_panel/upload.php', 
        array('course_id' => $course_id, 'type' => $type));

// Get all the informations for the form.
$modinfo =& get_fast_modinfo($course);
get_all_mods($course_id, $mods, $modnames, $modnamesplural, $modnamesused);

$sections = get_all_sections($course_id);

$sectionnames = array();
$sequences = array();
foreach ($sections as $section) {
    $sectionnames[] = get_section_name($course, $section);
    $sequences[$section->section] = $section->sequence;
}

// Prep things for activities
// Checkout /course/lib.php:1778
foreach ($modnames as $modname => $modnamestr) {
    if (!course_allowed_module($course, $modname)) {
        continue;
    }

    $libfile = "$CFG->dirroot/mod/$modname/lib.php";
    if (!file_exists($libfile)) {
        continue;
    }

    include_once($libfile);
    $gettypesfunc =  $modname.'_get_types';
    if (function_exists($gettypesfunc)) {
        if ($types = $gettypesfunc()) {
            $menu = array();
            $atype = null;
            $groupname = null;
            foreach($types as $modtype) {
                if ($modtype->typestr === '--') {
                    continue;
                }

                if (strpos($modtype->typestr, '--') === 0) {
                    $groupname = str_replace('--', '', $modtype->typestr);
                    continue;
                }

                $modtype->type = str_replace('&amp;', '&', $modtype->type);
                if ($modtype->modclass == MOD_CLASS_RESOURCE) {
                    $atype = MOD_CLASS_RESOURCE;
                }

                $menu[$modtype->type] = $modtype->typestr;
            }

            if (!is_null($groupname)) {
                if ($atype == MOD_CLASS_RESOURCE) {
                    $resources[] = array($groupname => $menu);
                } else {
                    $activities[] = array($groupname => $menu);
                }
            } else {
                if ($atype == MOD_CLASS_RESOURCE) {
                    $resources = array_merge($resources, $menu);
                } else {
                    $activities = array_merge($activities, $menu);
                }
            }
        }
    } else {
        $archetype = plugin_supports('mod', $modname, 
            FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
        if ($archetype == MOD_ARCHETYPE_RESOURCE) {
            $resources[$modname] = $modnamestr;
        } else {
            // all other archetypes are considered activity
            $activities[$modname] = $modnamestr;
        }
    }
}

// Prep things for rearrange
$sectionnodes = array();
foreach ($sequences as $section => $sequence) {
    $sectionmods = explode(',', $sequence);
  
    $nodes = array();
    foreach ($sectionmods as $mod_id) {
        if (isset($mods[$mod_id])) {
            $cm =& $mods[$mod_id];

            if ($cm->section != $section) {
                debugging('Mismatching section for ' . $cm->name
                    . "({$cm->section})\n");
                // TODO FIX THIS!
                continue;
            }

            if ($cm->modname == 'label') {
                $display_text = format_text($modinfo->cms[$mod_id]->extra,
                    FORMAT_HTML, array('noclean' => true));
            } else {
                $display_text = format_string($modinfo->cms[$mod_id]->name,
                    true, $course_id);
            }

            $nodes[] = new modnode($mod_id, $display_text, $cm->indent);
        }
    }

    $parent_stack = array();
    $root_nodes = array();
    foreach ($nodes as $index => $node) {
        if (sizeof($parent_stack) == 0) {
            array_push($root_nodes, $node);
        } else {
            $indentdiff = $node->modindent - $nodes[$index - 1]->modindent;
            
            if ($indentdiff <= 0) {
                // Goto the previous possible parent at the same 
                // indentation level
                for ($i = abs($indentdiff) + 1; $i > 0; $i--) {
                    array_pop($parent_stack);
                }

                if (sizeof($parent_stack) == 0) {
                    array_push($root_nodes, $node);
                } else {
                    $nodes[end($parent_stack)]->add_child($node);
                }
            } else {
                $nodes[end($parent_stack)]->add_child($node);
            }
        }

        array_push($parent_stack, $index);
    }

    $sectionnodes[$section] = $root_nodes;
}

// Prep for return
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
        'sectionnames' => $sectionnames,
        'sectionnodes' => $sectionnodes,
        'resources' => $resources,
        'activities' => $activities
    ));

if ($uploadform->is_cancelled()) {
    redirect($cpurl);
} else if ($data = $uploadform->get_data()) {
    if (isset($data->redirectme)) {
        $dest = new moodle_url($data->redirectme,
            array('section' => $data->section));

        redirect($dest);
    }

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
    $jspath = '/blocks/ucla_control_panel/javascript/';

    $PAGE->requires->js($jspath . 'jquery-1.6.2.min.js');
    $PAGE->requires->js($jspath . 'inestedsortable-1.0.1.pack.js');
    $PAGE->requires->js($jspath . 'easyadd.js');

    $uploadform->display();
} else {
    $message = get_string('successfuladd', 'block_ucla_control_panel', $type);

    $params = array('id' => $course_id);

    // These following lines could be extracted out into a function
    // Get the _GET variable for the topic thing in the format
    $key = 'topic';
    $format = $course->format;
    $fn = 'callback_' . $format . '_request_key';
    if (function_exists($fn)) {
        $key = $fn();
    }

    $courseurl = new moodle_url('/course/view.php', $params);
    $courseret = new single_button($courseurl, get_string('returntocourse',
            'block_ucla_control_panel'), 'get');

    $secturl = new moodle_url('/course/view.php', $params);
    $secturl->param($key, $sectionid);
    $sectret = new single_button($secturl, get_string('returntosection', 
            'block_ucla_control_panel'), 'get');

    echo $OUTPUT->confirm($message, $sectret, $courseret);
}

echo $OUTPUT->footer();

// EOF
