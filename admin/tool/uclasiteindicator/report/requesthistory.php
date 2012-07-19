<?php
/**
 * UCLA Site indicator: Request history
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
echo $OUTPUT->heading(get_string('requesthistory', 'tool_uclasiteindicator'), 2, 'headingblock');
echo html_writer::link($baseurl . '/index.php', get_string('back', 'tool_uclasiteindicator'));

$history = siteindicator_manager::get_request_history();

if (empty($history)) {
    echo html_writer::tag('p', get_string('norequesthistory', 'tool_uclasiteindicator'));
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable';
    $table->head = array(get_string('type', 'tool_uclasiteindicator') . ' (' . 
        count($history) . ')', get_string('category'), 
        get_string('shortname') . ' / ' . get_string('fullname'), 
        get_string('site_requester', 'tool_uclasiteindicator'), 
        get_string('site_status', 'tool_uclasiteindicator'));

    foreach($history as $h) {
        $row = array();
        $row[] = $h->type;
        $row[] = siteindicator_manager::get_categories_list($h->categoryid);
        $name = siteindicator_manager::get_username($h->requester);

        if($h->courseid) {
            $course = $DB->get_record('course', array('id' => $h->courseid));

            // Site name
            $sitename = $course->fullname;
            $link = html_writer::link($CFG->wwwroot . '/course/view.php?id=' . $h->courseid, $sitename);
            $row[] = html_writer::tag('span', $course->shortname . '<br/>' . $link);

            // Requester
            $row[] = html_writer::link($CFG->wwwroot . '/user/profile.php?id=' . $h->requester, $name);

            // Site status
            $link = html_writer::link($CFG->wwwroot . '/course/view.php?id=' . $h->courseid, 'Active');
            $row[] = html_writer::tag('span', $link, array('class' => 'indicator-active indicator-block'));
        } else if($h->requestid) {
            $course = $DB->get_record('course_request', array('id' => $h->requestid));

            // Site name
            $sitename = $course->shortname . '<br/>' . $course->fullname;
            $row[] = html_writer::tag('span', $sitename);

            // Requester
            $row[] = html_writer::link($CFG->wwwroot . '/user/profile.php?id=' . $h->requester, $name);

            // Status
            $link = html_writer::link($CFG->wwwroot . '/course/pending.php?request=' . $h->requestid, 'Pending');
            $row[] = html_writer::tag('span', $link, array('class' => 'indicator-pending indicator-block'));
        } else {
            // Site name is empty
            $row[] = html_writer::tag('span', '&lt;empty&gt;');
            // Requester
            $row[] = html_writer::link($CFG->wwwroot . '/user/profile.php?id=' . $h->requester, $name);
            // Site status
            $row[] = html_writer::tag('span', 'Rejected', array('class' => 'indicator-reject indicator-block'));
        }

        $table->data[] = $row;
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
