<?php

require_once(dirname(__FILE__).'/../../config.php');
global $CFG, $PAGE;

require_once($CFG->libdir.'/blocklib.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.
    '/blocks/ucla_office_hours/block_ucla_office_hours.php');
require_once($CFG->dirroot.
        '/blocks/ucla_office_hours/update_officehours_form.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');

$course_id = optional_param('course_id', 0, PARAM_INT); 
$edit_id = optional_param('edit_id', 0, PARAM_INT); 
//print_object('ID: '); print_object($course_id);
//print_object('EDIT: '); print_object($edit_id);
if($course_id) {
    if ($course_id == SITEID){
        // don't allow editing of  'site course' using this form
        print_error('cannoteditsiteform');
    }
    //$user_edit_id = $DB->get_record('user', array('id'=>$edit_id), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);

    require_login($course, true);
    $context = get_context_instance(CONTEXT_COURSE, $course_id);

    $PAGE->set_url('/blocks/ucla_office_hours/officehours.php', 
        array('course_id' => $course_id, 'edit_id' => $edit_id));
    
    $PAGE->url->param('id',$course_id);
    $PAGE->url->param('edit',$edit_id);

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
}

$context = get_context_instance(CONTEXT_COURSE, 506); //Need to not hardcode '506'
$PAGE->set_context($context);

$updateform = new officehours_form(NULL, array('course_id' => $course_id, 'edit_id' => $edit_id));
if ($updateform->is_cancelled()) { //DOESNT WORK
    $url = new moodle_url($CFG->wwwroot.'/course/view.php', array('id'=>506));
    redirect($url);
} else if($data = $updateform->get_data()) {
    print_object($data);
    
    $url = new moodle_url($CFG->wwwroot.'/course/view.php', array('id'=>506));
    //redirect($url);
}

echo $OUTPUT->header();
$updateform->display();
echo $OUTPUT->footer();

//EOF
