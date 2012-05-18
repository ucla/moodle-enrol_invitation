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
// course must have invitation plugin installed
$invite_instance = $invitationmanager->get_invitation_instance($courseid, true);

// get invites and display them
$invites = $invitationmanager->get_invites();

print_object($invites);

if (empty($invites)) {
    echo html_writer::tag('div', 
            get_string('noinvitehistory', 'enrol_invitation'), 
            array('class' => 'noinvitehistory'));
} else {
    $table = new flexible_table('invitehistory');
    $table->define_columns(array('invitee', 'role', 'status', 'date_sent', 'date_expiration', 'actions'));
    $table->define_headers(array('Invitee', 'Role', 'Status', 'Date sent', 'Expiration Date', 'Actions'));
    $table->define_baseurl($PAGE->url);

    $table->setup();
    
    foreach ($invites as $invite) {
        $table->add_data($invite);        
    }
    
    $table->finish_output();    
}

echo $OUTPUT->footer();