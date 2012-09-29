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
$filter_div = optional_param('filter_div', '', PARAM_TEXT);

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
echo $OUTPUT->heading(get_string('all_by_division', 'tool_uclacopyrightstatusreports'), 2, 'headingblock');
echo html_writer::tag('span', html_writer::link($baseurl . '/index.php', get_string('mainmenu', 'tool_uclacopyrightstatusreports')), array('class'=>'spacer'));
echo html_writer::tag('span', html_writer::link($baseurl . '/report/all_filter.php', get_string('filterpage', 'tool_uclacopyrightstatusreports')),array('class'=>'spacer'));

// Prepare data
$list = get_all('div');

// Start output
if (empty($list)) {
    echo html_writer::tag('p', get_string('no_all_by_course', 'tool_uclacopyrightstatusreports'));
} else {
    // all items across CCLE
    $tbd_ccle = 0;
    $iown_ccle = 0;
    $ucown_ccle = 0;
    $lib_ccle = 0;
    $public1_ccle = 0;
    $cc1_ccle = 0;
    $obtained_ccle = 0;
    $fairuse_ccle = 0;

    // one term per table
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
        
        foreach($termlist as $div => $list2) {
    
            if ($filter_div){
                if (array_key_exists($filter_div,$termlist )){
                    $divlist = $termlist[$filter_div];
                }else{
                    echo get_string('no_all_by_course', 'tool_uclacopyrightstatusreports');
                    break;
                }
            }
            else{
                $divlist = $list2;
            }
             
            // for division
            $tbd_div = 0;
            $iown_div = 0;
            $ucown_div = 0;
            $lib_div = 0;
            $public1_div = 0;
            $cc1_div = 0;
            $obtained_div = 0;
            $fairuse_div = 0;

            // loop division
            foreach ($divlist as $subj => $list3){
                
                // loop subject area
                $tbd_subj = 0;
                $iown_subj = 0;
                $ucown_subj = 0;
                $lib_subj = 0;
                $public1_subj = 0;
                $cc1_subj = 0;
                $obtained_subj = 0;
                $fairuse_subj = 0;

                foreach ($list3 as $item){

                    // calculate each item
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

                    // calculate for subtotal for each subject area
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
                $row_s = new html_table_row($row_subj);
                $row_s->style = 'color: #33CCCC';
                $table->data[] = $row_s;

                // add to division
                $tbd_div += isset($tbd_subj)?$tbd_subj:0;
                $iown_div += isset($iown_subj)?$iown_subj:0;
                $ucown_div += isset($ucown_subj)?$ucown_subj:0;
                $lib_div += isset($lib_subj)?$lib_subj:0;
                $public1_div += isset($public1_subj)?$public1_subj:0;
                $cc1_div += isset($cc1_subj)?$cc1_subj:0;
                $obtained_div += isset($obtained_subj)?$obtained_subj:0;
                $fairuse_div += isset($fairuse_subj)?$fairuse_subj:0;
                
            }
            $row_div = array();
            $subtotal = $iown_div + $ucown_div + $lib_div + $public1_div + $cc1_div + $obtained_div + $fairuse_div + $tbd_div;
            $row_div[] = html_writer::tag('span', html_writer::tag('strong', $item->fullname).' (D)', array('class'=>'grouping-text'));
            $row_div[] = $subtotal;
            $row_div[] = $tbd_div;
            $row_div[] = $iown_div;
            $row_div[] = $ucown_div;
            $row_div[] = $lib_div;
            $row_div[] = $public1_div;
            $row_div[] = $cc1_div;
            $row_div[] = $obtained_div;
            $row_div[] = $fairuse_div;
            $row_d = new html_table_row($row_div);
            $row_d->style = 'color: #6600CC';
            $table->data[] = $row_d;

             // ccle total
            $tbd_ccle += isset($tbd_div)?$tbd_div:0;
            $iown_ccle += isset($iown_div)?$iown_div:0;
            $ucown_ccle += isset($ucown_div)?$ucown_div:0;
            $lib_ccle += isset($lib_div)?$lib_div:0;
            $public1_ccle += isset($public1_div)?$public1_div:0;
            $cc1_ccle += isset($cc1_div)?$cc1_div:0;
            $obtained_ccle += isset($obtained_div)?$obtained_div:0;
            $fairuse_ccle += isset($fairuse_div)?$fairuse_div:0;
             
            if ($filter_div){
                break;
            }
      }
     
      echo html_writer::table($table);
      if ($filter_term){
          break;
      }
    }

    // CCLE total
    if (!$filter_div){
        $table = new html_table();
        $table->id = $tableid;    
        $table->attributes['class'] = 'generaltable';
        $table->align = array('left', 'left');
        $row_ccle = array();
        $subtotal = $iown_ccle + $ucown_ccle + $lib_ccle + $public1_ccle + $cc1_ccle + $obtained_ccle + $fairuse_ccle + $tbd_ccle;
        $row_ccle[] = html_writer::tag('span', html_writer::tag('strong', get_string('ccle', 'tool_uclacopyrightstatusreports')), array('class'=>'grouping-text'));
        $row_ccle[] = $subtotal.' ('.get_string('total_files','tool_uclacopyrightstatusreports').')';
        $row_ccle[] = $tbd_ccle.' ('.get_string('tbd','tool_uclacopyrightstatusreports').')';
        $row_ccle[] = $iown_ccle.' ('.get_string('iown','tool_uclacopyrightstatusreports').')';
        $row_ccle[] = $ucown_ccle.' ('.get_string('ucown','tool_uclacopyrightstatusreports').')';
        $row_ccle[] = $lib_ccle.' ('.get_string('lib','tool_uclacopyrightstatusreports').')';
        $row_ccle[] = $public1_ccle.' ('.get_string('public1','tool_uclacopyrightstatusreports').')';
        $row_ccle[] = $cc1_ccle.' ('.get_string('cc1','tool_uclacopyrightstatusreports').')';
        $row_ccle[] = $obtained_ccle.' ('.get_string('obtained','tool_uclacopyrightstatusreports').')';
        $row_ccle[] = $fairuse_ccle.' ('.get_string('fairuse','tool_uclacopyrightstatusreports').')';
        $table->data[] = $row_ccle;
        echo html_writer::table($table);
    }
}
echo $OUTPUT->footer();