<?php
/*
 * Responds to syllabus alert form. Handles setting of user preferences and
 * redirecting.
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/alert_form.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/course/format/ucla/ucla_course_prefs.class.php');

$id = required_param('id', PARAM_INT);   // course
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$ucla_syllabus_manager = new ucla_syllabus_manager($course);

require_course_login($course);

if (!$ucla_syllabus_manager->can_manage()) {
    print_error('err_cannot_manage', 'local_ucla_syllabus');
}

$success_msg = null;
$alert_form = new alert_form();
$data = $alert_form->get_data();
if (!empty($data) && confirm_sesskey()) {

    if (isset($data->yesbutton)) {
        // yes: redirect user to syllabus index with editing turned on
        $params = array('id' => $id);

        // if user is not currently in editing mode, turn it on
        if (!$USER->editing) {
            $params['edit'] = 1;
            $params['sesskey'] = sesskey();
        }

        // Handling manually uploaded syllabus?
        if (!empty($data->manualsyllabus)) {
            $params['manualsyllabus'] = $data->manualsyllabus;
        }

        redirect(new moodle_url('/local/ucla_syllabus/index.php', $params));
    } else if (isset($data->nobutton)) {
        // Handling manually uploaded syllabus?
        if (isset($data->manualsyllabus)) {
            // no: set user preference ucla_syllabus_noprompt_manual_<cmid> to 0
            set_user_preference('ucla_syllabus_noprompt_manual_' .
                    $data->manualsyllabus, 0);
        } else {
            // no: set user preference ucla_syllabus_noprompt_<courseid> to 0
            set_user_preference('ucla_syllabus_noprompt_' . $id, 0);
            $success_msg = get_string('alert_no_redirect', 'local_ucla_syllabus');
        }
    } else if (isset($data->laterbutton)) {
        // later: set user preference value ucla_syllabus_noprompt_<courseid> to
        // now + 24 hours
        set_user_preference('ucla_syllabus_noprompt_' . $id, time() + 86400);
        $success_msg = get_string('alert_later_redirect', 'local_ucla_syllabus');
    }

    // redirect no/later responses to course page (make sure to redirect to
    // landing page or user wouldn't get success message)
    $section = 0;    
    $ucla_course_prefs = new ucla_course_prefs($course->id);
    $landing_page = $ucla_course_prefs->get_preference('landing_page');
    if (!empty($landing_page)) {
        $section = $landing_page;
    }
    flash_redirect(new moodle_url('/course/view.php', 
            array('id' => $id, 'section' => $section)), $success_msg);
}
