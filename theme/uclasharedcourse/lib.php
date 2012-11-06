<?php
require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');

/**
 * Serves course logo images
 * 
 * @param type $course
 * @param type $cm
 * @param type $context
 * @param type $filearea
 * @param array $args
 * @param type $forcedownload
 * @param array $options 
 */
function theme_uclasharedcourse_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    
    $itemid = clean_param(array_shift($args), PARAM_INT);
    $filename = clean_param(array_shift($args), PARAM_TEXT);

    // If a site is 'private', then we only display logos to enrolled users
    if($collabsite = siteindicator_site::load($course->id)) {
        global $USER;
        if($collabsite->property->type == siteindicator_manager::SITE_TYPE_PRIVATE &&
                (!is_enrolled($context, $USER) && !has_capability('moodle/course:update', $context))) {
            send_file_not_found();
        }
    }

    // Grab stored file
    $fs = get_file_storage();
    $stored_file = $fs->get_file($context->id, 'theme_uclasharedcourse', $filearea, $itemid, '/', $filename);

    // Serve
    send_stored_file($stored_file, 86400, 0, $forcedownload);
}
