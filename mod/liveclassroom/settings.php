<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/mod/liveclassroom/lib.php');
    require_once($CFG->dirroot.'/mod/liveclassroom/settingslib.php');

    global $PAGE;
    $PAGE->requires->js('/mod/liveclassroom/js/configcheck.js');
    $jsstrs = array('wrongconfigurationURLunavailable','emptyAdminUsername','emptyAdminPassword','trailingSlash','trailingHttp');
    $PAGE->requires->strings_for_js($jsstrs, 'liveclassroom');

    $settings->add(new admin_setting_heading('liveclassroom_header', get_string('serverconfiguration', 'liveclassroom'), ''));
    $settings->add(new admin_setting_configtext('liveclassroom_servername', get_string('servername', 'liveclassroom'), get_string('configservername', 'liveclassroom'), ''));
    $settings->add(new admin_setting_configtext('liveclassroom_adminusername', get_string('adminusername', 'liveclassroom'), '', '', PARAM_TEXT));
    $settings->add(new admin_setting_configpasswordunmask('liveclassroom_adminpassword', get_string('adminpassword', 'liveclassroom'), '', ''));

    $settings->add(new admin_setting_liveclassroom_classroomversion('liveclassroom_classversion', get_string('lcversion', 'liveclassroom'), ''));
    $settings->add(new admin_setting_liveclassroom_integrationversion('liveclassroom_integrationversion', get_string('integrationversion', 'liveclassroom'), ''));

    $logchoices = array('1' => 'DEBUG', '2' => 'INFO', '3' => 'WARN', '4' => 'ERROR');
    $settings->add(new admin_setting_liveclassroom_loglevel('liveclassroom_loglevel', get_string('loglevel', 'liveclassroom'), '', 2, $logchoices));

    $settings->add(new admin_setting_liveclassroom_configtest('liveclassroom_configtest'));
}

