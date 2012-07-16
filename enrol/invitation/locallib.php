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

class invitation_manager {
    /*
     * The invitation enrol instance of a course
     */
    var $enrol_instance = null;

    /*
     * The course id
     */
    var $courseid = null;
    
    // For revoking an active invite
    const INVITE_REVOKE = 1;
    
    // For extending the expiration time of an active invite
    const INVITE_EXTEND = 2;
    
    // For resending an expired or revoked invite
    const INVITE_RESEND = 3;

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
        global $DB, $CFG, $COURSE, $SITE, $USER;

        if (has_capability('enrol/invitation:enrol', get_context_instance(CONTEXT_COURSE, $data->courseid))) {

            // get course record, to be used later
            $course = $DB->get_record('course', array('id' => $data->courseid), '*', MUST_EXIST);            
            
            if (!empty($data->email)) {
                //create unique token for invitation
                do {
                    $token = uniqid();
                    $existingtoken = $DB->get_record('enrol_invitation', array('token' => $token));
                } while (!empty($existingtoken));

                //save token information in config (token value, course id, TODO: role id)
                $invitation = new stdClass();
                $invitation->token = $token;
                $invitation->email = $data->email;
                $invitation->roleid = $data->role_group['roleid'];
                $invitation->courseid = $data->courseid;
                $invitation->tokenused = false;
                
                // set time
                $timesent = time();
                $invitation->timesent = $timesent;
                $invitation->timeexpiration = $timesent + 
                        get_config('enrol_invitation', 'enrolperiod');

                $invitation->inviterid = $USER->id;
                $invitation->notify_inviter = empty($data->notify_inviter) ? 0 : 1;
                $invitation->show_from_email = empty($data->show_from_email) ? 0 : 1;
                
                $invitation->subject = $data->subject;
                
                // construct message: custom (if any) + template
                $message = '';
                if (!empty($data->message)) {
                    $message .= get_string('instructormsg', 'enrol_invitation', 
                            $data->message);
                    $invitation->message = $data->message;
                }
                
                $message_params = new stdClass();
                $message_params->fullname = 
                        sprintf('%s: %s', $course->shortname, $course->fullname);
                $message_params->expiration = date('M j, Y g:ia', $invitation->timeexpiration);
                $inviteurl =  new moodle_url('/enrol/invitation/enrol.php', 
                                array('token' => $token));
                $message_params->inviteurl = $inviteurl->out(false);
                $message_params->supportemail = $CFG->supportemail;
                $message .= get_string('emailmsgtxt', 'enrol_invitation', $message_params);

                $DB->insert_record('enrol_invitation', $invitation);

                // change FROM to be $CFG->supportemail if user has show_from_email off
                $fromuser = $USER;
                if (empty($invitation->show_from_email)) {
                    $fromuser = new stdClass();
                    $fromuser->email = $CFG->supportemail;
                    $fromuser->firstname = '';
                    $fromuser->lastname = $SITE->fullname;
                    $fromuser->maildisplay = true;
                }
                
                //send invitation to the user
                $contactuser = new stdClass();
                $contactuser->email = $invitation->email;
                $contactuser->firstname = '';
                $contactuser->lastname = '';
                $contactuser->maildisplay = true;
                email_to_user($contactuser, $fromuser, $invitation->subject, $message);
            }
        } else {
            throw new moodle_exception('cannotsendinvitation', 'enrol_invitation',
                    new moodle_url('/course/view.php', array('id' => $data['courseid'])));
        }
    }
    
    // BEGIN UCLA MOD: CCLE-2960-Viewing-history-of-invites-and-status
    /**
     * Resend an invite that was already sent by send_invitations()
     * 
     * @global $USER
     * @global $DB
     * @param $invite       Invite to be resent
     */
    public function resend_invite($invite) {
        global $DB, $CFG, $SITE, $USER;
        
        $course = $DB->get_record('course', array('id' => $invite->courseid));
        
        $invitation = new stdClass();
        $invitation->token = $invite->token;
        $invitation->email = $invite->email;
        $invitation->roleid = $invite->roleid;
        $invitation->courseid = $invite->courseid;
        $invitation->tokenused = false;
        
        // set time
        $timesent = time();
        $invitation->timesent = $timesent;
        $invitation->timeexpiration = $timesent + 
                get_config('enrol_invitation', 'enrolperiod');
        
        // update $invite to have the proper timesent/timeexpiration
        $invitationmanager = new invitation_manager($invite->courseid);
        $invitationmanager->update_invite($invite->courseid, $invite->id, 
                array('timeexpiration' => $timesent + get_config('enrol_invitation', 'enrolperiod')) );
        
        $invitation->inviterid = $USER->id;
        $invitation->notify_inviter = empty($invite->notify_inviter) ? 0 : 1;
        $invitation->show_from_email = empty($invite->show_from_email) ? 0 : 1;
        
        // Prepend subject heading with a 'Reminder' string
        $invitation->subject = get_string('reminder', 'enrol_invitation') . $invite->subject;
        
        // construct message: custom (if any) + template
        $message = '';
        if (!empty($invite->message)) {
            $message .= get_string('instructormsg', 'enrol_invitation', 
                    $invite->message);
            $invitation->message = $invite->message;
        }
        
        $message_params = new stdClass();
        $message_params->fullname = 
                sprintf('%s: %s', $course->shortname, $course->fullname);
        $message_params->expiration = date('M j, Y g:ia', $invitation->timeexpiration);
        $inviteurl =  new moodle_url('/enrol/invitation/enrol.php', 
                array('token' => $invite->token));
        $message_params->inviteurl = $inviteurl->out(false);
        $message_params->supportemail = $CFG->supportemail;
        $message .= get_string('emailmsgtxt', 'enrol_invitation', $message_params);
        
        // change FROM to be $CFG->supportemail if user has show_from_email off
        $fromuser = $USER;
        if (empty($invitation->show_from_email)) {
            $fromuser = new stdClass();
            $fromuser->email = $CFG->supportemail;
            $fromuser->firstname = '';
            $fromuser->lastname = $SITE->fullname;
            $fromuser->maildisplay = true;
        }
        
        //send invitation to the user
        $contactuser = new stdClass();
        $contactuser->email = $invitation->email;
        $contactuser->firstname = '';
        $contactuser->lastname = '';
        $contactuser->maildisplay = true;
        email_to_user($contactuser, $fromuser, $invitation->subject, $message);
    }
    // END UCLA MOD: CCLE-2960
    
    /**
     * Returns status of given invite. 
     * 
     * @param object $invite    Database record
     * 
     * @return string              Returns invite status string.
     */
    public function get_invite_status($invite) {
        if (!is_object($invite)) {
            return get_string('status_invite_invalid', 'enrol_invitation');
        }

        if ($invite->tokenused) {
            // invite was used already
            return get_string('status_invite_used', 'enrol_invitation');
        } else if ($invite->timeexpiration < time()) {
            // invite is expired
            return get_string('status_invite_expired', 'enrol_invitation');
        } else {
            return get_string('status_invite_active', 'enrol_invitation');
        }
        // TO DO: add status_invite_revoked and status_invite_resent status
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
    public function enroluser($invitation) {
        global $USER;

        $enrol = enrol_get_plugin('invitation');
        $enrol->enrol_user($this->enrol_instance, $USER->id, $invitation->roleid);
    }

    /**
     * Figures out who used an invite.
     * 
     * @param object $invite    Invitation record
     * 
     * @return object           Returns an object with following values:
     *                          ['username'] - name of who used invite
     *                          ['useremail'] - email of who used invite
     *                          ['roles'] - roles the user has for course that 
     *                                      they were invited
     *                          ['timeused'] - formatted string of time used
     *                          Returns false on error or if invite wasn't used.
     */
    public function who_used_invite($invite) {
        global $DB;
        $ret_val = new stdClass();
        
        if (empty($invite->userid) || empty($invite->tokenused) || 
                empty($invite->courseid) || empty($invite->timeused)) {
            //debugging('one of required fields empty');
            return false;
        }
        
        // find user
        $user = $DB->get_record('user', array('id' => $invite->userid));        
        if (empty($user)) {
            //debugging('could not find user');
            return false;
        }
        $ret_val->username = sprintf('%s %s', $user->firstname, $user->lastname);
        $ret_val->useremail = $user->email;
        
        // find their roles for course
        $ret_val->roles = get_user_roles_in_course($invite->userid, $invite->courseid);
        if (empty($ret_val->roles)) {
            // if no roles, then they must have been booted out later            
            //debugging('no roles found');
            return false;
        }
        $ret_val->roles = strip_tags($ret_val->roles);
        
        // format string when invite was used
        $ret_val->timeused = date('M j, Y g:ia', $invite->timeused);
        
        return $ret_val;
    }
    
    // BEGIN UCLA MOD: CCLE-2960-Viewing-history-of-invites-and-status
    /**
     * Updates the invitation denoted by $invite.
     * The old invite fields will be overwritten by the values in $field.
     * 
     * @param int    $courseid   id of the course to which the invite belongs to
     * @param int    $inviteid   id of invite to be updated
     * @param array  $fields     array of fields to be updated
     * 
     * @return bool              determine if the invite was sucessfully updated
     */
    public function update_invite($courseid, $inviteid, $fields) {
        global $DB;
        
        if ( $DB->get_record('enrol_invitation', array('courseid' => $courseid,  'id' => $inviteid)) ) {
            foreach ($fields as $field_key => $field_value) {
                $DB->set_field('enrol_invitation', $field_key, $field_value, 
                        array('courseid' => $courseid,  'id' => $inviteid));
            }
        } else {
            // invite does not exist in the db
            return false;
        }
        
        return true;
    }
    // END UCLA MOD: CCLE-2960
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
