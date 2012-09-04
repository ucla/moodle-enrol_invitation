<?php
/**
 * Events for UCLA course format.
 *
 * @package format_ucla
 * @copyright 2012 UC Regents
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * When a course is updated/added, if it is using the UCLA format, make sure 
 * that coursedisplay is set to COURSE_DISPLAY_MULTIPAGE
 * 
 * @param object $course  course object
 * @return boolean
 */
function fix_coursedisplay($course) {
    global $DB;
    
    if ($course->format == 'ucla' && 
            $course->coursedisplay != COURSE_DISPLAY_MULTIPAGE) {
        // need to update database table
        $course->coursedisplay = COURSE_DISPLAY_MULTIPAGE;
        $DB->update_record('course', $course);
    }

    return true;
}
