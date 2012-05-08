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

$types = $DB->get_records('ucla_indicator_type');

if(empty($types)) {
    ucla_indicator_admin::pre_populate_sql();
    echo html_writer::tag('p', 'Tables were pre-poluated.');
    echo $OUTPUT->single_button(new moodle_url($baseurl), 'Continue', 'get');
    
} else {

    $roles = $DB->get_records('ucla_indicator_siteroles');

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

    $role_table = new html_table();
    $role_table->attributes['class'] = 'generaltable';
    $role_table->align = array('center', 'left');
    $role_table->head = array('id', 'Type roles');

    foreach($roles as $role) {
        $role_table->data[] = $role;
    }

    $assignform = new siteindicator_form(null, array('roles' => $roles));

    if($data = $assignform->get_data()) {

        // Delete all records
        $DB->delete_records('ucla_indicator_assign');

        foreach($data->instruction as $i) {
            $rec = new stdClass();
            $rec->siteroleid = 1;
            $rec->roleid = $i;
            $DB->insert_record('ucla_indicator_assign', $rec);
        }

        foreach($data->project as $p) {
            $rec = new stdClass();
            $rec->siteroleid = 2;
            $rec->roleid = $p;
            $DB->insert_record('ucla_indicator_assign', $rec);
        }

        foreach($data->test as $t) {
            $rec = new stdClass();
            $rec->siteroleid = 3;
            $rec->roleid = $t;
            $DB->insert_record('ucla_indicator_assign', $rec);
        }
    }

    // Display indicator types
    echo html_writer::table($table);
    // Display indicator roles
    echo html_writer::table($role_table);

    $assignform->display();
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();

