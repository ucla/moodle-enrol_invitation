<?php

global $CFG, $PAGE, $USER, $DB;

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/admin/tool/uclabulkcoursereset/bulkcoursereset_form.php');
require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');
require_once($CFG->dirroot . '/course/reset_form.php');

require_login();

// Set up $PAGE
$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_heading(get_string('pluginname', 'tool_uclabulkcoursereset'));
$PAGE->set_pagetype('admin-*');
$PAGE->set_pagelayout('admin');
$PAGE->set_url($CFG->dirroot . '/admin/tool/uclabulkcoursereset/index.php');

admin_externalpage_setup('uclabulkcoursereset');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'tool_uclabulkcoursereset'), 2, 'headingblock');

// Get collab sites of type "test" from site indicator
$collab_sites = siteindicator_manager::get_sites();

$courses = array();
foreach ($collab_sites as $site) {
    if ($site->type == 'test') {
        $courses[] = $site;
    }
}

$course_list = array();
foreach ($courses as $course) {
    $course_list[$course->id] = $course->fullname;
}

// Create the form for selecting collab sites to reset
$selectform = new bulkcoursereset_form(NULL, array('course_list' => $course_list, 'course_selected' => NULL));

if ($selectform->is_cancelled()) {
    // do nothing?
    //$redirect = new moodle_url('/my');
    //redirect($redirect);
} else if ($data = $selectform->get_data()) {
    
    // Copied and modified from course/reset.php
    if (isset($data->selectdefault)) {
        $_POST = array();
        $selectform = new bulkcoursereset_form(NULL, 
                array('course_list' => $course_list, 'course_selected' => $data->course_list));
        $selectform->load_defaults();

    } else if (isset($data->deselectall)) {
        $_POST = array();
        $selectform = new bulkcoursereset_form(NULL, 
                array('course_list' => $course_list, 'course_selected' => NULL));

    } else {
        
        foreach ($data->course_list as $courseid) {
            $reset_data = $data;
            unset($reset_data->course_list);
            $reset_data->reset_start_date_old = $DB->get_record('course', array('id' => $courseid))->startdate;
            $status = reset_course_userdata($reset_data);
            
            $reset_data = array();
            foreach ($status as $item) {
                $line = array();
                $line[] = $item['component'];
                $line[] = $item['item'];
                $line[] = ($item['error']===false) ? get_string('ok') : '<div class="notifyproblem">'.$item['error'].'</div>';
                $reset_data[] = $line;
            }
            
            $table = new html_table();
            $table->head  = array(get_string('resetcomponent'), get_string('resettask'), get_string('resetstatus'));
            $table->size  = array('20%', '40%', '40%');
            $table->align = array('left', 'left', 'left');
            $table->width = '80%';
            $table->data  = $reset_data;
            echo html_writer::table($table);
        }
        echo $OUTPUT->continue_button('/admin/tool/uclabulkcoursereset/index.php');
        echo $OUTPUT->footer();
        exit;
        
    }
}

$selectform->display();

echo $OUTPUT->footer();
