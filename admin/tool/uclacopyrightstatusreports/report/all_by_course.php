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

$list = get_copyright_list_by_class($filter_term);

if (empty($list)) {
    echo html_writer::tag('p', get_string('no_all_by_course', 'tool_uclacopyrightstatusreports'));
} else {
    foreach ($list as $term=>$list1){

        $a = new stdClass();
        $a->term = ucla_term_to_text($term);
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

        $tbd_term = 0;
        $iown_term = 0;
        $ucown_term = 0;
        $lib_term = 0;
        $public1_term = 0;
        $cc1_term = 0;
        $obtained_term = 0;
        $fairuse_term = 0;

        foreach($list1 as $item) {
      
            $filelist = array();
            if($item->courseid){
                $filelist = get_files_copyright_status_by_course($item->courseid);
            }
            $filestat = array();
            if (!empty($filelist)){
                $filestat = calculate_copyright_status_statistics($filelist);
            }
            $row = array();
            $row[] = html_writer::link(new moodle_url($thisdir . 'report/course_copyright_detail.php', array('id' => $item->courseid)), 
                     $item->subj_area.' '.$item->coursenum.' (section '.$item->sectnum.')', array('target' => '_blank'));
            if (!empty($filestat)){
                $total = $filestat['total'];
                $tbd = isset($filestat['tbd'])?$filestat['tbd']:0;
                $iown = isset($filestat['iown'])?$filestat['iown']:0;
                $ucown = isset($filestat['ucown'])?$filestat['ucown']:0;
                $lib = isset($filestat['lib'])?$filestat['lib']:0;
                $public1 = isset($filestat['public1'])?$filestat['public1']:0;
                $cc1 = isset($filestat['cc1'])?$filestat['cc1']:0;
                $obtained = isset($filestat['obtained'])?$filestat['obtained']:0;
                $fairuse = isset($filestat['fairuse'])?$filestat['fairuse']:0;

                $row[] = $total;
                $row[] = $tbd;
                $row[] = $iown;
                $row[] = $ucown;
                $row[] = $lib;
                $row[] = $public1;
                $row[] = $cc1;
                $row[] = $obtained;
                $row[] = $fairuse;

                // calculate for subtotal for each term
                $tbd_term += $tbd;
                $iown_term += $iown;
                $ucown_term += $ucown;
                $lib_term += $lib;
                $public1_term += $public1;
                $cc1_term += $cc1;
                $obtained_term += $obtained;
                $fairuse_term += $fairuse;

            }else{
                $newcell = new html_table_cell();
                $newcell->colspan = 9;
                $newcell->text = get_string('no_file', 'tool_uclacopyrightstatusreports');
                $row[] = $newcell;
            }
            $table->data[] = $row;
        }
        // get total and list them

        $row_term = array();
        $total = $iown_term + $ucown_term + $lib_term + $public1_term + $cc1_term + $obtained_term + $fairuse_term + $tbd_term;
        $row_term[] = html_writer::tag('span', html_writer::tag('strong', get_string('total_files', 'tool_uclacopyrightstatusreports')), array('class'=>'grouping-text'));
        $row_term[] = $total;
        $row_term[] = $tbd_term;
        $row_term[] = $iown_term;
        $row_term[] = $ucown_term;
        $row_term[] = $lib_term;
        $row_term[] = $public1_term;
        $row_term[] = $cc1_term;
        $row_term[] = $obtained_term;
        $row_term[] = $fairuse_term;
        $table->data[] = $row_term;
        echo html_writer::table($table);
    }
}

echo $OUTPUT->footer();
