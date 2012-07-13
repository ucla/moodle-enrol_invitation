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
 * This page try to enrol the user
 *
 * @package    enrol
 * @subpackage invitation
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');
require($CFG->dirroot . '/enrol/invitation/locallib.php');

require_login(null, false);

//check if param token exist
$enrolinvitationtoken = required_param('token', PARAM_ALPHANUM);

//retrieve the token info
$invitation = $DB->get_record('enrol_invitation', 
        array('token' => $enrolinvitationtoken, 'tokenused' => false));

//if token is valid, enrol the user into the course          
if (empty($invitation) or empty($invitation->courseid)) {
    throw new moodle_exception('expiredtoken', 'enrol_invitation');
}

// make sure that course exists
$course = $DB->get_record('course', array('id' => $invitation->courseid), '*', MUST_EXIST);
$context = get_context_instance(CONTEXT_COURSE, $course->id);

// set up page
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/enrol/invitation/enrol.php', 
        array('token' => $enrolinvitationtoken)));
$PAGE->set_pagelayout('course');
$PAGE->set_course($course);
$PAGE->set_heading(get_string('invitation_acceptance_title', 'enrol_invitation'));
$PAGE->set_title(get_string('invitation_acceptance_title', 'enrol_invitation'));
$PAGE->navbar->add(get_string('invitation_acceptance_title', 'enrol_invitation'));

//get
$invitationmanager = new invitation_manager($invitation->courseid);
$instance = $invitationmanager->get_invitation_instance($invitation->courseid);

//First multiple check related to the invitation plugin config
//@todo better handle exceptions here

if (isguestuser()) {
    // can not enrol guest!!
    echo $OUTPUT->header();
    echo $OUTPUT->box_start('generalbox');

    $notice_object = prepare_notice_object($invitation);        
    echo get_string('loggedinnot', 'enrol_invitation', $notice_object);
    $loginbutton = new single_button(new moodle_url($CFG->wwwroot 
            . '/login/index.php'), get_string('login'));

    echo $OUTPUT->render($loginbutton);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}

// allow invitation to work for users already enrolled in a course, because 
// invite might be for another role type
//if ($DB->record_exists('user_enrolments', 
//        array('userid' => $USER->id, 'enrolid' => $instance->id))) {
//    //TODO: maybe we should tell them they are already enrolled, but can not access the course
//    debugging('user_enrolments exists');
//    return null;
//}

// have invitee confirm their acceptance of the site invitation
$confirm = optional_param('confirm', 0, PARAM_BOOL);
if (empty($confirm)) {
    echo $OUTPUT->header();
    
    $accepturl = new moodle_url('/enrol/invitation/enrol.php', 
            array('token' => $invitation->token, 'confirm' => true));
    $accept = new single_button($accepturl, 
            get_string('invitationacceptancebutton', 'enrol_invitation'), 'get');
    $cancel = new moodle_url('/');

    $notice_object = prepare_notice_object($invitation);
    echo $OUTPUT->confirm(get_string('invitationacceptance', 'enrol_invitation', 
            $notice_object), $accept, $cancel);    
        
    echo $OUTPUT->footer();
    exit;    
} else {
    // user confirmed, so add them
    require_once($CFG->dirroot . '/enrol/invitation/locallib.php');
    $invitationmanager = new invitation_manager($invitation->courseid);
    $invitationmanager->enroluser($invitation);

    //Set token as used and mark which user was assigned the token
    $invitation->tokenused = true;
    $invitation->timeused = time();
    $invitation->userid = $USER->id;
    $DB->update_record('enrol_invitation', $invitation);

    if (!empty($invitation->notify_inviter)) {
        //send an email to the user who sent the invitation        
        $inviter = $DB->get_record('user', array('id' => $invitation->notify_inviter));

        $contactuser = new object;
        $contactuser->email = $inviter->email;
        $contactuser->firstname = $inviter->firstname;
        $contactuser->lastname = $inviter->lastname;
        $contactuser->maildisplay = true;

        $emailinfo = prepare_notice_object($invitation);
        $emailinfo->userfullname = trim($USER->firstname . ' ' . $USER->lastname);        
        $emailinfo->useremail = $USER->email;
        $courseenrolledusersurl = new moodle_url('/enrol/users.php', 
                array('id' => $invitation->courseid));
        $emailinfo->courseenrolledusersurl = $courseenrolledusersurl->out(false);
        $invitehistoryurl = new moodle_url('/enrol/invitation/history.php', 
                array('courseid' => $invitation->courseid));
        $emailinfo->invitehistoryurl = $invitehistoryurl->out(false);

        $course = $DB->get_record('course', array('id' => $invitation->courseid));
        $emailinfo->coursefullname = sprintf('%s: %s', $course->shortname, $course->fullname);
        $emailinfo->sitename = $SITE->fullname;
        $siteurl = new moodle_url('/');
        $emailinfo->siteurl = $siteurl->out(false);

        email_to_user($contactuser, get_admin(), 
                get_string('emailtitleuserenrolled', 'enrol_invitation', $emailinfo), 
                get_string('emailmessageuserenrolled', 'enrol_invitation', $emailinfo));
    }

    $courseurl = new moodle_url('/course/view.php', array('id' => $invitation->courseid));
    redirect($courseurl);    
}

/**
 * Setups the object used in the notice strings for when a user is accepting 
 * a site invitation.
 * 
 * @global moodle_database $DB
 * 
 * @param mixed $invitation
 * 
 * @return stdClass 
 */
function prepare_notice_object($invitation) {
    global $CFG, $course, $DB;
    
    $notice_object = new stdClass();
    $notice_object->email = $invitation->email;    
    $notice_object->coursefullname = $course->fullname;    
    $notice_object->supportemail = $CFG->supportemail;
    
    // get role name for use in acceptance message
    $role = $DB->get_record('role', array('id' => $invitation->roleid));
    $notice_object->rolename = $role->name;
    $notice_object->roledescription = strip_tags($role->description);
 
    return $notice_object;
}