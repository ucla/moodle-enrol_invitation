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

$a = new stdClass();
if ($filter_term){
    $a->term = ucla_term_to_text($filter_term);
}
else{
    $a->term = get_string('allterm', 'tool_uclacopyrightstatusreports', $a);
}

// Heading
echo $OUTPUT->heading(get_string('all_by_course_subj', 'tool_uclacopyrightstatusreports', $a), 2, 'headingblock');
echo html_writer::tag('span', html_writer::link($baseurl . '/index.php', get_string('mainmenu', 'tool_uclacopyrightstatusreports')), array('class'=>'spacer'));
echo html_writer::tag('span', html_writer::link($baseurl . '/report/all_filter.php', get_string('filterpage', 'tool_uclacopyrightstatusreports')),array('class'=>'spacer'));

// Prepare data
$list = get_all('subj');

// Start output
if (empty($list)) {
    echo html_writer::tag('p', get_string('no_all_by_course', 'tool_uclacopyrightstatusreports'));
} else {
    foreach ($list as $term => $list1){
        if ($filter_term){
            $termlist = $list[$filter_term];
            $theterm = $filter_term;
        }
        else{
            $termlist = $list1;
            $theterm = $term;
        }
        $a = new stdClass();
        $a->term = ucla_term_to_text($theterm);
        echo html_writer::tag('div',strtoupper(get_string('list_by_course_term', 'tool_uclacopyrightstatusreports', $a)), array('class'=>'linespacer'));

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
        foreach($termlist as $subj => $list2) {
            if ($filter_subj){
                if (array_key_exists($filter_subj,$termlist)){
                    $subjlist = $termlist[$filter_subj];
                }else{
                    echo get_string('no_all_by_course', 'tool_uclacopyrightstatusreports');
                    break;
                }
            }
            else{
                $subjlist = $list2;
            }

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

            // loop subject area
            foreach ($subjlist as $item){
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
                        html_writer::tag('strong', $item->subj_area).' '.$item->coursenum.' (section '.$item->sectnum.')', array('target' => '_blank'));
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

            $row_subj = array();
            $subtotal = $iown_subj + $ucown_subj + $lib_subj + $public1_subj + $cc1_subj + $obtained_subj + $fairuse_subj + $tbd_subj;
            $row_subj[] = html_writer::tag('span', html_writer::tag('strong', $item->subj_area_full).' (S)', array('class'=>'grouping-text'));
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

            if ($filter_subj){
                break;
            }
        }
        echo html_writer::table($table);
        if ($filter_term){
          break;
        }
    }
}
echo $OUTPUT->footer();