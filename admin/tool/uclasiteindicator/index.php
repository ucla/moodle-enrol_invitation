<?php

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$thisdir = '/' . $CFG->admin . '/tool/uclasiteindicator/';
require_once($CFG->dirroot . $thisdir . 'lib.php');
require_once($CFG->dirroot . $thisdir . 'siteindicator_form.php');

global $DB, $ME, $USER;

require_login();

$syscontext = get_context_instance(CONTEXT_SYSTEM);
require_capability('tool/uclasiteindicator:edit', $syscontext);


$thisfile = $thisdir . 'index.php';
// Initialize $PAGE
$PAGE->set_url($thisdir . $thisfile);
$PAGE->set_context($syscontext);
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

$requestform = new siteindicator_form();


$types = $DB->get_records('ucla_indicator_type');

$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->align = array('left', 'left', 'left', 'center', 'center');
$table->head = array('Indicator type', 'Shortname', 'Description', 'Visible', get_string('action'));

foreach($types as $type) {
    $row = array();
    $row[] = $type->fullname;
    $row[] = $type->shortname;
    $row[] = $type->description;
    $row[] = $type->visible;
    $row[] = 'action!';

    $table->data[] = $row;
}

echo html_writer::table($table);

$requestform->display();

echo $OUTPUT->box_end();

echo $OUTPUT->footer();

