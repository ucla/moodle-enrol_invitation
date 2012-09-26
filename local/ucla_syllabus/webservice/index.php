<?php

// Admin interface to web service
require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$thisdir = '/local/ucla_syllabus/webservice/';
require_once($CFG->dirroot . $thisdir . 'lib.php');
require_once($CFG->dirroot . $thisdir . 'webservice_form.php');

global $DB, $ME, $USER;

$baseurl = $CFG->wwwroot . '/local/ucla_syllabus/webservice';

require_login();

$syscontext = get_context_instance(CONTEXT_SYSTEM);
//require_capability('tool/uclasiteindicator:view', $syscontext);

$thisfile = $thisdir . 'index.php';
// Initialize $PAGE
$PAGE->set_url($thisdir . $thisfile);
$PAGE->set_context($syscontext);
$PAGE->set_heading('Syllabus web service');
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');

// Render page
echo $OUTPUT->header();

// Heading
echo $OUTPUT->heading('this is a heading', 2, 'headingblock');

echo $OUTPUT->box_start('generalbox');

$wsform = new syllabus_ws_form();

$wsform->display();

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
