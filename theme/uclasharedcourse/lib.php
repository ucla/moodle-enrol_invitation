<?php

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
    
    // Grab stored file
    $fs = get_file_storage();
    $stored_file = $fs->get_file($context->id, 'theme_uclasharedcourse', $filearea, $itemid, '/', $filename);

    // Serve
    send_stored_file($stored_file, 86400, 0, $forcedownload);
}
