<?php
/*
 * Responds to copyright alert form. Handles setting of user preferences and
 * redirecting.
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_copyright_status/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_copyright_status/alert_form.php');

$id = required_param('id', PARAM_INT);   // course
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

require_course_login($course);

if (!has_capability('moodle/course:manageactivities', $coursecontext)) {
    print_error('permission_not_allow', 'block_ucla_copyright_status');
}

$alert_form = new copyright_alert_form();
$data = $alert_form->get_data();

if (!empty($data) && confirm_sesskey()) {    
    if (isset($data->yesbutton)) {
        // yes: redirect user to manage copyright status with editing turned on
        $params = array('courseid' => $id);

        // if user is not currently in editing mode, turn it on
        if (!$USER->editing) {
            $params['edit'] = 1;
            $params['sesskey'] = sesskey();
        }

        redirect(new moodle_url('/blocks/ucla_copyright_status/view.php', $params));
    } else if (isset($data->nobutton)) {
        // no: set user preference ucla_copyright_status_noprompt_<courseid> to 0
        set_user_preference('ucla_copyright_status_noprompt_' . $id, 0);
        $success_msg = get_string('alert_no_redirect', 'block_ucla_copyright_status');
    } else if (isset($data->laterbutton)) {
        // later: set user preference value ucla_copyright_status_noprompt_<courseid> to
        // now + 24 hours
        set_user_preference('ucla_copyright_status_noprompt_' . $id, time() + 86400);
        $success_msg = get_string('alert_later_redirect', 'block_ucla_copyright_status');
    }

    // redirect no/later responses to course page (make sure to redirect to
    // landing page or user wouldn't get success message)
    $section = 0;    
    $format_options = course_get_format($course->id)->get_format_options();
    if (isset($format_options['landing_page'])) {
        $landing_page = $format_options['landing_page'];
    }
    if (!empty($landing_page)) {
        $section = $landing_page;
    } 
    flash_redirect(new moodle_url('/course/view.php', 
            array('id' => $id, 'section' => $section)), $success_msg);    
}
