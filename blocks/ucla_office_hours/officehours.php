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

$context = get_context_instance(CONTEXT_COURSE, $course_id);
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_url('/blocks/ucla_office_hours/officehours.php',
        array('course_id' => $course_id, 'edit_id' => $edit_id));

$page_title = get_string('header', 'block_ucla_office_hours');
$PAGE->set_title($page_title);
$PAGE->set_heading($page_title);

set_editing_mode_button();

$PAGE->navbar->add($page_title);

// make sure that entry can be edited
if (!allow_editing($context, $edit_user)) {
    print_error('cannotedit', 'block_ucla_office_hours');    
}

echo $OUTPUT->header();
echo $OUTPUT->heading($page_title, 2, 'headingblock');

// prepare form data

// get office hours entry, if any
$officehours_entry = $DB->get_record('ucla_officehours',
        array('courseid' => $course_id, 'userid' => $edit_id),
        'officelocation, officehours, phone, email');

// get user's name/url
$edit_user_name = $edit_user->firstname . ' ' . $edit_user->lastname;
$edit_user_url = $edit_user->url;

$updateform = new officehours_form(NULL, 
        array('course_id' => $course_id, 
              'edit_id' => $edit_id, 
              'defaults' => $officehours_entry, 
              'edit_name' => $edit_user_name, 
              'url' => $edit_user_url),
        'post',
        '',
        array('class' => 'officehours_form'));

if ($updateform->is_cancelled()) { //If the cancel button is clicked, return to 'Site Info' page
    $url = new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $course_id, 'topic' => 0));
    redirect($url);
} else if ($data = $updateform->get_data()) { //Otherwise, process data
    //If this course/user pair is not in the database, attempt to add it in
    if (!$DB->get_record('ucla_officehours',
                    array('courseid' => $course_id, 'userid' => $edit_id))) {

        if (!$DB->insert_record('ucla_officehours',
                        array('courseid' => $course_id, 'userid' => $edit_id,
                    'timemodified' => time(), 'modifierid' => $USER->id))
        ) { //Attempt to add course/user pair into database
            print_error('cannotinsertrecord');
        }
    }
    
    //Update information
    if (!empty($officehours_entry)) {
        // updating entry
        $update_data->id = $entry->id;
        
    }
    $update_data->userid = $edit_id;
    $update_data->courseid = $course_id;
    $update_data->modifierid = $USER->id;
    $update_data->timemodified = time();
    $update_data->officehours = $data->officehours;
    $update_data->officelocation = $data->office;
    $update_data->email = $data->email;
    $update_data->phone = $data->phone;

    $DB->update_record('ucla_officehours', $update_data);

    $userchange = $DB->get_record('user', array('id' => $edit_id), '*',
            MUST_EXIST);
    if ($data->website != $userchange->url) {
        $userchange->url = $data->website;
        user_update_user($userchange);
    }

    //TODO: format the display properly
    $rurl = new moodle_url($CFG->wwwroot . '/course/view.php',
                    array('id' => $course_id, 'topic' => 0));
    $confirmation = '';
    $confirmation .= html_writer::tag('h1',
                    get_string('success', 'block_ucla_office_hours'));
    $confirmation .= html_writer::tag('div',
                    get_string('confirmation_message', 'block_ucla_office_hours'));
    $confirmation .= html_writer::start_tag('div') . get_string('confirmation_redirect1',
                    'block_ucla_office_hours');
    $confirmation .= html_writer::link($rurl, 'here');
    $confirmation .= get_string('confirmation_redirect2',
            'block_ucla_office_hours');
    $confirmation .= html_writer::end_tag('div');
    echo $confirmation;
} else {
    $updateform->display();
}

echo $OUTPUT->footer();

/**
 * Makes sure that $edit_user is an instructing role for $course. Also makes 
 * sure that user initializing editing has the ability to edit office hours.
 * 
 * @param mixed $course_context Course context
 * @param mixed $edit_user      User we are editing
 * 
 * @return boolean
 */
function allow_editing($course_context, $edit_user) {
    global $CFG, $USER;
    
    // do capability check (but always let user edit their own entry)
    if (!has_capability('block/ucla_office_hours:editothers', $course_context) &&
            $edit_user->id != $USER->id) {
        debugging('failed capability check');
        return false;
    }
    
    /**
    * Course and edit_user must be in the same course and must be one of the 
    * roles defined in $CFG->instructor_levels_roles, which is currently:
    * 
    * $CFG->instructor_levels_roles = array(
    *   'Instructor' => array(
    *       'editinginstructor',
    *       'ta_instructor'
    *   ),
    *   'Teaching Assistant' => array(
    *       'ta',
    *       'ta_admin'
    *   )
    * );
    */    
    
    // format $CFG->instructor_levels_roles so it is easier to search
    $allowed_roles = array_merge($CFG->instructor_levels_roles['Instructor'],
            $CFG->instructor_levels_roles['Teaching Assistant']);
    
    // get user's roles
    $roles = get_user_roles($course_context, $edit_user->id);
    
    // now see if any of those roles match anything in 
    // $CFG->instructor_levels_roles
    foreach ($roles as $role) {
        if (in_array($role->shortname, $allowed_roles)) {
            return true;
        }        
    }
    
    debugging('role not in instructor_levels_roles');    
    return false;
}