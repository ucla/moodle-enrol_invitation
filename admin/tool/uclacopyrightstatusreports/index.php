<?php
/**
 * UCLA copyright status reports 
 * 
 * 
 * @package     ucla
 * @subpackage  uclacopyrightstatusreports
 * @author      Jun Wan
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$thisdir = '/' . $CFG->admin . '/tool/uclacopyrightstatusreports/';
require_once($CFG->dirroot . $thisdir . 'lib.php');

global $DB, $ME, $USER;

$baseurl = $CFG->wwwroot . '/' . $CFG->admin . '/tool/uclacopyrightstatusreports';

// Check permissions
require_login();
$syscontext = get_context_instance(CONTEXT_SYSTEM);
require_capability('tool/uclacopyrightstatusreports:view', $syscontext);
$thisfile = $thisdir . 'index.php';

// Initialize $PAGE
$PAGE->set_url($thisdir . $thisfile);
$PAGE->set_context($syscontext);
$PAGE->set_heading(get_string('pluginname', 'tool_uclacopyrightstatusreports'));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');

// Prepare and load Moodle Admin interface
admin_externalpage_setup('uclacopyrightstatusreports');

// Render page
echo $OUTPUT->header();

// Heading
echo $OUTPUT->heading(get_string('pluginname', 'tool_uclacopyrightstatusreports'), 2, 'headingblock');

echo $OUTPUT->box_start('generalbox');

echo html_writer::tag('p', get_string('reports_intro', 'tool_uclacopyrightstatusreports'));

$report_types = array(
    array('all_by_course_current_term','all_by_course'),
    array('all_by_instructor_current_term','all_by_instructor'),
    array('all_by_course_subj_current_term','all_by_course_subj'),
    array('all_by_course_ccle_current_term','all_by_course_div'),
    array('all_filter', 'all_filter')
);

// create nodes to put in ordered list
foreach ($report_types as $index => $report_type) {
    $url = $baseurl . '/report/' . $report_type[1] . '.php';
    $report_types[$index] = html_writer::link($url, 
            get_string($report_type[0], 'tool_uclacopyrightstatusreports'));
}

echo html_writer::alist($report_types, array(), 'ol');

echo $OUTPUT->box_end();
echo $OUTPUT->footer();

