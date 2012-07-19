<?php
/**
 * UCLA Site indicator: Orphan site report
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
echo $OUTPUT->heading(get_string('orphans', 'tool_uclasiteindicator'), 2, 'headingblock');
echo html_writer::link($baseurl . '/index.php', get_string('back', 'tool_uclasiteindicator'));

$orphans = siteindicator_manager::get_orphans();

if (empty($orphans)) {
    echo html_writer::tag('p', get_string('noorphans', 'tool_uclasiteindicator'));
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable';
    $table->align = array('left', 'left');
    $table->head = array(get_string('shortname') . ' (' . count($orphans) . ')', get_string('fullname'));

    foreach($orphans as $orphan) {
        $row = array();
        $row[] = html_writer::link(new moodle_url($CFG->wwwroot . 
                '/course/view.php', array('id' => $orphan->id)), 
                $orphan->shortname, array('target' => '_blank'));
        $row[] = $orphan->fullname;
        
        $table->data[] = $row;
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
