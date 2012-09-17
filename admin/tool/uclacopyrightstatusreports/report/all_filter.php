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

$filter_term = optional_param('term', $CFG->currentterm, PARAM_TEXT);
$filter_instr_uid = optional_param('filter_instr_uid', '', PARAM_TEXT);
$filter_subj = optional_param('filter_subj','', PARAM_TEXT);
$filter_div = optional_param('filter_div','', PARAM_TEXT);

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

// Render page
echo $OUTPUT->header();

// Heading
echo $OUTPUT->heading(get_string('all_filter', 'tool_uclacopyrightstatusreports'), 2, 'headingblock');
echo html_writer::link($baseurl . '/index.php', get_string('mainmenu', 'tool_uclacopyrightstatusreports'));

$div_list = get_division();
$subj_list = get_subjarea();
$term_list = get_terms();
$instr_list = get_instructors_list_by_term($filter_term);

// output
echo html_writer::start_tag('div', array('id' => 'tool_uclacopyrightstatusreports_filter'));

$PAGE->set_url($thisdir . 'report/all_by_course_subj.php');
echo html_writer::start_tag('form', array('id'=>'tool_uclacopyrightstatusreports_course_div_form', 'action'=>$PAGE->url->out(), 'method'=>'post'));
echo html_writer::select($div_list, 'filter_div', $filter_div, array(''=>'Choose division'), array('id'=>'tool_uclacopyrightstatusreports_id_filter_div'));
echo html_writer::select($term_list, 'filter_term', $filter_term, false, array('id'=>'tool_uclacopyrightstatusreports_id_filter_term_subj'));
echo html_writer::empty_tag('input', array('id' => 'tool_uclacopyrightstatusreports_btn2', 'name' => 'course', 'value' => get_string('course_button',
                            'tool_uclacopyrightstatusreports'), 'type' => 'submit'));
echo html_writer::end_tag('form');

$PAGE->set_url($thisdir . 'report/all_by_course_subj.php');
echo html_writer::start_tag('form', array('id'=>'tool_uclacopyrightstatusreports_course_subj_form', 'action'=>$PAGE->url->out(), 'method'=>'post'));
echo html_writer::select($subj_list, 'filter_subj', $filter_subj, array(''=>'Choose subject area'), array('id'=>'tool_uclacopyrightstatusreports_id_filter_subj'));
echo html_writer::select($term_list, 'filter_term', $filter_term, false, array('id'=>'tool_uclacopyrightstatusreports_id_filter_term_subj'));
echo html_writer::empty_tag('input', array('id' => 'tool_uclacopyrightstatusreports_btn2', 'name' => 'course', 'value' => get_string('course_button',
                            'tool_uclacopyrightstatusreports'), 'type' => 'submit'));
echo html_writer::end_tag('form');

$PAGE->set_url($thisdir . 'report/all_by_instructor.php');
echo html_writer::start_tag('form', array('id'=>'tool_uclacopyrightstatusreports_instructor_form', 'action'=>$PAGE->url->out(), 'method'=>'post'));
echo html_writer::select($instr_list, 'filter_instructor', $filter_instr_uid, array(''=>'Choose instructor'), array('id'=>'tool_uclacopyrightstatusreports_id_filter_instr'));
echo html_writer::select($term_list, 'filter_term', $filter_term, false, array('id'=>'tool_uclacopyrightstatusreports_id_filter_term'));
echo html_writer::empty_tag('input', array('id' => 'tool_uclacopyrightstatusreports_btn1', 'name' => 'instr', 'value' => get_string('instr_button',
                            'tool_uclacopyrightstatusreports'), 'type' => 'submit'));
echo html_writer::end_tag('form');

echo html_writer::end_tag('div');
    


echo $OUTPUT->footer();