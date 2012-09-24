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
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    local
 * @subpackage ucla_syllabus
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/syllabus_form.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->libdir . '/resourcelib.php');   // for embedding code

// get script variables to be used later
$id = required_param('id', PARAM_INT);   // course
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$ucla_syllabus_manager = new ucla_syllabus_manager($course);
$coursecontext = context_course::instance($course->id);
$can_manage_syllabus = $ucla_syllabus_manager->can_manage();

require_course_login($course);

// setup page
$PAGE->set_url('/local/ucla_syllabus/index.php', array('id' => $id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);
$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);

// set editing button
if ($can_manage_syllabus) {
    $url = new moodle_url('/local/ucla_syllabus/index.php',
                    array('id' => $course->id));
    set_editing_mode_button($url);
    
    // setup form
    $syllabus_form = new syllabus_form(null, 
            array('courseid' => $course->id, 
                  'ucla_syllabus_manager' => $ucla_syllabus_manager),
            'post',
            '',
            array('class' => 'syllabus_form'));    

    // If the cancel button is clicked, return to non-editing mode of syllabus page
    if ($syllabus_form->is_cancelled()) { 
        $url = new moodle_url('/local/ucla_syllabus/index.php', 
                array('id' => $course->id,
                      'sesskey' => sesskey(),
                      'edit' => 'off'));
        redirect($url);
    }
}
    

if ($USER->editing && $can_manage_syllabus) {    
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('syllabus_manager', 'local_ucla_syllabus'), 2, 'headingblock');        
    
    // User is editing, so process or display form
    $data = $syllabus_form->get_data();
    if (!empty($data) && confirm_sesskey()) {
        $result = $ucla_syllabus_manager->save_syllabus($data);        
        if ($result) {
            // upload was successful, give success message to user            
            $OUTPUT->notification(get_string('successful_upload', 'local_ucla_syllabus'), 'notifysuccess');
        }        
    } else {        
        $syllabus_form->display();
    }
    
} else {
    // else just display syllabus
    echo $OUTPUT->header();
    $title = ''; $body = '';

    $syllabi = $ucla_syllabus_manager->get_syllabi();
    
    // see if there is a public syllabus uploaded
    $public_syllabus = $syllabi['public'];
    if (empty($public_syllabus)) {
        // no public syllabus, so display no info
        $title = get_string('display_name_default', 'local_ucla_syllabus');
        $body = html_writer::tag('p', get_string('no_syllabus_uploaded', 'local_ucla_syllabus'));
        
        // if user can upload a syllabus, let them know about turning editing on
        if ($can_manage_syllabus) {
            $body .= html_writer::tag('p', 
                    get_string('no_syllabus_uploaded_help', 'local_ucla_syllabus'));
        }
    } else {
        $title = $public_syllabus->display_name;

        $fullurl = $public_syllabus->get_file_url();
        $mimetype = $public_syllabus->get_mimetype();
        $download_link = $public_syllabus->get_download_link();        

        // try to embed file using resource functions
        if ($mimetype === 'application/pdf') {
            $body .= resourcelib_embed_pdf($fullurl, $title, $download_link);
        } else {            
            $body .= resourcelib_embed_general($fullurl, $title, $download_link, $mimetype);
        }
        
        // also add download link
        $body .= html_writer::tag('div', $download_link, array('id' => 'download_link'));        
    }
    
    // now display content
    echo $OUTPUT->heading($title, 2, 'headingblock');   
    echo $OUTPUT->container($body, 'ucla_syllabus-container');
    
    // log for statistics later
    add_to_log($course->id, 'ucla_syllabus', 'view', 'index.php?id='.$course->id, '');    
}

echo $OUTPUT->footer();
