<?php
// This file is not a part of Moodle - http://moodle.org/
// This is a none core contributed module.
//
// This is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// The GNU General Public License
// can be see at <http://www.gnu.org/licenses/>.

/**
 * Sending invitation page script.
 *
 * @package    enrol
 * @subpackage invitation
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/invitation_forms.php');
require_once($CFG->dirroot . '/enrol/locallib.php');
require_login();

$courseid = required_param('courseid', PARAM_INT);
$courseurl = new moodle_url('/course/view.php', array('id' => $courseid));

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$fullname = $course->fullname;
$context = get_context_instance(CONTEXT_COURSE, $courseid);

if (!has_capability('enrol/invitation:enrol', $context)) {
    throw new moodle_exception('nopermissiontosendinvitation' , 'enrol_invitation', $courseurl);
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/enrol/invitation/invitation.php', 
        array('courseid' => $courseid)));
$PAGE->set_pagelayout('course');
$PAGE->set_course($course);
$PAGE->set_heading(get_string('inviteusers', 'enrol_invitation'));
$PAGE->set_title(get_string('inviteusers', 'enrol_invitation'));
$PAGE->navbar->add(get_string('inviteusers', 'enrol_invitation'));

$invitationmanager = new invitation_manager($courseid);
$instance = $invitationmanager->get_invitation_instance($courseid, true);
$mform = new invitations_form(null, array('courseid' => $courseid));
$mform->set_data($invitationmanager);
$data = $mform->get_data();
$confirmation = '';
if ($data and confirm_sesskey()) {
    $data->fullname = $fullname;
    $invitationmanager->send_invitations($data);

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('inviteusers', 'enrol_invitation'), 3, 'main');
    $confirmation = $OUTPUT->notification(get_string('emailssent', 'enrol_invitation'),
            'notifysuccess');
    echo $confirmation;
    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', array('id' => $courseid)));
    echo $OUTPUT->footer();
    exit();
}

//OUTPUT form
echo $OUTPUT->header();

// OUTPUT page tabs
print_page_tabs('invite');

echo $OUTPUT->heading(get_string('inviteusers', 'enrol_invitation'));
$mform->display();

echo $OUTPUT->footer();