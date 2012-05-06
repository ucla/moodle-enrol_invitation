<?php
/**
 * Events library. 
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Automatically add enrollment plugin for newly created courses.
 * 
 * @param object $course
 * @return boolean          Returns false on error, otherwise true. 
 */
function add_site_invitation_plugin($course) {
    // This hopefully means that this plugin IS enabled
    $invitation = enrol_get_plugin('invitation');
    if (empty($invitation)) {
        debugging('Site invitation enrolment plugin is not installed');
        return false;
    }
    
    // returns instance id, else returns NULL
    $instance_id = $invitation->add_instance($course);    
    if (is_null($instance_id)) {
        debugging('Cannot add site invitation for course: ' . print_r($course));
        return false;
    }
    
    return true;
}
