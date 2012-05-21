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

defined('MOODLE_INTERNAL') || die();

// invite status codes
define('ENROL_INVITE_INVALID', -1);
define('ENROL_INVITE_EXPIRED', 0);
define('ENROL_INVITE_USED', 1);
define('ENROL_INVITE_REVOKED', 2);
define('ENROL_INVITE_RESENT', 3);
define('ENROL_INVITE_ACTIVE', 4);

class invitation_manager {
    /*
     * The invitation enrol instance of a course
     */

    var $enrol_instance = null;

    /*
     * The course id
     */
    var $courseid = null;

    /**
     *
     * @param type $courseid 
     */
    public function __construct($courseid, $instancemustexist = true) {
        $this->courseid = $courseid;
        $this->enrol_instance = $this->get_invitation_instance($courseid, $instancemustexist);
    }

    /**
     * Return HTML invitation menu link for a given course 
     * It's mostly useful to add a link in a block menu - by default icon is displayed.
     * @param boolean $withicon - set to false to not display the icon
     * @return 
     */
    public function get_menu_link($withicon = true) {
        global $OUTPUT;

        $inviteicon = '';
        $link = '';

        if (has_capability('enrol/invitation:enrol', get_context_instance(CONTEXT_COURSE, $this->courseid))) {

            //display an icon with requested (css can be changed in stylesheet)
            if ($withicon) {
                $inviteicon = html_writer::empty_tag('img', array('alt' => "invitation", 'class' => "enrol_invitation_item_icon", 'title' => "invitation",
                            'src' => $OUTPUT->pix_url('invite', 'enrol_invitation')));
            }

            $link = html_writer::link(
                            new moodle_url('/enrol/invitation/invitation.php',
                                    array('courseid' => $this->courseid)), $inviteicon . get_string('inviteusers', 'enrol_invitation'));
        }

        return $link;
    }

    /**
     * Send invitation (create a unique token for each of them)
     * @global type $USER
     * @global type $DB
     * @param type $data 
     */
    public function send_invitations($data) {
        global $USER, $DB, $SITE;

        $data = (array) $data;

        if (has_capability('enrol/invitation:enrol', get_context_instance(CONTEXT_COURSE, $data['courseid']))) {
            $email = $data['email'];
            if (!empty($email)) {

                //create unique token for invitation
                do {
                    $token = md5(uniqid(rand(), 1));
                    $existingtoken = $DB->get_record('enrol_invitation', array('token' => $token));                        
                } while (!empty($existingtoken));

                //save token information in config (token value, course id, TODO: role id)
                $invitation = new stdClass();
                $invitation->courseid = $data['courseid'];
                $invitation->email = $email;
                $invitation->creatorid = $USER->id;
                $invitation->token = $token;
                $invitation->tokenused = false;
                $invitation->timesent = time();

                $DB->insert_record('enrol_invitation', $invitation);

                $enrolurl = new moodle_url('/enrol/invitation/enrol.php',
                                array('enrolinvitationtoken' => $token, 'id' => $data['courseid']));

                //send invitation to the user
                $contactuser = new object;
                $contactuser->email = $email;
                $contactuser->firstname = '';
                $contactuser->lastname = '';
                $contactuser->maildisplay = true;
                $emailinfo = new stdClass();
                $emailinfo->fullname = $data['fullname'];
                $emailinfo->managername = $USER->firstname . ' ' . $USER->lastname;
                $emailinfo->enrolurl = $enrolurl->out(false);
                $emailinfo->sitename = $SITE->fullname;
                $siteurl = new moodle_url('/');
                $emailinfo->siteurl = $siteurl->out(false);
                email_to_user($contactuser, $USER, get_string('emailtitleinvitation', 'enrol_invitation', $emailinfo), get_string('emailmessageinvitation', 'enrol_invitation', $emailinfo));
            }
        } else {
            throw new moodle_exception('cannotsendinvitation', 'enrol_invitation',
                    new moodle_url('/course/view.php', array('id' => $data['courseid'])));
        }
    }

    /**
     * Returns status of given invite. 
     * 
     * @param object $invite    Database record
     * 
     * @return int              Returns invite status code.
     */
    public function get_invite_status($invite) {
        if (!is_object($invite)) {
            return ENROL_INVITE_INVALID;
        }

        if ($invite->tokenused) {
            // invite was used already
            return ENROL_INVITE_USED;
        } else if ($invite->timeexpiration < time()) {
            // invite is expired
            return ENROL_INVITE_EXPIRED;
        } else {
            return ENROL_INVITE_ACTIVE;
        }
        // TO DO: add ENROL_INVITE_REVOKED and ENROL_INVITE_RESENT status
    }

    /**
     * Return all invites for given course.
     * 
     * @global type $DB
     * @param type $courseid
     * @return type 
     */
    public function get_invites($courseid = null) {
        global $DB;

        if (empty($courseid)) {
            $courseid = $this->courseid;
        }

        $invites = $DB->get_records('enrol_invitation', array('courseid' => $courseid));

        return $invites;
    }

    /**
     * Return the invitation instance for a specific course
     * Note: as using $PAGE variable, this function can only be called in a Moodle script page
     * @global object $PAGE
     * @param int $courseid
     * @param boolean $mustexist when set, an exception is thrown if no instance is found
     * @return type 
     */
    public function get_invitation_instance($courseid, $mustexist = false) {
        global $PAGE, $CFG, $DB;

        if (($courseid == $this->courseid) and !empty($this->enrol_instance)) {
            return $this->enrol_instance;
        }

        //find enrolment instance
        $instance = null;
        require_once("$CFG->dirroot/enrol/locallib.php");
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $manager = new course_enrolment_manager($PAGE, $course);
        foreach ($manager->get_enrolment_instances() as $tempinstance) {
            if ($tempinstance->enrol == 'invitation') {
                if ($instance === null) {
                    $instance = $tempinstance;
                }
            }
        }

        if ($mustexist and empty($instance)) {
            throw new moodle_exception('noinvitationinstanceset', 'enrol_invitation');
        }

        return $instance;
    }

    /**
     * Enrol the user following the invitation data
     * @param object $invitation 
     */
    public function enroluser() {
        global $USER, $DB, $PAGE, $CFG;

        $enrol = enrol_get_plugin('invitation');
        $enrol->enrol_user($this->enrol_instance, $USER->id, $this->enrol_instance->roleid);
    }

}



/**
 *
 * @param type $active_tab  Either 'invite' or 'history'
 */
function print_page_tabs($active_tab) {
    global $CFG, $COURSE;

    $tabs[] = new tabobject('history',
                    new moodle_url('/enrol/invitation/history.php',
                            array('courseid' => $COURSE->id)),
                    get_string('invitehistory', 'enrol_invitation'));
    $tabs[] = new tabobject('invite',
                    new moodle_url('/enrol/invitation/invitation.php',
                            array('courseid' => $COURSE->id)),
                    get_string('inviteusers', 'enrol_invitation'));

    // display tabs here
    print_tabs(array($tabs), $active_tab);
}
