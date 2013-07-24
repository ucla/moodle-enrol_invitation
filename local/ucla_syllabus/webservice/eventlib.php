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
 * File for handling all syllabus events.
 * 
 * Contains functions for:
 *      - adding/updating a syllabus,
 *      - deleting a syllabus, and
 *      - responding to course alerts to syllabus.
 * 
 * @package     local_ucla_syllabus
 * @subpackage  webservice
 * @copyright   2012 UC Regents
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// Handling the following events.
require_once($CFG->dirroot . '/local/ucla_syllabus/webservice/lib.php');
require_once($CFG->dirroot . '/local/ucla_syllabus/locallib.php');

/**
 * Handle syllabus add/update.
 * 
 * @param   mixed $data syllabus id
 * @return  bool true if operation is successful
 */
function ucla_syllabus_updated($data) {

    $instanceid = is_object($data) ? $data->id : $data;

    if ($syllabus = ucla_syllabus_manager::instance($instanceid)) {
        global $DB;

        /* Outgoing syllabus logic:
         *
         *  If public syllabus added/updated and no private syllabus exist,
         *      send public.
         *  If public syllabus added/updated and private syllabus exist,
         *      do not send public (do not send anything).
         *  If private syllabus added/updated and public syllabus exists/does not exist,
         *      send private.
         */

        $hostcourse = $DB->get_record('course', array('id' => $syllabus->courseid));

        // Given that events can be held up in the queue, the course associated
        // with the sillabus might have been deleted by the time it's our turn...
        if (empty($hostcourse)) {
            // Don't send anything.. dequeue.
            return true;
        }

        if ($syllabus instanceof ucla_private_syllabus) {
            // Private syllabus added, we'll send it.
            $outgoing = $syllabus;
        } else {
            // We got a public syllabus.

            // Get all the syllabi.
            $manager = new ucla_syllabus_manager($hostcourse);
            $syllabi = $manager->get_syllabi();

            // Check if private syllabus exists.
            foreach ($syllabi as $si) {
                if ($si instanceof ucla_private_syllabus) {
                    // If it does, send nothing.
                    return true;
                }
            }

            // Public syllabus added, and private syllabus does not exist.
            $outgoing = $syllabus;
        }

        // Check that file still exists, this may happen when user deletes
        // syllabus before cron runs.
        if (empty($outgoing->stored_file)) {
            return true;
        }

        $courses = ucla_get_course_info($hostcourse->id);

        $result = true;

        foreach ($courses as $course) {
            // Prepare criteria and payload.
            list($criteria, $payload) = syllabus_ws_manager::setup_transfer($outgoing, $course);

            // Handle event.
            $result &= syllabus_ws_manager::handle(syllabus_ws_manager::ACTION_TRANSFER, $criteria, $payload);
        }

        // Handler requires a bool result.
        return (bool)$result;
    }

    return true;
}

/**
 * Handle deletion of syllabus.
 * 
 * @param   mixed $data
 * @return  bool, true if operation successful
 */
function ucla_syllabus_deleted($data) {
    global $DB;

    $hostcourse = $DB->get_record('course', array('id' => $data->courseid));

    // The course may not exist if the event is delayed...
    if (empty($hostcourse)) {
        // Don't send anything and dequeue.
        return true;
    }

    $courses = ucla_get_course_info($hostcourse->id);

    // Get all the syllabi.
    $manager = new ucla_syllabus_manager($hostcourse);
    $syllabi = $manager->get_syllabi();

    foreach ($courses as $course) {
        switch(intval($data->access_type)) {
            case UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE:

                // Case where syllabus is private:
                //     If no public syllabus exists, POST delete.
                //     If public syllabus exists, POST public syllabus.
                if (empty($syllabi[UCLA_SYLLABUS_TYPE_PUBLIC])) {
                    list($criteria, $payload) = syllabus_ws_manager::setup_delete($course);
                    syllabus_ws_manager::handle(syllabus_ws_manager::ACTION_TRANSFER, $criteria, $payload);
                } else {
                    $publicsyllabus = array_shift($syllabi);

                    // Pass it on to another handler...
                    ucla_syllabus_updated($publicsyllabus->id);

                    // We want to break out of the loop as well...
                    // The ucla_syllabus_updated() function already checks if course is crosslisted.
                    break 2;
                }

                break;
            case UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC:
            case UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN:

                // Case where syllabus is public:
                //     If no private syllabus exists, POST delete.
                //     Else do nothing.
                if (empty($syllabi[UCLA_SYLLABUS_TYPE_PRIVATE])) {
                    list($criteria, $payload) = syllabus_ws_manager::setup_delete($course);
                    syllabus_ws_manager::handle(syllabus_ws_manager::ACTION_TRANSFER, $criteria, $payload);
                }
                // Else do nothing.
                break;
        }
    }
    return true;
}

/**
 * Event handler for course alert.
 * 
 * This handles crosslisted courses by sending multiple alerts 
 * in those cases.
 * 
 * @param   mixed $data course object
 * @return  bool true if operation is successful for all courses
 */
function ucla_course_alert($data) {

    if (!is_collab_site($data->id)) {

        // If a course is crosslisted, we want to send multiple alerts.
        $courses = ucla_get_course_info($data->id);

        $result = true;
        // Do for all coures found.
        foreach ($courses as $course) {
            // Prepare criteria & payload.
            list($criteria, $payload) = syllabus_ws_manager::setup_alert($course);
            // Handle event.
            $result &= syllabus_ws_manager::handle(syllabus_ws_manager::ACTION_ALERT, $criteria, $payload);
        }

        return (bool)$result;
    }
}
