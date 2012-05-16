<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('localsettingucla', 
        get_string('pluginname', 'local_ucla'), 'moodle/site:config');

    $settings->add(new admin_setting_configtext(
            'local_ucla/student_access_week', 
            get_string('student_access_week_title', 'local_ucla'), 
            get_string('student_access_week_desc', 'local_ucla'), 
            2
        ));

    $ADMIN->add('localplugins', $settings);
}
