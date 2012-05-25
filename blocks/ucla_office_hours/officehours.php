<?php

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $PAGE, $USER, $DB;

require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.
    '/blocks/ucla_office_hours/block_ucla_office_hours.php');
require_once($CFG->dirroot.
        '/blocks/ucla_office_hours/update_officehours_form.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot.'/user/lib.php');

$course_id = optional_param('course_id', 0, PARAM_INT); 
$edit_id = optional_param('edit_id', 0, PARAM_INT); 

if($course_id) {
    if ($course_id == SITEID){
        print_error('cannoteditsiteform');
    }
    //$user_edit = $DB->get_record('user', array('id'=>$edit_id), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);

    require_login($course, true);
    $context = get_context_instance(CONTEXT_COURSE, $course_id);

    $PAGE->set_url('/blocks/ucla_office_hours/officehours.php', 
        array('course_id' => $course_id, 'edit_id' => $edit_id));
    
    //$PAGE->url->param('id',$course_id);
    //$PAGE->url->param('edit',$edit_id);

    $page_title = $course->shortname.': '.get_string('pluginname',
        'block_ucla_office_hours');

    $PAGE->set_context($context);
    $PAGE->set_title($page_title);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_pagelayout('course');
    $PAGE->set_pagetype('course-view-'.$course->format);

    set_editing_mode_button();

    $PAGE->navigation->initialise();
    $PAGE->navbar->add(get_string('header', 'block_ucla_office_hours'));
} else { //If invalid course id, then redirect to homepage
    $default_url = new moodle_url($CFG->wwwroot);
    redirect($default_url);
}

$context = get_context_instance(CONTEXT_COURSE, $course_id);
$PAGE->set_context($context);

$updateform = new officehours_form(NULL, array('course_id' => $course_id, 'edit_id' => $edit_id));
if ($updateform->is_cancelled()) { //If the cancel button is clicked, return to 'Site Info' page
    $url = new moodle_url($CFG->wwwroot.'/course/view.php', array('id'=>$course_id));
    redirect($url);
} else if($data = $updateform->get_data()) { //Otherwise, process data
    print_object($data);
    
    //If this course/user pair is not in the database, attempt to add it in
    if($DB->get_record('ucla_officehours', 
        array('courseid' => $course_id, 'userid' => $edit_id)) ){
        ;//Course/user pair already in database. Do nothing
    } else if(! $DB->insert_record('ucla_officehours', 
            array('courseid' => $course_id, 'userid' => $edit_id, 
            'timemodified' => time(), 'modifierid' => $USER->id) ) 
            ){ //Attempt to add course/user pair into database
        print_error('cannotinsertrecord');
    }
    
    //Update information
    $DB->set_field('ucla_officehours', 'modifierid', $USER->id, 
            array('courseid' => $course_id, 'userid' => $edit_id));
    $DB->set_field('ucla_officehours', 'timemodified', time(), 
            array('courseid' => $course_id, 'userid' => $edit_id));
    
    $DB->set_field('ucla_officehours', 'officehours', $data->officehours, 
            array('courseid' => $course_id, 'userid' => $edit_id));
    $DB->set_field('ucla_officehours', 'officelocation', $data->office, 
            array('courseid' => $course_id, 'userid' => $edit_id));
    $DB->set_field('ucla_officehours', 'email', $data->email, 
            array('courseid' => $course_id, 'userid' => $edit_id));
    $DB->set_field('ucla_officehours', 'phone', $data->phone, 
            array('courseid' => $course_id, 'userid' => $edit_id));
    
    $userchange = $DB->get_record('user', array('id' => $edit_id), '*', MUST_EXIST);
    $userchange->url = $data->website;
    user_update_user($userchange);
    
    $url = new moodle_url($CFG->wwwroot.'/course/view.php', array('id'=>$course_id));
    redirect($url);
}

echo $OUTPUT->header();
$updateform->display();
echo $OUTPUT->footer();

//EOF
