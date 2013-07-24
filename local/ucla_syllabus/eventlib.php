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
 * Event handlers for non-webservices events.
 *
 * @package    local_ucla_syllabus
 * @copyright  2013 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/alert_form.php');

/**
 * Delete a course's syllabus when a course is deleted.
 *
 * NOTE: Unfortunately cannot use ucla_syllabus_manager to delete syllabus
 * entry and files, because course context is already deleted. Need to manually
 * find the syllabus entries and delete associated files.
 *
 * @param object $course
 */
function delete_syllabi($course) {
    global $DB;

    // Get all syllabus entries for course.
    $syllabi = $DB->get_records('ucla_syllabus',
            array('courseid' => $course->id));

    if (empty($syllabi)) {
        return true;
    }

    $fs = get_file_storage();
    foreach ($syllabi as $syllabus) {
        // Delete any files associated with syllabus entry.
        $files = $fs->get_area_files($course->context->id,
                'local_ucla_syllabus', 'syllabus', $syllabus->id, '', false);
        if (!empty($files)) {
            foreach ($files as $file) {
                $file->delete();
            }
        }

        // Next, delete entry in syllabus table.
        $DB->delete_records('ucla_syllabus', array('id' => $syllabus->id));

        // This is the data needed to handle events.
        $data = new stdClass();
        $data->courseid = $course->id;
        $data->access_type = $syllabus->access_type;

        // Trigger any necessary events.
        events_trigger('ucla_syllabus_deleted', $data);
    }
}

/**
 * Alert instructors to upload syllabus if they haven't done so already.
 * 
 * @param object $eventdata Contains userid, course, user_is_editing, roles,
 *                          and term
 *                          
 */
function ucla_syllabus_handle_ucla_format_notices($eventdata) {
    global $CFG, $DB, $OUTPUT;

    // Ignore any old terms or if term is not set (meaning it is a collab site).
    if (!isset($eventdata->term) ||
            term_cmp_fn($eventdata->term, $CFG->currentterm) == -1) {
        // It is important for event handlers to return true, because false
        // indicates error and event will be reprocessed on the next cron run.
        return true;
    }

    // See if current user can manage syllabi for course.
    $course = new stdClass();
    $syllabusmanager = new ucla_syllabus_manager($eventdata->course);

    // Ignore alert if user cannot upload syllabi or if course has one uploaded.
    if (!$syllabusmanager->can_manage() ||
            $syllabusmanager->has_syllabus()) {
        return true;
    }

    $alertform = null;

    if (empty($alertform)) {
        // User can add syllabus, but course doesn't have syllabus, give alert.

        // But first, see if they turned off the syllabus alert for their
        // account ucla_syllabus_noprompt_<courseid>.
        $timestamp = get_user_preferences('ucla_syllabus_noprompt_' .
                $eventdata->course->id, null, $eventdata->userid);

        // Do not display alert if user turned off syllabus alerts or if remind
        // me time has not passed.
        if (!is_null($timestamp) && (intval($timestamp) === 0 ||
                $timestamp > time())) {
            return true;
        }

        // Now we can display the alert.
        $alertform = new alert_form(new moodle_url('/local/ucla_syllabus/alert.php',
                array('id' => $eventdata->course->id)), null, 'post', '',
                array('class' => 'ucla-syllabus-alert-form'));
    }

    // Unfortunately, the display function outputs HTML, rather than returning
    // it, so we need to capture it.
    ob_start();
    $alertform->display();
    $eventdata->notices[] = ob_get_clean();

    return true;
}
