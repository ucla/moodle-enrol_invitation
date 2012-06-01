<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot.'/mod/voicetools/lib.php');
    require_once($CFG->dirroot.'/mod/voicetools/settingslib.php');

    global $PAGE;
    $PAGE->requires->js('/mod/voicetools/js/voicetools.js');
    $jsstrs = array('wrongconfigurationURLunavailable','emptyAdminUsername','emptyAdminPassword','trailingSlash','trailingHttp');
    $PAGE->requires->strings_for_js($jsstrs, 'voicetools');

    $settings->add(new admin_setting_heading('voicetools_header', get_string('serverconfiguration', 'voicetools'), ''));
    $settings->add(new admin_setting_configtext('voicetools_servername', get_string('servername', 'voicetools'), get_string('configservername', 'voicetools'), ''));
    $settings->add(new admin_setting_configtext('voicetools_adminusername', get_string('adminusername', 'voicetools'), '', '', PARAM_TEXT));
    $settings->add(new admin_setting_configpasswordunmask('voicetools_adminpassword', get_string('adminpassword', 'voicetools'), '', ''));

    $settings->add(new admin_setting_voicetools_voiceversion('voicetools_voiceversion', get_string('vtversion', 'voicetools'), ''));
    $settings->add(new admin_setting_voicetools_integrationversion('voicetools_integrationversion', get_string('integrationversion', 'voicetools'), ''));

    $logchoices = array('1' => 'DEBUG', '2' => 'INFO', '3' => 'WARN', '4' => 'ERROR');
    $settings->add(new admin_setting_voicetools_loglevel('voicetools_loglevel', get_string('loglevel', 'voicetools'), '', 2, $logchoices));

    $settings->add(new admin_setting_voicetools_configtest('voicetools_configtest'));
}

