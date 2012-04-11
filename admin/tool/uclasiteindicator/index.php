<?php

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$thisdir = '/' . $CFG->admin . '/tool/uclasiteindicator/';
//require_once($CFG->dirroot . $thisdir . 'lib.php');

global $DB, $ME, $USER;

require_login();

//$syscontext = get_context_instance(CONTEXT_SYSTEM);
//$rucr = 'tool_uclacourserequestor';
//
//// Adding 'Support Admin' capability to course requestor
//if (!has_capability('tool/uclacourserequestor:edit', $syscontext)) {
//    print_error('adminsonlybanner');
//}


$thisfile = $thisdir . 'index.php';
// Initialize $PAGE
$PAGE->set_url($thisdir . $thisfile);
//$PAGE->set_context($syscontext);
$PAGE->set_heading(get_string('plugintitle', 'tool_uclasiteindicator'));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');

// Prepare and load Moodle Admin interface
admin_externalpage_setup('uclasiteindicator');

// Render page
echo $OUTPUT->header();

// Heading
echo $OUTPUT->box($OUTPUT->heading(get_string('plugintitle', 'tool_uclasiteindicator')), 'generalbox categorybox box');

echo $OUTPUT->box_start('generalbox');
echo $OUTPUT->heading(get_string('pluginname', 'tool_uclasiteindicator'));
echo "this is a test";

echo $OUTPUT->box_end();

echo $OUTPUT->footer();

