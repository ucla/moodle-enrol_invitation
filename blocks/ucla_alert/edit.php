<?php


require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $PAGE;

require_once($CFG->dirroot . '/blocks/ucla_alert/block_ucla_alert.php');
require_once($CFG->dirroot . '/blocks/ucla_alert/ucla_alert_form.php');

require_login();

$PAGE->set_url('/blocks/ucla_alert/edit.php');

$PAGE->set_course($SITE);

$PAGE->set_pagetype('site-index');
$PAGE->set_pagelayout('coursecategory');

$PAGE->set_title('Editing the alerts block');
$PAGE->navbar->add('Editing the alerts block');

// I have no idea when this is used...
$PAGE->set_heading($SITE->fullname);

// Load YUI script
block_ucla_alert::alert_edit_js();

echo $OUTPUT->header();

// Alert add message form
$alert_add_form = new ucla_alert_add_form();
$alert_add_form->display();

if($data = $alert_add_form->get_data()) {

    $data->type = '';
    
    $record = new stdClass();
    $record->module = 'body';
    $record->type = 'default';
    $record->visible = 0;
    $record->sortorder = 1000;
    $record->content = json_encode($data);
    
    $DB->insert_record('ucla_alert', $record);
}

// Alert edit (Y)UI
echo block_ucla_alert::write_alert_edit_ui();

echo $OUTPUT->footer();
