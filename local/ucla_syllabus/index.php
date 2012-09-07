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
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/syllabus_form.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

// get script variables to be used later
$id = required_param('id', PARAM_INT);   // course
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);
$can_manage_syllabus = has_capability('local/ucla_syllabus:managesyllabus', $coursecontext);

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
    
    $maxbytes = get_max_upload_file_size();
    $filemanager_config = array('subdirs' => 0, 'maxbytes' => $maxbytes, 'maxfiles' => 1,
                      'accepted_types' => array('.pdf'));
    
    // setup form
    $syllabus_form = new syllabus_form(null, 
            array('courseid' => $course->id, 'filemanager_config' => $filemanager_config),
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
    if ($data = $syllabus_form->get_data() && confirm_sesskey()) {
        
        // first create a entry in ucla_syllabus
        $ucla_syllabus_entry = new stdClass();
        $ucla_syllabus_entry->courseid      = $data->id;
        $ucla_syllabus_entry->display_name  = $data->display_name;
        $ucla_syllabus_entry->access_type   = $data->access_types['access_type'];
        $ucla_syllabus_entry->is_preview    = isset($data->is_preview) ? 1 : 0;

        $insertedid = $DB->insert_record('ucla_syllabus', $ucla_syllabus_entry);        
        if (empty($insertedid)) {        
            print_error(get_string('cannnot_make_db_entry', 'local_ucla_syllabus'));
        }
        
        // then save file, with link to ucla_syllabus
        file_save_draft_area_files($data->public_syllabus_file, 
                $coursecontext->id, 'local_ucla_syllabus', 'syllabus', 
                $insertedid, $filemanager_config);
        
        // upload was successful, give success message to user
        $OUTPUT->notification(get_string('successful_upload', 'local_ucla_syllabus'), 'notifysuccess');
            
    } else {        
        $syllabus_form->display();
    }
    
} else {
    // else just display syllabus
    echo $OUTPUT->header();
        
    // log for statistics later
    add_to_log($course->id, 'ucla_syllabus', 'view', 'index.php?id='.$course->id, '');    
}

echo $OUTPUT->footer();
