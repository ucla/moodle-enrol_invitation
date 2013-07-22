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

/*
 * Responds to syllabus alert form. Handles setting of user preferences and
 * redirecting.
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/alert_form.php');
require_once($CFG->dirroot . '/local/ucla/lib.php');
require_once($CFG->dirroot . '/course/format/ucla/ucla_course_prefs.class.php');

// Use the ID of the course to retrieve course records.
$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$syllabusmanager = new ucla_syllabus_manager($course);

require_course_login($course);

if (!$syllabusmanager->can_manage()) {
    print_error('err_cannot_manage', 'local_ucla_syllabus');
}

$successmessage = null;
$alertform = new alert_form();
$data = $alertform->get_data();
if (!empty($data) && confirm_sesskey()) {

    if (isset($data->yesbutton)) {
        // Redirect user to syllabus index with editing turned on.
        $params = array('id' => $id);

        // If user is not currently in editing mode, turn it on.
        if (!$USER->editing) {
            $params['edit'] = 1;
            $params['sesskey'] = sesskey();
        }

        // Handling manually uploaded syllabus.
        if (!empty($data->manualsyllabus)) {
            $params['manualsyllabus'] = $data->manualsyllabus;
        }

        redirect(new moodle_url('/local/ucla_syllabus/index.php', $params));
    } else if (isset($data->nobutton)) {
        // Handling manually uploaded syllabus?
        if (isset($data->manualsyllabus)) {
            // Set user preference ucla_syllabus_noprompt_manual_<cmid> to 0.
            set_user_preference('ucla_syllabus_noprompt_manual_' .
                    $data->manualsyllabus, 0);
        } else {
            // Set user preference ucla_syllabus_noprompt_<courseid> to 0.
            set_user_preference('ucla_syllabus_noprompt_' . $id, 0);
            $successmessage = get_string('alert_no_redirect', 'local_ucla_syllabus');
        }
    } else if (isset($data->laterbutton)) {
        // Set user preference value ucla_syllabus_noprompt_<courseid> to
        // now + 24 hours.
        set_user_preference('ucla_syllabus_noprompt_' . $id, time() + 86400);
        $successmessage = get_string('alert_later_redirect', 'local_ucla_syllabus');
    }

    // Redirect no/later responses to course page (make sure to redirect to
    // landing page or user wouldn't get success message).
    $section = 0;
    $courseprefs = new ucla_course_prefs($course->id);
    $landingpage = $courseprefs->get_preference('landing_page');
    if (!empty($landingpage)) {
        $section = $landingpage;
    }
    flash_redirect(new moodle_url('/course/view.php',
            array('id' => $id, 'section' => $section)), $successmessage);
}
