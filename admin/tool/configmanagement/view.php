<?php
/**
 * Either display or delete config dump file
 */
require(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . '/filelib.php');

require_login();
if (!is_siteadmin($USER->id)) {
    print_error('accessdenied', 'admin');
}

$dir = $CFG->dataroot.'/configmanagement/';

// check if user wants to delete a file
if ($delete_file = optional_param('delete', false, PARAM_FILE)) {
    $delete_file = basename($delete_file);
    // make sure it exists, then delete
    if (is_file($dir . $delete_file)) {
        if (unlink($dir . $delete_file)) {
            // redirect user back to index page
            // TODO: Have nice sucess message after redirect
            redirect(new moodle_url('/'.$CFG->admin.'/tool/configmanagement/index.php'));
        }
    } else {
        send_file_not_found();
    }    
} else {
    // user wants to download file
    $config_dump_file = required_param('name', PARAM_FILE);
    $config_dump_file = basename($config_dump_file);
    
    if (is_file($dir . $config_dump_file)) {
        send_file($dir . $config_dump_file, $config_dump_file, 0, false, false, true);
    } else {
        send_file_not_found();
    }
}

