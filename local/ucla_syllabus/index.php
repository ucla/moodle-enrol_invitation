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

// see if user wants to do an action for a given syllabus type
$action = optional_param('action', null, PARAM_ALPHA);
$type = optional_param('type', null, PARAM_ALPHA);

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
                  'action' => $action,
                  'type' => $type,
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
    

if (!empty($USER->editing) && $can_manage_syllabus) {        
    // User uploaded/edited a syllabus file, so handle it
    $data = $syllabus_form->get_data();
    if (!empty($data) && confirm_sesskey()) {
        $result = $ucla_syllabus_manager->save_syllabus($data);        
        if ($result) {
            // upload was successful, give success message to user (redirect to
            // refresh site menu and prevent duplication submission of file)

            $url = new moodle_url('/local/ucla_syllabus/index.php',
                    array('action' => UCLA_SYLLABUS_ACTION_VIEW,
                          'id' => $course->id));
            if (isset($data->entryid)) {
                // syllabus was updated
                $success_msg = get_string('successful_update', 'local_ucla_syllabus');     
            } else {
                // syllabus was added
               $success_msg = get_string('successful_add', 'local_ucla_syllabus');                
            }

            flash_redirect($url, $success_msg);
        }       
    }  else if ($action == UCLA_SYLLABUS_ACTION_DELETE) {
        // user wants to delete syllabus
        $syllabi = $ucla_syllabus_manager->get_syllabi();        
        $todel = null;        
        
        if ($type == UCLA_SYLLABUS_TYPE_PUBLIC && !empty($syllabi[UCLA_SYLLABUS_TYPE_PUBLIC])) {
            $todel = $syllabi[UCLA_SYLLABUS_TYPE_PUBLIC];
        } else if ($type == UCLA_SYLLABUS_TYPE_PRIVATE && !empty($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE])) {
            $todel = $syllabi[UCLA_SYLLABUS_TYPE_PRIVATE];
        }
        
        if (empty($todel)) {
            print_error('err_syllabus_notexist', 'local_ucla_syllabus');
        } else {
            $ucla_syllabus_manager->delete_syllabus($todel);
            
            $url = new moodle_url('/local/ucla_syllabus/index.php',
                    array('action' => UCLA_SYLLABUS_ACTION_VIEW,
                          'id' => $course->id));
            $success_msg = get_string('successful_delete', 'local_ucla_syllabus');
            flash_redirect($url, $success_msg);            
        }
    } else if ($action == UCLA_SYLLABUS_ACTION_CONVERT) {
        // User is converting between public or private syllabus
        $syllabi = $ucla_syllabus_manager->get_syllabi();
        
        $convertto = 0;
        $fromto = new StdClass();
        $fromto->old = $type;
        if ($type == UCLA_SYLLABUS_TYPE_PUBLIC) {
            $convertto = UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE;
            $fromto->new = UCLA_SYLLABUS_TYPE_PRIVATE;
        } else if ($type == UCLA_SYLLABUS_TYPE_PRIVATE) {
            // Using the stricter version of public - require user login
            $convertto = UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN;
            $fromto->new = UCLA_SYLLABUS_TYPE_PUBLIC;
        }
        
        if ($convertto == 0) {
             print_error('err_syllabus_notexist', 'local_ucla_syllabus');
        } else {
            $ucla_syllabus_manager->convert_syllabus($syllabi[$type], $convertto);
            
            $url = new moodle_url('/local/ucla_syllabus/index.php',
                    array('action' => UCLA_SYLLABUS_ACTION_VIEW,
                          'id' => $course->id));
            $success_msg = get_string('successful_convert', 'local_ucla_syllabus', $fromto);
            flash_redirect($url, $success_msg);
        }
    }

    display_header(get_string('syllabus_manager', 'local_ucla_syllabus'));    
    $syllabus_form->display();
    
} else {
    // else just display syllabus
    $title = ''; $body = '';

    $syllabi = $ucla_syllabus_manager->get_syllabi();

     $syllabus_to_display = null;
    if (!empty($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE]) &&
            $syllabi[UCLA_SYLLABUS_TYPE_PRIVATE]->can_view()) {
        // see if logged in user can view private syllabus
        $syllabus_to_display = $syllabi[UCLA_SYLLABUS_TYPE_PRIVATE];
    } else if (!empty($syllabi[UCLA_SYLLABUS_TYPE_PUBLIC]) &&
            $syllabi[UCLA_SYLLABUS_TYPE_PUBLIC]->can_view()) {
        // fallback on trying to see if user can view public syllabus
        $syllabus_to_display = $syllabi[UCLA_SYLLABUS_TYPE_PUBLIC];
    }
    
    // setup what to display
    if (empty($syllabus_to_display)) {
        // no syllabus, so display no info
        $title = get_string('display_name_default', 'local_ucla_syllabus');

        $error_string = '';
        if (!empty($syllabi[UCLA_SYLLABUS_TYPE_PUBLIC])) {
            $error_string = get_string('cannot_view_public_syllabus', 'local_ucla_syllabus');
        } else if (!empty($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE])) {
            $error_string = get_string('cannot_view_private_syllabus', 'local_ucla_syllabus');
        } else {
            $error_string = get_string('no_syllabus_uploaded', 'local_ucla_syllabus');
        }

        $body = html_writer::tag('p', $error_string, array('class' => 'no_syllabus'));

        // if user can upload a syllabus, let them know about turning editing on
        if ($can_manage_syllabus) {
            $body .= html_writer::tag('p', 
                    get_string('no_syllabus_uploaded_help', 'local_ucla_syllabus'));
        }        
    } else {
        $title = $syllabus_to_display->display_name;

        $fullurl = $syllabus_to_display->get_file_url();
        $mimetype = $syllabus_to_display->get_mimetype();
        $clicktoopen = get_string('err_noembed', 'local_ucla_syllabus');
        $download_link = $syllabus_to_display->get_download_link();        

        // try to embed file using resource functions
        if ($mimetype === 'application/pdf') {
            $body .= resourcelib_embed_pdf($fullurl, $title, $clicktoopen);
        } else {            
            $body .= resourcelib_embed_general($fullurl, $title, $clicktoopen, $mimetype);
        }

        // also add download link
        $body .= html_writer::tag('div', $download_link, array('id' => 'download_link')); 
        
        // if this is a preview syllabus, give some disclaimer text
        // add some disclaimer text for public syllabus
        if ($syllabus_to_display instanceof ucla_public_syllabus) {
            $title .= '*';
            $disclaimer_text = '';
            if ($syllabus_to_display->is_preview) {
                $disclaimer_text = get_string('preview_disclaimer', 'local_ucla_syllabus');

            } else {                
                $disclaimer_text = get_string('public_disclaimer', 'local_ucla_syllabus');
            }
            $body .= html_writer::tag('p', '*' . $disclaimer_text, 
                    array('class' => 'syllabus_disclaimer'));
        }        
    }
    
    // now display content
    display_header($title);
    echo $OUTPUT->container($body, 'ucla_syllabus-container');
    
    // log for statistics later
    add_to_log($course->id, 'ucla_syllabus', 'view', 'index.php?id='.$course->id, '');    
}

echo $OUTPUT->footer();

// SCRIPT FUNCTIONS

function display_header($page_title) {
    global $OUTPUT;
    echo $OUTPUT->header();
    echo $OUTPUT->heading($page_title, 2, 'headingblock');           
    flash_display();    // display any success messages
}