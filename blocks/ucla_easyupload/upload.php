<?php

require_once(dirname(__FILE__) . '/../../config.php');

require_once($CFG->dirroot . '/course/lib.php');

$thispath = '/blocks/ucla_easyupload';
require_once($CFG->dirroot . $thispath . '/block_ucla_easyupload.php');
require_once($CFG->dirroot . $thispath . '/upload_form.php');

@include_once($CFG->libdir . '/publicprivate/module.class.php');
// Need to inlucde here.  License is not treated as plugin in the code
@include_once($CFG->libdir. '/licenselib.php');

global $CFG, $PAGE, $OUTPUT;

$course_id = required_param('course_id', PARAM_INT);
$type = required_param('type', PARAM_ALPHA);

$currsect = optional_param('section', 0, PARAM_INT);

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

$PAGE->set_url('/blocks/ucla_easyupload/upload.php', 
        array('course_id' => $course_id, 'type' => $type));

// TODO Fix this Prep for return
$cpurl = new moodle_url('/blocks/ucla_control_panel/view.php',
        array('course_id' => $course_id));

$courseurl = new moodle_url('/course/view.php',
        array('id' => $course_id));

// Type was not specified, or the form was cancelled...
if (!$type) {
    redirect($cpurl);
}

// Get all the informations for the form.
$modinfo =& get_fast_modinfo($course);
get_all_mods($course_id, $mods, $modnames, $modnamesplural, $modnamesused);

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

// Prep copyrights for copyright selector
$copyrights_result = array();
$copyrights_result = license_manager::get_licenses(array('enabled'=>1));
$copyrights = array();
 foreach ($copyrights_result as $copyright) {
    $sid = $copyright->shortname;
    $copyrights[$sid] = $copyright->fullname;
}


// Prep things for section selector
$sections = get_all_sections($course_id);

$sectionnames = array();
$indexed_sections = array();

// if default section is greater than course numsections, default to 0
if (!isset($defaultsection) || $defaultsection > $course->numsections) {
    $defaultsection = 0;
}

foreach ($sections as $section) {
    if ($section->section > $course->numsections) {
        continue;
    }

    $sid = $section->id;
    if ($section->section == $currsect) {
        $defaultsection = $sid;
    }
    
    $sectionnames[$sid] = get_section_name($course, $section);

    $indexed_sections[$sid] = $section;
}

// Prep things for rearrange
$rearrange_avail = false;
if (block_ucla_easyupload::block_ucla_rearrange_installed()) {
    $rearrange_avail = true;
    $sectionmodnodes = block_ucla_rearrange::get_sections_modnodes(
        $course_id, $sections, $mods, $modinfo
    );

    $sectionnodeshtml = array();
    foreach ($sectionmodnodes as $index => $smn) {
        $snhtml = '';
        foreach ($smn as $modnode) {
            $snhtml .= $modnode->render();
        }
        $sectionnodeshtml[$index] = $snhtml;
    }

    // Start placing required javascript
    // This is a set of custom javascript hooks
    $PAGE->requires->js('/blocks/ucla_easyupload/javascript'
        . '/block_ucla_easyadd.js');
    $PAGE->requires->css('/blocks/ucla_rearrange/styles.css');

    // TODO watch out for multiheader
    $dli = new modnode('new', null, 0);
    $dlihtml = $dli->render();
    $cv = array('empty_item' => $dlihtml);

    block_ucla_rearrange::setup_nested_sortable_js($sectionnodeshtml, 
        '#thelist', $cv);
}
// End rearrange behavior */

$typeclass = block_ucla_easyupload::upload_type_exists($type);
if (!$typeclass) {
    print_error('typenotexists');
}

// Create the upload form
$uploadform = new $typeclass(null, 
    array(
        // Needed to come back to this script w/o error
        'course' => $course, 
        // Needed for some get_string()
        'type' => $type, 
        // Needed for copyright <SELECT>
        'copyrights' => $copyrights,
        // Needed for the section <SELECT>
        'sectionnames' => $sectionnames,
        'defaultsection' => $defaultsection,
        // Needed when picking resources 
        'resources' => $resources,
        // Needed when picking activities
        'activities' => $activities,
        // Needed to enable/disable rearrange
        'rearrange' => $rearrange_avail
    ));

if ($uploadform->is_cancelled()) {
    redirect($cpurl);
} else if ($data = $uploadform->get_data()) {
    // Confusing distinction between sectionid and sectionnumber
    $targetsection = $data->section;
    $targetsectnum = $indexed_sections[$targetsection]->section;
    $data->section = $targetsectnum;

    if (isset($data->redirectme)) {
        if (!method_exists($uploadform, 'get_send_params')) {
            print_error('redirectimplementationerror');
        }

        // This discrepancy is really terrible.
        $data->section = $targetsectnum;
        
        $params = $uploadform->get_send_params();

        $subtypes = explode('&', $data->add);

        if (count($subtypes) > 1) {
            $data->add = $subtypes[0];

            unset($subtypes[0]);

            foreach ($subtypes as $subtype) { 
                $subtypeassign = explode('=', $subtype);
                $subtypestr = $subtypeassign[0];
                $subtypeval = $subtypeassign[1];

                $params[] = $subtypestr;
                $data->{$subtypestr} = $subtypeval;
            }
        }

        $get_sends = array();
        foreach ($params as $param) {
            if (!isset($data->$param)) {
                print_error('missingparam', $param);
            }

            $get_sends[$param] = $data->$param;
        }

        $dest = new moodle_url($data->redirectme, $get_sends);

        redirect($dest);
    }

    // Pilfered parts from /course/modedit.php
    $modulename = $data->modulename;
    // Module resource
    $moddir = $CFG->dirroot . '/mod/' . $modulename;
    $modform = $moddir . '/mod_form.php';
    if (file_exists($modform)) {
        include_once($modform);
    } else {
        print_error('noformdesc');
    }

    $module = $DB->get_record('modules', array('name' => $modulename),
            '*', MUST_EXIST);

    if (!course_allowed_module($course, $modulename)) {
        print_error('moduledisable');
    }

    $addinstancefn = $modulename . '_add_instance';
    
    $newcm = new stdclass();
    $newcm->course = $course->id;
    $newcm->section = $targetsection;
    $newcm->module = $module->id;
    $newcm->instance = 0;

    // Observe course/modedit.php
    if (!empty($CFG->enableavailability)) {
        $newcm->availablefrom = $data->availablefrom;
        $newcm->availableuntil = $data->availableuntil;
        $newcm->showavailability = $data->showavailability;
    }
   
    // TODO Handle section visibility
    $newcm->visible = 1;

    $coursemoduleid = add_course_module($newcm);
    if (!$coursemoduleid) {
        print_error('cannotaddnewmodule');
    }

    $data->coursemodule = $coursemoduleid;
        
    if (plugin_supports('mod', $modulename, FEATURE_MOD_INTRO, true)
            && !empty($data->introeditor)) {
        $introeditor = $data->introeditor;
        unset($data->introeditor);

        $data->intro       = $introeditor['text'];
        $data->introformat = $introeditor['format'];
    }

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

    // Public Private
    if (class_exists('PublicPrivate_Module') 
            && PublicPrivate_Site::is_enabled()) {
        if (!empty($data->publicprivateradios)) {
            $ppsetting = $data->publicprivateradios['publicprivate'];
        } else {
            $ppsetting = 'public';
        }

        $pp = new PublicPrivate_Module($coursemoduleid);

        if ($ppsetting == 'public') {
            $pp->disable();
        } else {
            $pp->enable();
        }
    }
    
    if (!isset($data->serialized) || empty($data->serialized)) {
        // Assume that we're not changing the order
        $sequencearr = false;
    } else {
        parse_str($data->serialized, $parsed);
        $newmods = modnode::flatten($parsed['thelist']);

        $sequencearr = array();
        foreach($newmods as $newmod) {
            if ($newmod->id == 'new') {
                $newmod->id = $coursemoduleid;
            }

            $sequencearr[$newmod->id] = $newmod->id;
        }
    }

    if (isset($newmods) && $sequencearr) {
        // This implies that we have rearrange available
        $newmodules = array($sectionid => $newmods);
        block_ucla_rearrange::move_modules_section_bulk($newmodules);
    }

    rebuild_course_cache($course_id);
}

// Display the rest of the page
$title = get_string($typeclass, 'block_ucla_easyupload', $course->fullname);

$PAGE->set_title($title);
$PAGE->set_heading($title);

// Print out the header and blocks
echo $OUTPUT->header();

// Print out a heading
echo $OUTPUT->heading($title);

if (!isset($data) || !$data) {
    $uploadform->display();
} else {
    // Do not draw the form! 
    $message = get_string('successfuladd', 'block_ucla_easyupload', $type);

    $params = array('id' => $course_id);

    // These following lines could be extracted out into a function
    // Get the _GET variable for the topic thing in the format
    $key = 'topic';
    $format = $course->format;
    $fn = 'callback_' . $format . '_request_key';
    if (function_exists($fn)) {
        $key = $fn();
    }
    
    if (defined('UCLA_FORMAT_DISPLAY_LANDING')) {
        $params['topic'] = UCLA_FORMAT_DISPLAY_LANDING;
    }    
    $courseurl = new moodle_url('/course/view.php', $params);
    $courseret = new single_button($courseurl, get_string('returntocourse',
            'block_ucla_easyupload'), 'get');

    $secturl = new moodle_url('/course/view.php', $params);
    $secturl->param($key, $indexed_sections[$sectionid]->section);
    $sectret = new single_button($secturl, get_string('returntosection', 
            'block_ucla_easyupload'), 'get');

    echo $OUTPUT->confirm($message, $sectret, $courseret);
}

echo $OUTPUT->footer();

// EOF
