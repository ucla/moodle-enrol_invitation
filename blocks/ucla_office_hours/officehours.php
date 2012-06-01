<?php

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $PAGE, $USER, $DB;

require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.
    '/blocks/ucla_office_hours/block_ucla_office_hours.php');
require_once($CFG->dirroot.
        '/blocks/ucla_office_hours/officehours_form.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot.'/user/lib.php');

$course_id = optional_param('course_id', 0, PARAM_INT); 
$edit_id = optional_param('edit_id', 0, PARAM_INT); 

if(! $course_id) {
    $default_url = new moodle_url($CFG->wwwroot);
    redirect($default_url);
}


if ($course_id == SITEID){
    print_error('cannoteditsiteform');
}
$course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);

require_login($course, true);
$context = get_context_instance(CONTEXT_COURSE, $course_id);

$PAGE->set_url('/blocks/ucla_office_hours/officehours.php', 
    array('course_id' => $course_id, 'edit_id' => $edit_id));

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

echo $OUTPUT->header();
    
if(! $defaults = $DB->get_record('ucla_officehours', array('courseid' => $course_id, 'userid' => $edit_id),
                'officelocation, officehours, phone, email')) {
    $defaults = '';
}
$edit_user = $DB->get_record('user', array('id'=>$edit_id), '*', MUST_EXIST);
$edit_name = $edit_user->firstname . ' ' . $edit_user->lastname;
$e_url = $DB->get_record('user', array('id'=>$edit_id), 'url', MUST_EXIST)->url;

$updateform = new officehours_form(NULL, 
        array('course_id' => $course_id, 'edit_id' => $edit_id, 'defaults'=>$defaults, 'edit_name'=>$edit_name, 'url'=>$e_url));
if ($updateform->is_cancelled()) { //If the cancel button is clicked, return to 'Site Info' page
    $url = new moodle_url($CFG->wwwroot.'/course/view.php', array('id'=>$course_id, 'topic'=>0));
    redirect($url);
} else if($data = $updateform->get_data()) { //Otherwise, process data

    //If this course/user pair is not in the database, attempt to add it in
    if(! $DB->get_record('ucla_officehours', 
        array('courseid' => $course_id, 'userid' => $edit_id)) ) {

        if(! $DB->insert_record('ucla_officehours', 
            array('courseid' => $course_id, 'userid' => $edit_id, 
                'timemodified' => time(), 'modifierid' => $USER->id) ) 
                ){ //Attempt to add course/user pair into database
            print_error('cannotinsertrecord');
        }
    }
    //Update information

    $update_data = new StdClass();
    $entry = $DB->get_record('ucla_officehours', array('courseid'=>$course_id, 'userid'=>$edit_id), 'id');
    $update_data->id = $entry->id ;
    $update_data->userid = $edit_id;
    $update_data->courseid = $course_id;
    $update_data->modifierid = $USER->id;
    $update_data->timemodified = time();
    $update_data->officehours = $data->officehours;
    $update_data->officelocation = $data->office;
    $update_data->email = $data->email;
    $update_data->phone = $data->phone;

    $DB->update_record('ucla_officehours', $update_data);

    $userchange = $DB->get_record('user', array('id' => $edit_id), '*', MUST_EXIST);
    if($data->website != $userchange->url) {
        $userchange->url = $data->website;
        user_update_user($userchange);
    }

    //TODO: format the display properly
    $rurl = new moodle_url($CFG->wwwroot.'/course/view.php', 
            array('id'=>$course_id, 'topic'=>0));
    $confirmation = '';
    echo get_string('success', 'block_ucla_office_hours');
    echo get_string('confirmation_message', 'block_ucla_office_hours');
    echo get_string('confirmation_redirect1', 'block_ucla_office_hours');
    $confirmation .= html_writer::link($rurl, 'here');
    echo $confirmation;
    echo get_string('confirmation_redirect2', 'block_ucla_office_hours');
    
}else {
    $updateform->display();
}

//echo $OUTPUT->header();
//$updateform->display();
echo $OUTPUT->footer();

//EOF
