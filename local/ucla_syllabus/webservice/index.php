<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Administrator interface to web service.
 * 
 * @package     local_ucla_syllabus
 * @subpackage  webservice
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$thisdir = '/local/ucla_syllabus/webservice/';
require_once($CFG->dirroot . $thisdir . 'lib.php');
require_once($CFG->dirroot . $thisdir . 'webservice_form.php');
require_once($CFG->dirroot . $thisdir . 'client.php');

$statusupdate = optional_param('status_update', 0, PARAM_BOOL);
$delete = optional_param('delete', 0, PARAM_BOOL);

$baseurl = $CFG->wwwroot . '/local/ucla_syllabus/webservice/index.php';

require_login();

$syscontext = get_context_instance(CONTEXT_SYSTEM);

$thisfile = $thisdir . 'index.php';

// Initialize $PAGE variable.
$PAGE->set_url($thisdir . $thisfile);
$PAGE->set_context($syscontext);
$PAGE->set_heading('Syllabus web service');
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');

// Prepare and load Moodle Admin interface.
admin_externalpage_setup('ucla_syllabus_webservice');

// Process web service parameters.
if (!empty($statusupdate) and confirm_sesskey()) {
    $record = new stdClass();
    $record->id = required_param('status_id', PARAM_INT);
    $record->enabled = required_param('status_set', PARAM_INT);
    syllabus_ws_manager::update_subscription($record);
}

if (!empty($delete) and confirm_sesskey()) {
    $id = required_param('delete_id', PARAM_INT);
    syllabus_ws_manager::delete_subscription($id);
}

// Process form for forms.
$subjareas = syllabus_ws_manager::get_subject_areas();
$wsform = new syllabus_ws_form(null, array('subjareas' => $subjareas));

if ($wsform->is_cancelled()) {
    redirect($baseurl);

} else if ($data = $wsform->get_data()) {
    syllabus_ws_manager::add_subscription($data);
    redirect($baseurl);
}

// Prepare table sorting functionality.
$tableid = setup_js_tablesorter('syllabus_webservice');

// Render page.
echo $OUTPUT->header();

// Render heading.
echo $OUTPUT->heading(get_string('heading', 'local_ucla_syllabus'), 2, 'headingblock');

// Display form.
$wsform->display();

echo $OUTPUT->box_start('generalbox');
echo $OUTPUT->heading('Subscribers');

$table = new html_table();
$table->id = $tableid;
$table->attributes['class'] = 'generaltable';
$table->head = array(get_string('subject_area', 'local_ucla_syllabus'),
        get_string('leading_srs', 'local_ucla_syllabus'),
        get_string('post_url', 'local_ucla_syllabus'),
        get_string('token', 'local_ucla_syllabus'),
        get_string('contact_email', 'local_ucla_syllabus'),
        get_string('select_action', 'local_ucla_syllabus'),
        get_string('status', 'local_ucla_syllabus'),
        get_string('delete', 'local_ucla_syllabus'));

$subscriptions = syllabus_ws_manager::get_subscriptions();
$actions = syllabus_ws_manager::get_event_actions();

foreach ($subscriptions as $s) {
    $row = array();
    $row[] = empty($s->subjectarea) ? '' : $subjareas[$s->subjectarea];
    $row[] = $s->leadingsrs;
    $row[] = $s->url;
    $row[] = $s->token;
    $row[] = $s->contact;
    $row[] = $actions[$s->action];

    // Add status enable/disable.
    $label = empty($s->enabled) ? get_string('enable', 'local_ucla_syllabus') : get_string('disable', 'local_ucla_syllabus');
    $url = new moodle_url($baseurl,
            array('status_update' => 1, 'status_set' => !$s->enabled, 'status_id' => $s->id));
    $row[] = $OUTPUT->single_button($url, $label);

    // Add delete.
    $url = new moodle_url($baseurl, array('delete' => 1, 'delete_id' => $s->id));
    $row[] = $OUTPUT->single_button($url, get_string('delete', 'local_ucla_syllabus'));

    $table->data[] = $row;
}

// Display subscribers.
echo html_writer::table($table);

echo $OUTPUT->box_end();

echo $OUTPUT->box_start('generalbox');
echo $OUTPUT->heading('Client test data');

$data = syllabus_ws_client::get_data();

$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->head = array('action', 'time', 'srs', 'term', 'token', 'url', 'file name', 'filesize');

foreach ($data as $d) {

    $d = (object)$d;

    $row = array();
    $row[] = $d->action;
    $row[] = date('Y-m-d H:i:s', $d->timestamp);
    $row[] = empty($d->srs) ? 'none' : $d->srs;
    $row[] = empty($d->term) ? 'none' : $d->term;
    $row[] = $d->token;
    $row[] = empty($d->url) ? 'none' : $d->url;
    $row[] = empty($d->filename) ? 'none' : $d->filename;
    $row[] = empty($d->filesize) ? '0' : $d->filesize;

    $table->data[] = $row;
}

echo html_writer::table($table);

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
