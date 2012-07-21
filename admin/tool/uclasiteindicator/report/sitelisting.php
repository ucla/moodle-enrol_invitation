<?php
/**
 * UCLA Site indicator: Site listing report
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
echo $OUTPUT->heading(get_string('sitelisting', 'tool_uclasiteindicator'), 2, 'headingblock');
echo html_writer::link($baseurl . '/index.php', get_string('back', 'tool_uclasiteindicator'));

$sites = siteindicator_manager::get_sites();

if (empty($sites)) {
    echo html_writer::tag('p', get_string('nositelisting', 'tool_uclasiteindicator'));
} else {
    $table = new html_table();
    
    // prepare table sorting functionality
    $table->id = setup_js_tablesorter('uclasiteindicator_sitelisting_report');
    
    $table->attributes['class'] = 'generaltable';
    $table->align = array('left', 'left');
    $table->head = array(get_string('shortname') . ' (' . count($sites) . ')', 
        get_string('category'), get_string('fullname'),
        get_string('type', 'tool_uclasiteindicator'));

    $category_cache = array();
    
    foreach($sites as $site) {
        $row = array();
        $row[] = html_writer::link(new moodle_url($CFG->wwwroot . 
                '/course/view.php', array('id' => $site->id)), 
                $site->shortname, array('target' => '_blank'));

        // print category
        if (empty($category_cache[$site->category])) {
            $category_cache[$site->category] = 
                    siteindicator_manager::get_categories_list($site->category);  
        }        
        $row[] = $category_cache[$site->category];
        
        $row[] = $site->fullname;
        $row[] = $site->type;
        
        $table->data[] = $row;
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
