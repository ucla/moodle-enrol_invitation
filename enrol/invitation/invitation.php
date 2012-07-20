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

$inviteid = optional_param('inviteid', 0, PARAM_INT);

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

echo $OUTPUT->header();
print_page_tabs('invite');  // OUTPUT page tabs
echo $OUTPUT->heading(get_string('inviteusers', 'enrol_invitation'));

$invitationmanager = new invitation_manager($courseid);

// make sure that site has invitation plugin installed
$instance = $invitationmanager->get_invitation_instance($courseid, true);

// If the user was sent to this page by selecting 'resend invite', then
// prefill the form with the data used to resend the invite
$prefilled = array();
if ($inviteid) {
    if ( $invite = $DB->get_record('enrol_invitation', array('courseid' => $courseid, 'id' => $inviteid)) ) {
        $prefilled['roleid'] = $invite->roleid;
        $prefilled['email'] = $invite->email;
        $prefilled['subject'] = $invite->subject;
        $prefilled['message'] = $invite->message;
        $prefilled['show_from_email'] = $invite->show_from_email;
        $prefilled['notify_inviter'] = $invite->notify_inviter;
    } else {
        print_error('invalidinviteid');
    }
}

$mform = new invitations_form(null, array('course' => $course, 'prefilled' => $prefilled));
$mform->set_data($invitationmanager);

$data = $mform->get_data();
if ($data and confirm_sesskey()) {
    
    // BEGIN UCLA MOD: CCLE-2955-Invite-multiple-users
    // Check for the invitation of multiple users
    $delimiters = "/[;, \r\n]/";
    $email_list = invitations_form::parse_dsv_emails($data->email, $delimiters);
    
    foreach ($email_list as $email) {
        $data->email = $email;
        $invitationmanager->send_invitations($data);
    }
    // END UCLA MOD: CCLE-2955
    
    $courseurl = new moodle_url('/course/view.php', array('id' => $courseid));
    $courseret = new single_button($courseurl, get_string('returntocourse',
                            'enrol_invitation'), 'get');

    $secturl = new moodle_url('/enrol/invitation/invitation.php',
                    array('courseid' => $courseid));
    $sectret = new single_button($secturl, get_string('returntoinvite',
                            'enrol_invitation'), 'get');    
    
    echo $OUTPUT->confirm(get_string('invitationsuccess', 'enrol_invitation'),
            $sectret, $courseret);    
    
} else {
    $mform->display();
}

echo $OUTPUT->footer();