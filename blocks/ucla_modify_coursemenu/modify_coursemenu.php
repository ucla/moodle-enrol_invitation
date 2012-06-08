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
require_once($CFG->dirroot . $thispath . '/verify_modification_form.php');

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

$courseviewurl = new moodle_url('/course/view.php', array('id' => $courseid));

$modinfo =& get_fast_modinfo($course);

$course_preferences = new ucla_course_prefs($courseid);
$landing_page = $course_preferences->get_preference('landing_page', false);
if ($landing_page === false) {
    $landing_page = 0;
} 

// Set up the page.
$PAGE->set_context($context);

$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_url('/blocks/ucla_modify_coursemenu/modify_coursemenu.php', 
        array('courseid' => $courseid));

// Note that forms will call $OUTPUT->pix_url which uses
// PAGE->theme which will autoload stuff I'm not certain enough to
// document here, so call $PAGE->set_context before loading forms
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

// This is needed if we're deleting sections
$verifyform = new verify_modification_form();
$verifydata = false;

$redirector = null;

// Used to tell users that they cannot delete
$sectionsnotify = array();
$passthrudata = null;

//extract the data from the form and update the database
if ($modify_coursemenu_form->is_cancelled()) {
    redirect($courseviewurl);
} else if ($data = $modify_coursemenu_form->get_data()) {
    // TODO see if some of the fields can be parsed from within the MForm
    parse_str($data->serialized, $unserialized);
    parse_str($data->sectionsorder, $sectionorderparsed);

    // TODO make it consistent IN CODE how section id's are generated
    $sectionorder = array();
    foreach ($sectionorderparsed['sections-order'] as $k => $sectionid) {
        $sectnum = str_replace('section-', '', $sectionid);
        if ($sectnum == UCLA_FORMAT_DISPLAY_ALL 
                || $sectnum == '0') {
            continue;
        }

        // subtract 1 since we have to compensate for the pseudo-show-all
        // section
        if (is_int($k)) {        
            $k--;
        }

        $sectionorder[$k] = $sectnum;
    }

    // TODO make it consistent IN CODE how all these fields are generated
    $sectiondata = array();
    foreach ($unserialized as $fieldid => $value) {
        list($fieldtype, $sectionkey) = explode('-', $fieldid);
        if (!isset($sectiondata[$sectionkey])) {
            $sectiondata[$sectionkey] = array();
        }

        // Try to synchronize with Moodle field names
        if ($fieldtype == 'hidden') {
            $fieldtype = 'visible';
            $value = 0;
        }

        if ($fieldtype == 'title') {
            $fieldtype = 'name';
        }

        $sectiondata[$sectionkey][$fieldtype] = $value;
    }

    // Compare submitted data and current sections,
    // Set the ordering and the to-be-deleted sections
    $tobedeleted = array();
    $couldnotdelete = array();

    $newsectnum = 1;
    foreach ($sectiondata as $oldsectnum => $sectdata) {
        if (!isset($sections[$oldsectnum])) {
            $sectdata['course'] = $courseid;
            $sections[$oldsectnum] = (object) $sectdata;
        }

        $section = $sections[$oldsectnum];

        // check to delete
        if (!empty($sectdata['delete'])) {
            // Notification mode needs to be turned on if the section is
            // not empty
            if (!block_ucla_modify_coursemenu::section_is_empty($section)) {
                $sectionsnotify[$oldsectnum] = $section;
            }

            $tobedeleted[] = $section;
            unset($sections[$oldsectnum]);
            continue;
        }

        $section->section = $newsectnum;

        $section = block_ucla_modify_coursemenu::section_apply($section,
            $sectdata);

        $newsectnum++;
    }

    // Delete some sections...how to do this?
    $deletesectionids = array();
    foreach ($tobedeleted as $todelete) {
        // Double check?
        if (isset($todelete->id)) {
            $deletesectionids[] = $todelete->id;
        }
    }

    $passthrudata = new object();
    $passthrudata->sections = $sections;
    $passthrudata->deletesectionids = $deletesectionids;
    $passthrudata->landingpage = $data->landingpage;
    $passthrudata->coursenumsections = $newsectnum;

    // We need to add a validation thing for deleting sections
    if (!empty($sectionsnotify)) {
        // Generate html to display in the verifcation form
        $formdisplayhtml = get_string('deletesectioncontents',
            'block_ucla_modify_coursemenu') . html_writer::empty_tag('br')
                . $OUTPUT->heading(get_string('tbdel', 
                    'block_ucla_modify_coursemenu'), 2);

        // note: this section has potential to be copied if adding
        // delete functionality in JIT buttons
        // However, I do not want to function-ize it without properly
        // interfaced renderers
        foreach ($sectionsnotify as $oldsectnum => $sectionnotify) {
            $sectionhtml = $OUTPUT->heading($sectionnotify->name, 4) 
                . html_writer::start_tag('ul');

            $cminfos = block_ucla_modify_coursemenu::get_section_content(
                    $sectionnotify, $course, $modinfo
                );

            foreach ($cminfos as $cminstance) {
                list($cmcontent, $instancename) = 
                    get_print_section_cm_text($cminstance, $course);

                $sectionhtml .= html_writer::tag(
                        'li',
                        $instancename . ' (' 
                            . get_string('modulename', 
                                $cminstance->modname) . ')'
                    );
            }

            $sectionhtml .= html_writer::end_tag('ul');
        
            $formdisplayhtml .= $sectionhtml;
        }

        $formdisplayhtml = $OUTPUT->box($formdisplayhtml, 
            'modify-course-sections-summary generalbox');

        $verifyform = new verify_modification_form(
                null,
                array(
                    'passthrudata' => $passthrudata,
                    'courseid' => $courseid,
                    'displayhtml' => $formdisplayhtml
                )
            );
    
        $passthrudata = null;
    }

    $redirector = $courseviewurl;
} else if ($verifyform->is_cancelled()) {
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
}

// If we've verified we want to delete sections, or if we don't need
// to verify
$verifydata = $verifyform->get_data();
if ($passthrudata || $verifydata) {
    if (!$passthrudata) {
        $passthrudata = unserialize($verifydata->passthrudata);
    }

    $deletesectionids = $passthrudata->deletesectionids;
    if (!empty($deletesectionids)) {
        $DB->delete_records_list('course_sections', 'id', 
            $deletesectionids);
    }

    foreach ($passthrudata->sections as $section) {
        // No need to update site info...
        if ($section->section == 0) {
            continue;
        }

        if (!isset($section->id)) {
            $DB->insert_record('course_sections', $section);
        } else {
            var_dump($section);
            $DB->update_record('course_sections', $section);
        }
    }

    $course_preferences->set_preference('landing_page', 
        $passthrudata->landingpage);
    $course_preferences->commit();

    // Update the course numsections
    $course->numsections = $passthrudata->coursenumsections;
    update_course($course);

    $redirector = $courseviewurl;
}

// Before doing any heavy PAGE-related lifting, see if we should redirect to
// the success screen
// This will come here when the modifier form is submitted, but a section 
// with content is discovered, BUT the verify form has not been submitted
if ($data && empty($sectionsnotify) || $verifydata) {
    redirect($redirector);
}

$restr = get_string('pluginname', 'block_ucla_modify_coursemenu');
$restrc = "$restr: {$course->shortname}";


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

// Load other things here for consistency 
$maintableid = block_ucla_modify_coursemenu::maintable_domnode;
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
        'sectiondata' => $sections,
    ));

$PAGE->requires->js_init_code(
    js_writer::set_variable('M.block_ucla_modify_coursemenu.pix.handle',
        $OUTPUT->pix_url('handle', 'block_ucla_modify_coursemenu')->out()
    ));

$PAGE->requires->js_init_code('M.block_ucla_modify_coursemenu.initialize()');

if ($courseformat == 'ucla') {
    $PAGE->requires->string_for_js('show_all', $format_compstr);
    block_ucla_modify_coursemenu::js_init_code_helper(
            'showallsection', UCLA_FORMAT_DISPLAY_ALL
        );
}

$PAGE->set_title($restrc);
$PAGE->set_heading($restrc);

set_editing_mode_button($courseviewurl);

echo $OUTPUT->header();
echo $OUTPUT->heading($restr, 2, 'headingblock');

if ($data && !empty($sectionsnotify) && !$verifydata) {
    $verifyform->display();
} else {
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

    echo html_writer::table($tablestructure);
    $modify_coursemenu_form->display();
}
 
echo $OUTPUT->footer();

