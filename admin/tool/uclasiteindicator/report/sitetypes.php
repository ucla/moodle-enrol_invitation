<?php
/**
 * UCLA Site indicator: Site types 
 * 
 * @package     tool
 * @subpackage  uclasiteindicator
 * @copyright   UC Regents
 */

require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$thisdir = '/' . $CFG->admin . '/tool/uclasiteindicator/';
require_once($CFG->dirroot . $thisdir . 'lib.php');
require_once($CFG->dirroot . $thisdir . 'siteindicator_form.php');

$baseurl = $CFG->wwwroot . '/' . $CFG->admin . '/tool/uclasiteindicator';

require_login();

$syscontext = get_context_instance(CONTEXT_SYSTEM);
require_capability('tool/uclasiteindicator:view', $syscontext);

// Initialize $PAGE
$PAGE->set_url($thisdir . 'index.php');
$PAGE->set_context($syscontext);
$PAGE->set_heading(get_string('pluginname', 'tool_uclasiteindicator'));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');

// Prepare and load Moodle Admin interface
admin_externalpage_setup('uclasiteindicator');

// Render page
echo $OUTPUT->header();

// Heading
echo $OUTPUT->heading(get_string('sitetypes', 'tool_uclasiteindicator'), 2, 'headingblock');
echo html_writer::link($baseurl . '/index.php', get_string('back', 'tool_uclasiteindicator'));

$table = new html_table();
    
// prepare table sorting functionality
$table->id = setup_js_tablesorter('uclasiteindicator_sitetypes_report');
    
$table->attributes['class'] = 'generaltable';
$table->align = array('left', 'left', 'left');
$table->head = array(get_string('type', 'tool_uclasiteindicator'), 
    get_string('shortname'), get_string('description'), 
    get_string('roles', 'tool_uclasiteindicator'));

$siteindicator_manager = new siteindicator_manager();
$types = $siteindicator_manager->get_types_list();

foreach($types as $type) {    
    $row = array();
    $row[] = $type['fullname'];
    $row[] = $type['shortname'];
    $row[] = $type['description'];

    // get roles
    $roles = $siteindicator_manager->get_assignable_roles($type['shortname']);    
    $row[] = implode(', ', $roles);

    $table->data[] = $row;
}
// Display indicator types
echo html_writer::table($table);

echo $OUTPUT->footer();
