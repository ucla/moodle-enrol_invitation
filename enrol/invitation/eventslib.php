<?php
/**
 * Events library. 
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Automatically add enrollment plugin for newly created courses.
 * 
 * @param mixed $param
 * @return boolean          Returns false on error, otherwise true. 
 */
function add_site_invitation_plugin($param) {
    global $DB;

    // handle different parameter types (course_created vs course_restored)
    if (isset($param->id)) {
        $courseid = $param->id;    // course created
    } else {
        // only respond to course restores
        if ($param->type != backup::TYPE_1COURSE) {
            return true;
        }
        $courseid = $param->courseid;  // course restored
    }
    
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
