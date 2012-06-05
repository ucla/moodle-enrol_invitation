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
 * Viewing invitation history script.
 *
 * @package    enrol
 * @subpackage invitation
 * @copyright  2012 Rex Lorenzo <rex@oid.ucla.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/invitation_forms.php');

require_once($CFG->dirroot . '/enrol/locallib.php');
require_once($CFG->libdir . '/tablelib.php');

// for distance_of_time_in_words
require_once($CFG->dirroot . '/local/ucla/datetimehelpers.php');

require_login();
$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

$context = get_context_instance(CONTEXT_COURSE, $courseid);
if (!has_capability('enrol/invitation:enrol', $context)) {
    $courseurl = new moodle_url('/course/view.php', array('id' => $courseid));    
    throw new moodle_exception('nopermissiontosendinvitation' , 
            'enrol_invitation', $courseurl);
}

// set up page
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/enrol/invitation/history.php', 
        array('courseid' => $courseid)));
$PAGE->set_pagelayout('course');
$PAGE->set_course($course);
$PAGE->set_heading(get_string('invitehistory', 'enrol_invitation'));
$PAGE->set_title(get_string('invitehistory', 'enrol_invitation'));
$PAGE->navbar->add(get_string('invitehistory', 'enrol_invitation'));

//OUTPUT form
echo $OUTPUT->header();

// OUTPUT page tabs
print_page_tabs('history');

echo $OUTPUT->heading(get_string('invitehistory', 'enrol_invitation'));

$invitationmanager = new invitation_manager($courseid);
// course must have invitation plugin installed (will give error if not found)
$invite_instance = $invitationmanager->get_invitation_instance($courseid, true);

// get invites and display them
$invites = $invitationmanager->get_invites();

if (empty($invites)) {
    echo html_writer::tag('div', 
            get_string('noinvitehistory', 'enrol_invitation'), 
            array('class' => 'noinvitehistory'));
} else {
    // columns to display
    $columns = array(
            'invitee'           => get_string('historyinvitee', 'enrol_invitation'),
            'role'              => get_string('historyrole', 'enrol_invitation'),
            'status'            => get_string('historystatus', 'enrol_invitation'),
            'datesent'          => get_string('historydatesent', 'enrol_invitation'),
            'dateexpiration'    => get_string('historydateexpiration', 'enrol_invitation'),
//            'actions'           => get_string('historyactions', 'enrol_invitation')
    );
    
    $table = new flexible_table('invitehistory');
    $table->define_columns(array_keys($columns));
    $table->define_headers(array_values($columns));
    $table->define_baseurl($PAGE->url);
    $table->set_attribute('class', 'generaltable generalbox');

    $table->setup();
    
    $role_cache = array();
    foreach ($invites as $invite) {
        /* build display row
         * [0] - invitee
         * [1] - role
         * [2] - status
         * [3] - dates sent
         * [4] - expiration date
         * [5] - actions (@todo)
         */
        
        // display invitee
        $row[0] = $invite->email;
        
        // figure out invited role
        if (empty($role_cache[$invite->roleid])) {
            $role = $DB->get_record('role', array('id' => $invite->roleid));
            if (empty($role)) {
                // cannot find role, give error
                $role_cache[$invite->roleid] = 
                        get_string('historyundefinedrole', 'enrol_invitation');
            } else {
                $role_cache[$invite->roleid] = $role->name;                
            }
        }
        $row[1] = $role_cache[$invite->roleid];
        
        // what is the status of the invite?
        $status = $invitationmanager->get_invite_status($invite);
        $row[2] = $status;
        
        // if status was used, figure out who used the invite
        $result = $invitationmanager->who_used_invite($invite);
        if (!empty($result)) {
            $row[2] .= get_string('used_by', 'enrol_invitation', $result);                
        }
        
        // when was the invite sent?
        $row[3] = date('M j, Y g:ia', $invite->timesent);
        
        // when does the invite expire?
        $row[4] = date('M j, Y g:ia', $invite->timeexpiration);
        
        // if status is active, then state how many days/minutes left
        if ($status == get_string('status_invite_active', 'enrol_invitation')) {
            $expires_text = sprintf('%s %s', 
                    get_string('historyexpires_in', 'enrol_invitation'),
                    distance_of_time_in_words(time(), $invite->timeexpiration, true));   
            $row[4] .= ' ' . html_writer::tag('span', '(' . $expires_text . ')', array('expires-text'));
        }
        
//        // are there any actions user can do?
//        $row[5] = '';
        
        $table->add_data($row); 
    }
    
    $table->finish_output();    
}

echo $OUTPUT->footer();