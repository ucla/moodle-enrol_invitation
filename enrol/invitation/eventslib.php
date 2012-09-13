<?php
/**
 * Events library. 
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Automatically add enrollment plugin for newly created courses.
 * 
 * @param int $courseid
 * @return boolean          Returns false on error, otherwise true. 
 */
function add_site_invitation_plugin($courseid) {
    global $DB;
    
    // make sure you aren't trying something silly like adding enrollment plugin
    // to siteid
    if ($courseid == SITEID) {
        return false;
    }    
    
    // This hopefully means that this plugin IS enabled
    $invitation = enrol_get_plugin('invitation');
    if (empty($invitation)) {
        debugging('Site invitation enrolment plugin is not installed');
        return false;
    }

    // get course object
    $course = $DB->get_record('course', array('id' => $courseid));
    
    // returns instance id, else returns NULL
    $instance_id = $invitation->add_instance($course);    
    if (is_null($instance_id)) {
        debugging('Cannot add site invitation for course: ' . print_r($course));
        return false;
    }
    
    return true;
}
