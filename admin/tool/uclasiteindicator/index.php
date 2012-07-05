<?php
/**
 * UCLA Site Indicator 
 * 
 * @todo        make this nicer!
 * 
 * @package     ucla
 * @subpackage  uclasiteindicator
 * @author      Alfonso Roman
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$thisdir = '/' . $CFG->admin . '/tool/uclasiteindicator/';
require_once($CFG->dirroot . $thisdir . 'lib.php');
require_once($CFG->dirroot . $thisdir . 'siteindicator_form.php');

global $DB, $ME, $USER;

$baseurl = $CFG->wwwroot . '/' . $CFG->admin . '/tool/uclasiteindicator/index.php';

require_login();

$syscontext = get_context_instance(CONTEXT_SYSTEM);
require_capability('tool/uclasiteindicator:edit', $syscontext);


$thisfile = $thisdir . 'index.php';
// Initialize $PAGE
$PAGE->set_url($thisdir . $thisfile);
$PAGE->set_context($syscontext);
$PAGE->set_heading(get_string('pluginname', 'tool_uclasiteindicator'));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');

// Prepare and load Moodle Admin interface
admin_externalpage_setup('uclasiteindicator');

// Render page
echo $OUTPUT->header();

// Heading
echo $OUTPUT->box($OUTPUT->heading(get_string('pluginname', 'tool_uclasiteindicator')), 'generalbox categorybox box');

echo $OUTPUT->box_start('generalbox');
echo $OUTPUT->heading('Site types');

$types = siteindicator_manager::get_types_list();

$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->align = array('left', 'left', 'left');
$table->head = array('Indicator type', 'Shortname', 'Description');

foreach($types as $type) {
    $row = array();
    $row[] = $type['fullname'];
    $row[] = $type['shortname'];
    $row[] = $type['description'];

    $table->data[] = $row;
}
// Display indicator types
echo html_writer::table($table);

echo $OUTPUT->heading('Request history');
$history = siteindicator_manager::get_request_history();

$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->align = array('left', 'left', 'left', 'center', 'center');
$table->head = array('Request type', 'Current category', 'Site requester', 'Site status');

foreach($history as $h) {
    $row = array();
    $row[] = $h->type;
    $row[] = siteindicator_manager::get_categories_list($h->categoryid);
    
    $name = siteindicator_manager::get_username($h->requester);
    $row[] = html_writer::link($CFG->wwwroot . '/user/profile.php?id=' . $h->requester, $name);
    
    if($h->courseid) {
        $link = html_writer::link($CFG->wwwroot . '/course/view.php?id=' . $h->courseid, 'Active');
        $row[] = html_writer::tag('span', $link, array('class' => 'indicator-active indicator-block'));
    } else if($h->requestid) {
        $link = html_writer::link($CFG->wwwroot . '/course/pending.php?request=' . $h->requestid, 'Pending');
        $row[] = html_writer::tag('span', $link, array('class' => 'indicator-pending indicator-block'));
    } else {
        $row[] = html_writer::tag('span', 'Rejected', array('class' => 'indicator-reject indicator-block'));
    }

    $table->data[] = $row;
}

echo html_writer::table($table);

//echo $OUTPUT->heading('Orphan sites');
//$orphans = siteindicator_manager::get_orphans();
//
//$table = new html_table();
//$table->attributes['class'] = 'generaltable';
//$table->align = array('left', 'left', 'left', 'center', 'center');
//$table->head = array('Request type', 'Current category', 'Site requester', 'Site status');
//
//foreach($history as $h) {
//    $row = array();
//    $row[] = $h->type;
//    $row[] = siteindicator_manager::get_categories_list($h->categoryid);
//    
//    $name = siteindicator_manager::get_username($h->requester);
//    $row[] = html_writer::link($CFG->wwwroot . '/user/profile.php?id=' . $h->requester, $name);
//    
//    if($h->courseid) {
//        $link = html_writer::link($CFG->wwwroot . '/course/view.php?id=' . $h->courseid, 'Active');
//        $row[] = html_writer::tag('span', $link, array('class' => 'indicator-active indicator-block'));
//    } else if($h->requestid) {
//        $link = html_writer::link($CFG->wwwroot . '/course/pending.php?request=' . $h->requestid, 'Pending');
//        $row[] = html_writer::tag('span', $link, array('class' => 'indicator-pending indicator-block'));
//    } else {
//        $row[] = html_writer::tag('span', 'Rejected', array('class' => 'indicator-reject indicator-block'));
//    }
//
//    $table->data[] = $row;
//}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();

