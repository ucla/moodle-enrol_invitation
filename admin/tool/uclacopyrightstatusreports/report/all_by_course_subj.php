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
$filter_term = optional_param('filter_term', $CFG->currentterm, PARAM_TEXT);
$filter_subj = optional_param('filter_subj', '', PARAM_TEXT);
$filter_array = array('term'=>$filter_term, 'subj'=>$filter_subj);

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
echo html_writer::tag('span', html_writer::link($baseurl . '/index.php', get_string('mainmenu', 'tool_uclacopyrightstatusreports')), array('class'=>'spacer'));
echo html_writer::tag('span', html_writer::link($baseurl . '/report/all_filter.php', get_string('filterpage', 'tool_uclacopyrightstatusreports')),array('class'=>'spacer'));

$list = get_copyright_list_by_course_subj(&$filter_array);

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


    // for subject area
    $subjarea = '';
    $tbd_subj = 0;
    $iown_subj = 0;
    $ucown_subj = 0;
    $lib_subj = 0;
    $public1_subj = 0;
    $cc1_subj = 0;
    $obtained_subj = 0;
    $fairuse_subj = 0;

    foreach($list as $item) {

        // subtotal for subject area
        if (trim($subjarea)!= $item->subjarea){
            if ($subjarea){
                $row_subj = array();
                $subtotal = $iown_subj + $ucown_subj + $lib_subj + $public1_subj + $cc1_subj + $obtained_subj + $fairuse_subj + $tbd_subj;
                $row_subj[] = html_writer::tag('span', html_writer::tag('strong', "SUBTOTAL for " . $subjarea), array('class'=>'grouping-text'));
                $row_subj[] = $subtotal;
                $row_subj[] = $tbd_subj;
                $row_subj[] = $iown_subj;
                $row_subj[] = $ucown_subj;
                $row_subj[] = $lib_subj;
                $row_subj[] = $public1_subj;
                $row_subj[] = $cc1_subj;
                $row_subj[] = $obtained_subj;
                $row_subj[] = $fairuse_subj;
                $table->data[] = $row_subj;
                $tbd_subj = 0;
                $iown_subj = 0;
                $ucown_subj = 0;
                $lib_subj = 0;
                $public1_subj = 0;
                $cc1_subj = 0;
                $obtained_subj = 0;
                $fairuse_subj = 0;
            }
            $subjarea = $item->subjarea;
        }

        $filelist = array();
        if ($item->courseid){
            $filelist = get_files_copyright_status_by_course($item->courseid);
        }
        $filestat = array();
        if (!empty($filelist)){
            $filestat = calculate_copyright_status_statistics($filelist);
        }
        $row = array();
        $row[] = html_writer::link(new moodle_url($thisdir . 'report/course_copyright_detail.php', array('id' => $item->courseid)), 
                html_writer::tag('strong', $item->subjarea).' '.$item->course.' (section '.$item->section.')', array('target' => '_blank'));
        if (!empty($filestat)){
            $row[] = $filestat['total']; 
            $row[] = isset($filestat['tbd'])?$filestat['tbd']:0;
            $row[] = isset($filestat['iown'])?$filestat['iown']:0;
            $row[] = isset($filestat['ucown'])?$filestat['ucown']:0;
            $row[] = isset($filestat['lib'])?$filestat['lib']:0;
            $row[] = isset($filestat['public1'])?$filestat['public1']:0;
            $row[] = isset($filestat['cc1'])?$filestat['cc1']:0;
            $row[] = isset($filestat['obtained'])?$filestat['obtained']:0;
            $row[] = isset($filestat['fairuse'])?$filestat['fairuse']:0;
        }else{
            $newcell = new html_table_cell();
            $newcell->colspan = 9;
            $newcell->text = get_string('no_file', 'tool_uclacopyrightstatusreports');
            $row[] = $newcell;
        }
        $table->data[] = $row;

        $tbd_subj += isset($filestat['tbd'])?$filestat['tbd']:0;
        $iown_subj += isset($filestat['iown'])?$filestat['iown']:0;
        $ucown_subj += isset($filestat['ucown'])?$filestat['ucown']:0;
        $lib_subj += isset($filestat['lib'])?$filestat['lib']:0;
        $public1_subj += isset($filestat['public1'])?$filestat['public1']:0;
        $cc1_subj += isset($filestat['cc1'])?$filestat['cc1']:0;
        $obtained_subj += isset($filestat['obtained'])?$filestat['obtained']:0;
        $fairuse_subj += isset($filestat['fairuse'])?$filestat['fairuse']:0;
         
    }

    // for the last subject area

    $row_subj = array();
    $subtotal = $iown_subj + $ucown_subj + $lib_subj + $public1_subj + $cc1_subj + $obtained_subj + $fairuse_subj + $tbd_subj;
    $row_subj[] = html_writer::tag('span', html_writer::tag('strong', "SUBTOTAL for " . $subjarea), array('class'=>'grouping-text'));
    $row_subj[] = $subtotal;
    $row_subj[] = $tbd_subj;
    $row_subj[] = $iown_subj;
    $row_subj[] = $ucown_subj;
    $row_subj[] = $lib_subj;
    $row_subj[] = $public1_subj;
    $row_subj[] = $cc1_subj;
    $row_subj[] = $obtained_subj;
    $row_subj[] = $fairuse_subj;
    $table->data[] = $row_subj;
    
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
