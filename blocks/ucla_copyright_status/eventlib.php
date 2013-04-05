<?php
/**
 * Event handlers for non-webservices events.
 *
 * @package    block
 * @subpackage block_ucla_copyright_status
 * @copyright  2013 UC Regents
 */

require_once($CFG->dirroot . '/blocks/ucla_copyright_status/lib.php');
require_once($CFG->dirroot . '/blocks/ucla_copyright_status/alert_form.php');

/**
 * Alert instructors to assign copyright status for files used in the course they haven't done so already.
 * @param object $eventdata Contains userid, course, user_is_editing, roles, and term
 *                          
 */
function handle_ucla_copyright_status_notice($eventdata) {
    global $CFG, $DB, $OUTPUT;

    // ignore any old terms or if term is not set (meaning it is a collab site)
    if (!isset($eventdata->term) ||
            term_cmp_fn($eventdata->term, $CFG->currentterm) == -1) {
        // important for event handlers to return true, because false indicates
        // error and event will be reprocessed on the next cron run
        return true;    
    }

    // see if current user can manage copyright status for course
    $context =  context_course::instance($eventdata->course->id);

    // ignore alert if user cannot assign copyright status for the course
    // or if user can assign copyright status, but there is file has not assign copyright status, give alert
    $a = sizeof(get_files_copyright_status_by_course($eventdata->course->id,$CFG->sitedefaultlicense));

    if (!has_capability('moodle/course:manageactivities', $context)||
        $a==0) {
        return true;
    }

    // but first, see if they turned off the copyright status alert for their account
    // ucla_copyright_status_noprompt_<courseid>
    $timestamp = get_user_preferences('ucla_copyright_status_noprompt_' .
            $eventdata->course->id, null, $eventdata->userid);

    // do not display alert if user turned off copyright status alerts or if remind me
    // time has not passed
    if (!is_null($timestamp) && (intval($timestamp) === 0 ||
            $timestamp > time())) {
        return true;
    }

    // now we can display the alert
    $alert_form = new copyright_alert_form(new moodle_url('/blocks/ucla_copyright_status/alert.php',
            array('id' => $eventdata->course->id)), $a, 'post', '',
            array('class' => 'ucla-copyright-status-alert-form'));

    // unfortunately, the display function outputs HTML, rather than returning
    // it, so we need to capture it
    ob_start();
    $alert_form->display();
    $eventdata->notices[] = ob_get_clean();
    return true;
}
