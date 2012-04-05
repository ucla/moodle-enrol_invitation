<?php
/* Build Now Site page for Course Creator*/
require(dirname(_FILE_).'/../../../config.php');
require($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot . '/admin/tool/uclacoursecreator/uclacoursecreator.class.php');
require_once($CFG->dirroot . '/admin/tool/uclacourserequestor/lib.php');

$thisdir = '/' . $CFG->admin . 'tool/uclacoursecreator/';
$thisfile = $thisdir . 'index.php';
$syscontext = get_context_instance(CONTEXT_SYSTEM);
$rucr = 'tool_uclacoursecreator';

$PAGE->set_url($thisdir . $thisfile);
$PAGE->set_context($syscontext);
$PAGE->set_heading(get_string('pluginname2', $rucr));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');

admin_externalpage_setup('uclacoursecreator');
$course = new uclacoursecreator();
$terms = get_requestor_view_fields();

echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox');
echo $OUTPUT->heading('Set Terms to Build Course');

echo html_writer::start_tag('form', array(
        'method' => 'POST',
        'action' => $PAGE->url
    ));

echo html_writer::select($terms['term'], 'termlist',
	'', array('' =>'Choose...'
    ));

echo html_writer::tag('input', '', array(
	'type' => 'submit',
	'name' => 'inputterm',
	'value' => get_string('checkterms', $rucr),
    ));

echo html_writer::end_tag('form');

if($_POST['termlist'] != '') {
	$course->set_term_list(array($_POST['termlist']));
	$course->set_cron_term($_POST['termlist']);
	$course->cron();
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
//EOF
