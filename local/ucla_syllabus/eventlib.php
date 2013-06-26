<?php
/**
 * Event handlers for non-webservices events.
 *
 * @package    local
 * @subpackage ucla_syllabus
 * @copyright  2013 UC Regents
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

    // get all syllabus entries for course
    $syllabi = $DB->get_records('ucla_syllabus',
            array('courseid' => $course->id));

    if (empty($syllabi)) {
        return true;
    }

    $fs = get_file_storage();
    foreach ($syllabi as $syllabus) {
        // delete any files associated with syllabus entry
        $files = $fs->get_area_files($course->context->id, 
                'local_ucla_syllabus', 'syllabus', $syllabus->id, '', false);
        if (!empty($files)) {
            foreach ($files as $file) {
                $file->delete();
            }            
        }

        // next, delete entry in syllabus table
        $DB->delete_records('ucla_syllabus', array('id' => $syllabus->id));

        // Data to handle events
        $data = new stdClass();
        $data->courseid = $course->id;
        $data->access_type = $syllabus->access_type;

        // trigger events
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

    // ignore any old terms or if term is not set (meaning it is a collab site)
    if (!isset($eventdata->term) ||
            term_cmp_fn($eventdata->term, $CFG->currentterm) == -1) {
        // important for event handlers to return true, because false indicates
        // error and event will be reprocessed on the next cron run
        return true;    
    }

    // see if current user can manage syllabi for course
    $course = new stdClass();
    $ucla_syllabus_manager = new ucla_syllabus_manager($eventdata->course);

    // ignore alert if user cannot upload syllabi or if course has one uploaded
    if (!$ucla_syllabus_manager->can_manage() || 
            $ucla_syllabus_manager->has_syllabus()) {
        return true;
    }

    $alert_form = null;

//    // User can add syllabus, but course does not have syllabus. Check to see
//    // if someone manually uploaded a syllabus.
//    $manuallysyllabi = $ucla_syllabus_manager->get_all_manual_syllabi();
//    if (!empty($manuallysyllabi)) {
//        // There might be multiple manually uploaded syllabus, and user might
//        // choose to ignore some of them.
//        foreach ($manuallysyllabi as $syllabus) {
//            $noprompt = get_user_preferences('ucla_syllabus_noprompt_manual_' .
//                    $syllabus->cmid, null, $eventdata->userid);
//            if (is_null($noprompt)) {
//                // Display form.
//                $alert_form = new alert_form(new moodle_url('/local/ucla_syllabus/alert.php',
//                        array('id' => $eventdata->course->id)),
//                        array('manualsyllabus' => $syllabus), 'post', '',
//                        array('class' => 'ucla-syllabus-alert-form'));
//                // Only want one alert to be shown.
//                break;
//            }
//        }
//    }

    if (empty($alert_form)) {
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
        $alert_form = new alert_form(new moodle_url('/local/ucla_syllabus/alert.php',
                array('id' => $eventdata->course->id)), null, 'post', '',
                array('class' => 'ucla-syllabus-alert-form'));
    }

    // Unfortunately, the display function outputs HTML, rather than returning
    // it, so we need to capture it.
    ob_start();
    $alert_form->display();
    $eventdata->notices[] = ob_get_clean();

    return true;
}
