<?php
require_once(dirname(__FILE__) . '/../../config.php');
global $CFG, $PAGE, $USER, $DB;

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot .
        '/blocks/ucla_office_hours/block_ucla_office_hours.php');
require_once($CFG->dirroot .
        '/blocks/ucla_office_hours/officehours_form.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/user/lib.php');

$course_id = required_param('course_id', PARAM_INT);
$edit_id = required_param('edit_id', PARAM_INT);

if ($course_id == SITEID) {
    print_error('cannoteditsiteform');
}
$course = $DB->get_record('course', array('id' => $course_id), '*', MUST_EXIST);
require_login($course, true);

$edit_user = $DB->get_record('user', array('id' => $edit_id), '*', MUST_EXIST);
$edit_user_name = $edit_user->firstname . ' ' . $edit_user->lastname;

$context = get_context_instance(CONTEXT_COURSE, $course_id);
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_url('/blocks/ucla_office_hours/officehours.php',
        array('course_id' => $course_id, 'edit_id' => $edit_id));

$page_title = get_string('header', 'block_ucla_office_hours', $edit_user_name);
$PAGE->set_title($page_title);
$PAGE->set_heading($page_title);

set_editing_mode_button();

$PAGE->navbar->add($page_title);

// make sure that entry can be edited
if (!block_ucla_office_hours::allow_editing($context, $edit_user->id)) {
    print_error('cannotedit', 'block_ucla_office_hours');    
}

echo $OUTPUT->header();
echo $OUTPUT->heading($page_title, 2, 'headingblock');

// get office hours entry, if any
$officehours_entry = $DB->get_record('ucla_officehours',
        array('courseid' => $course_id, 'userid' => $edit_id));

$updateform = new officehours_form(NULL, 
        array('course_id' => $course_id, 
              'edit_id' => $edit_id, 
              'edit_email' => $edit_user->email,
              'defaults' => $officehours_entry, 
              'url' => $edit_user->url),
        'post',
        '',
        array('class' => 'officehours_form'));

if ($updateform->is_cancelled()) { //If the cancel button is clicked, return to 'Site Info' page
    $url = new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $course_id, 'topic' => 0));
    redirect($url);
} else if ($data = $updateform->get_data()) { //Otherwise, process data
    
    // prepare new entry data
    $new_officehours_entry = new stdClass();
    $new_officehours_entry->userid          = $edit_id;
    $new_officehours_entry->courseid        = $course_id;
    $new_officehours_entry->modifierid      = $USER->id;
    $new_officehours_entry->timemodified    = time();
    $new_officehours_entry->officehours     = strip_tags(trim($data->officehours));
    $new_officehours_entry->officelocation  = $data->office;
    $new_officehours_entry->email           = $data->email;
    $new_officehours_entry->phone           = $data->phone;

    try {
        if (empty($officehours_entry)) {
            // need to insert new record
            $DB->insert_record('ucla_officehours', $new_officehours_entry);   
        } else {
            // use existing record id to update
            $new_officehours_entry->id = $officehours_entry->id;        
            $DB->update_record('ucla_officehours', $new_officehours_entry);        
        }
    } catch (dml_exception $e) {    
        print_error('cannotinsertrecord');        
    }   
    
    // check if editing user's profile needs to change
    if ($data->website != $edit_user->url) {
        $edit_user->url = $data->website;
        user_update_user($edit_user);
    }

    // display success message
    echo $OUTPUT->box_start('noticebox');
    echo html_writer::tag('h1', get_string('success', 'block_ucla_office_hours'));
    echo html_writer::tag('p', get_string('confirmation_message', 'block_ucla_office_hours'));
    echo $OUTPUT->continue_button(new moodle_url($CFG->wwwroot . '/course/view.php',
                    array('id' => $course_id, 'topic' => 0)));
    echo $OUTPUT->box_end();    

} else {
    $updateform->display();
}

echo $OUTPUT->footer();
