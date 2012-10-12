<?php
require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $PAGE;

require_once($CFG->dirroot . '/blocks/ucla_alert/block_ucla_alert.php');
require_once($CFG->dirroot . '/blocks/ucla_alert/ucla_alert_form.php');

require_login();

$courseid = required_param('id', PARAM_INT);

$PAGE->set_url('/blocks/ucla_alert/edit.php');

$PAGE->set_course($SITE);

$PAGE->set_pagetype('site-index');
$PAGE->set_pagelayout('coursecategory');

$PAGE->set_title('Editing the alerts block');
$PAGE->navbar->add('Editing the alerts block');

// I have no idea when this is used...
$PAGE->set_heading($SITE->fullname);

// Load YUI script
$PAGE->requires->js('/blocks/ucla_alert/alert.js');
$PAGE->requires->js_init_call('M.alert_block.init', array());

echo $OUTPUT->header();

?>
<style type="text/css" media="screen">
    .yui3-dd-proxy {
        text-align: left;
    }

</style>


<?php 

if(intval($courseid) == SITEID) {
    $alertedit = new ucla_alert_block_editable_site($courseid);
} else {
    $alertedit = new ucla_alert_block_editable($courseid);
}

echo $alertedit->render();

echo $OUTPUT->footer();
