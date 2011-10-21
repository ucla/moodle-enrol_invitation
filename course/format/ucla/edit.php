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
 * Edit course settings
 *
 * @package    moodlecore
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/../../lib.php');
require_once(dirname(__FILE__) . '/ucla_course_prefs.class.php');
require_once(dirname(__FILE__) . '/course_prefs_edit_form.class.php');

$courseid = required_param('courseid', 0, PARAM_INT);

if ($courseid == SITEID) {
    print_error('cannoteditsiteform');
}

// Get the course information
if (!isset($course)) {
    $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
}

require_login($course);

$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

require_capability('moodle/course:update', $coursecontext);

$course_prefs = new ucla_course_prefs($courseid);

// Set prepare page display
$PAGE->set_pagelayout('course');
$PAGE->set_url('/course/format/ucla/edit.php', array('courseid' => $courseid));
//$PAGE->navbar->add(get_string('course_pref', 'format_ucla'));

// Get the moodle form
$editform = new course_prefs_edit_form(null, array(
    'course' => $course, 'currprefs' => $course_prefs
));

// This is the view.php parameters
$redir_params = array('id' => $courseid);

$redirect = true;
if ($editform->is_cancelled()) {
    // Cancelled...
} else if ($data = $editform->get_data()) {
    $course_prefs = new ucla_course_prefs($courseid);

    foreach ($data as $key => $val) {
        if ($key == 'submitbutton' || $key == 'courseid') {
            continue;
        }

        $course_prefs->set_preference($key, $val);
    }

    $redir_params['topic'] = $data->landing_page;

    $course_prefs->commit();
} else {
    $title = get_string('course_pref_for', 'format_ucla', $course->fullname);

    $PAGE->set_title($title);
    $PAGE->set_heading($title);

    echo $OUTPUT->header();
    echo $OUTPUT->heading($title);

    $editform->display();

    echo $OUTPUT->footer();

    $redirect = false;
}

if ($redirect) {
    redirect(new moodle_url($CFG->wwwroot . '/course/view.php', $redir_params));
}
