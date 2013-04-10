<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
   $settings = new admin_settingpage('localsettingucla', 
        get_string('pluginname', 'local_ucla'), 'moodle/site:config');

    $settings->add(new admin_setting_configtext(
            'local_ucla/student_access_ends_week',
            get_string('student_access_ends_week', 'local_ucla'),
            get_string('student_access_ends_week_description', 'local_ucla'),
            0, PARAM_INT));

    $ADMIN->add('localplugins', $settings);
}
