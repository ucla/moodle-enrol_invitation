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
 * Alert instructors to upload syllabus if they haven't done so already.
 * 
 * @param object $eventdata Contains userid, course, user_is_editing, roles,
 *                          and term
 *                          
 */
function handle_ucla_format_notices($eventdata) {
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

    // user can upload syllabus, but course does not have syllabus, give alert

    // but first, see if they turned off the syllabus alert for their account
    // ucla_syllabus_noprompt_<courseid>
    $timestamp = get_user_preferences('ucla_syllabus_noprompt_' .
            $eventdata->course->id, null, $eventdata->userid);

    // do not display alert if user turned off syllabus alerts or if remind me
    // time has not passed
    if (!is_null($timestamp) && (intval($timestamp) === 0 ||
            $timestamp > time())) {
        return true;
    }

    // now we can display the alert
    $alert_form = new alert_form(new moodle_url('/local/ucla_syllabus/alert.php',
            array('id' => $eventdata->course->id)), null, 'post', '',
            array('class' => 'ucla-syllabus-alert-form'));

    // unfortunately, the display function outputs HTML, rather than returning
    // it, so we need to capture it
    ob_start();
    $alert_form->display();
    $eventdata->notices[] = ob_get_clean();

    return true;
}
