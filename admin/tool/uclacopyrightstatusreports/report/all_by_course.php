<?php
/**
 * UCLA copyright status report: copyright status by course
 * 
 * @package     tool
 * @subpackage  uclacopyrightstatusreports
 * @copyright   UC Regents
 */

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$thisdir = '/' . $CFG->admin . '/tool/uclacopyrightstatusreports/';
require_once($CFG->dirroot . $thisdir . 'lib.php');
require_once($CFG->dirroot . '/blocks/ucla_copyright_status/lib.php');

$baseurl = $CFG->wwwroot . '/' . $CFG->admin . '/tool/uclacopyrightstatusreports';
$term = optional_param('term', $CFG->currentterm, PARAM_TEXT);

require_login();

$syscontext = get_context_instance(CONTEXT_SYSTEM);
require_capability('tool/uclacopyrightstatusreports:view', $syscontext);

// Initialize $PAGE
$PAGE->set_url($thisdir . 'index.php');
$PAGE->set_context($syscontext);
$PAGE->set_heading(get_string('pluginname', 'tool_uclacopyrightstatusreports'));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');

// Prepare and load Moodle Admin interface
admin_externalpage_setup('uclacopyrightstatusreports');

// prepare table sorting functionality
$tableid = setup_js_tablesorter('uclacopyrightstatusreports_all_by_course_report');

// Render page
echo $OUTPUT->header();

// Heading
echo $OUTPUT->heading(get_string('all_by_course', 'tool_uclacopyrightstatusreports'), 2, 'headingblock');
echo html_writer::link($baseurl . '/index.php', get_string('mainmenu', 'tool_uclacopyrightstatusreports'));
$list = get_copyright_list_by_class($term);

if (empty($list)) {
    echo html_writer::tag('p', get_string('no_all_by_course', 'tool_uclacopyrightstatusreports'));
} else {
    $table = new html_table();
    $table->id = $tableid;    
    $table->attributes['class'] = 'generaltable';
    $table->align = array('left', 'left');
    $table->head = array(get_string('class_name', 'tool_uclacopyrightstatusreports'), 
        get_string('total_files', 'tool_uclacopyrightstatusreports'),
        get_string('tbd', 'tool_uclacopyrightstatusreports').get_string('tbd_help', 'tool_uclacopyrightstatusreports'),
        get_string('iown', 'tool_uclacopyrightstatusreports').get_string('iown_help', 'tool_uclacopyrightstatusreports'),
        get_string('ucown', 'tool_uclacopyrightstatusreports').get_string('ucown_help', 'tool_uclacopyrightstatusreports'),
        get_string('lib', 'tool_uclacopyrightstatusreports').get_string('lib_help', 'tool_uclacopyrightstatusreports'),
        get_string('public1', 'tool_uclacopyrightstatusreports').get_string('public1_help', 'tool_uclacopyrightstatusreports'),
        get_string('cc1', 'tool_uclacopyrightstatusreports').get_string('cc1_help', 'tool_uclacopyrightstatusreports'),
        get_string('obtained', 'tool_uclacopyrightstatusreports').get_string('obtained_help', 'tool_uclacopyrightstatusreports'),
        get_string('fairuse', 'tool_uclacopyrightstatusreports').get_string('fairuse_help', 'tool_uclacopyrightstatusreports'));

    foreach($list as $item) {
  
        $filelist = array();
        $filelist = get_files_copyright_status_by_course($item->courseid);
        $filestat = array();
        $filestat = calculate_copyright_status_statistics($filelist);
        $row = array();
        $row[] = html_writer::link(new moodle_url($thisdir . 'report/course_copyright_detail.php', array('id' => $item->courseid)), 
                $item->subjarea.' '.$item->course.' (section '.$item->section.')', array('target' => '_blank'));
        $row[] = $filestat['total'];  
        $row[] = isset($filestat['tbd'])?$filestat['tbd']:0;
        $row[] = isset($filestat['iown'])?$filestat['iown']:0;
        $row[] = isset($filestat['ucown'])?$filestat['ucown']:0;
        $row[] = isset($filestat['lib'])?$filestat['lib']:0;
        $row[] = isset($filestat['public1'])?$filestat['public1']:0;
        $row[] = isset($filestat['cc1'])?$filestat['cc1']:0;
        $row[] = isset($filestat['obtained'])?$filestat['obtained']:0;
        $row[] = isset($filestat['fairuse'])?$filestat['fairuse']:0;
        $table->data[] = $row;
    }
    
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
