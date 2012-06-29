<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com     //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Allow the administrator to look through a list of course requests and approve or reject them.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package course
 */

require_once(dirname(__FILE__) . '/../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/course/request_form.php');

require_login();
require_capability('moodle/site:approvecourse', get_context_instance(CONTEXT_SYSTEM));

$approve = optional_param('approve', 0, PARAM_INT);
$reject = optional_param('reject', 0, PARAM_INT);
$request = optional_param('request', 0, PARAM_INT);

$baseurl = $CFG->wwwroot . '/course/pending.php';
admin_externalpage_setup('coursespending');

/// Process approval of a course.
if (!empty($approve) and confirm_sesskey()) {
    /// Load the request.
    $course = new course_request($approve);
    $courseid = $course->approve();

    if ($courseid !== false) {
        // START UCLAMOD CCLE-2389
        ucla_site_indicator::create($courseid, $approve);
        // END UCLAMOD CCLE-2389
        redirect($CFG->wwwroot.'/course/edit.php?id=' . $courseid);
    } else {
        print_error('courseapprovedfailed');
    }
}

/// Process rejection of a course.
if (!empty($reject)) {
    // Load the request.
    $course = new course_request($reject);

    // Prepare the form.
    $rejectform = new reject_request_form($baseurl);
    $default = new stdClass();
    $default->reject = $course->id;
    $rejectform->set_data($default);
    
/// Standard form processing if statement.
    if ($rejectform->is_cancelled()){
        redirect($baseurl);

    } else if ($data = $rejectform->get_data()) {
        // START UCLAMOD CCLE-2389 - reject a collab site request
        /// Reject the request
        if($data->email) {
            $course->reject($data->rejectnotice);
        } else {
            $course->delete();
        }
        ucla_site_indicator::reject($course->id);

        /// Redirect back to the course listing.
        redirect($baseurl, get_string('courserejected', 'tool_uclasiteindicator'));
        // END UCLAMOD CCLE-2389
    }

/// Display the form for giving a reason for rejecting the request.
    echo $OUTPUT->header($rejectform->focus());
    $rejectform->display();
    echo $OUTPUT->footer();
    exit;
}

/// Print a list of all the pending requests.
echo $OUTPUT->header();

// START UCLA MOD CCLE-2389 - show only a requested course
if(!empty($request)) {
    $pending = $DB->get_records('course_request', array('id' => $request));
} else {
    $pending = $DB->get_records('course_request');
}
// END UCLA MOD CCLE-2389
if (empty($pending)) {
    echo $OUTPUT->heading(get_string('nopendingcourses', 'tool_uclasiteindicator'));
} else {
    echo $OUTPUT->heading(get_string('coursespending', 'tool_uclasiteindicator'));

/// Build a table of all the requests.
    $table = new html_table();
    $table->attributes['class'] = 'pendingcourserequests generaltable';
    $table->align = array('center', 'center', 'center', 'center', 'center', 'center');
    // START UCLA MOD CCLE-2389 - override table strings and add a site type & requested category columns
    $table->head = array(get_string('shortnamecourse', 'tool_uclasiteindicator'), get_string('fullnamecourse', 'tool_uclasiteindicator'),
            get_string('sitetype', 'tool_uclasiteindicator'), get_string('sitecat', 'tool_uclasiteindicator'),
            get_string('requestedby'), get_string('summary'), get_string('requestreason', 'tool_uclasiteindicator'), get_string('action'));
    // END UCLA MOD CCLE-2389

    foreach ($pending as $course) {
        $course = new course_request($course);

        // Check here for shortname collisions and warn about them.
        $course->check_shortname_collision();
        
        // START UCLA MOD CCLE-2389 - Get site request obj
        $ireq = new site_indicator_request($course->id);

        $row = array();
        $row[] = format_string($course->shortname);
        $row[] = format_string($course->fullname);
        // Set site type and requested category
        $row[] = $ireq->get_type_string();
        $row[] = $ireq->get_category_string();
        // END UCLA MOD CCLE-2389
        $row[] = fullname($course->get_requester());
        $row[] = $course->summary;
        $row[] = format_string($course->reason);
        $row[] = $OUTPUT->single_button(new moodle_url($baseurl, array('approve' => $course->id, 'sesskey' => sesskey())), get_string('approve'), 'get') .
                 $OUTPUT->single_button(new moodle_url($baseurl, array('reject' => $course->id)), get_string('rejectdots'), 'get');

    /// Add the row to the table.
        $table->data[] = $row;
    }

/// Display the table.
    echo html_writer::table($table);

/// Message about name collisions, if necessary.
    if (!empty($collision)) {
        print_string('shortnamecollisionwarning');
    }
}

/// Finish off the page.
// START UCLA MOD CCLE-2389 - redirect to homepage instead
echo $OUTPUT->single_button($CFG->wwwroot . '/my/', get_string('backtocourselisting', 'tool_uclasiteindicator'));
// END UCLA MOD CCLE-2389
echo $OUTPUT->footer();
