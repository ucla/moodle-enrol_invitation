<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('localsettingucla', 
        get_string('pluginname', 'local_ucla'), 'moodle/site:config');

    if (block_load_class('ucla_weeksdisplay')) {
        // TODO use a better admin_setting object
        $settings->add(new admin_setting_heading(
                'currenttermweek_disabled',
                get_string('currenttermweek_disabled', 'local_ucla'),
                false
            ));
    } else {
        $settings->add(new admin_setting_configtext(
                'currentterm',
                get_string('currentterm_title', 'local_ucla'),
                get_string('currentterm_desc', 'local_ucla'),
                ''
            ));

        $settings->add(new admin_setting_configtext(
                'local_ucla/current_week',
                get_string('current_week_title', 'local_ucla'), 
                get_string('current_week_desc', 'local_ucla'),
                ''
            ));
    }

    $settings->add(new admin_setting_configtext(
            'local_ucla/student_access_week', 
            get_string('student_access_week_title', 'local_ucla'), 
            get_string('student_access_week_desc', 'local_ucla'), 
            2
        ));

    $settings->add(new admin_setting_pickroles(
            'local_ucla/privileged_roles',
            get_string('privileged_roles_title', 'local_ucla'), 
            get_string('privileged_roles_desc', 'local_ucla'), 
            array('manager', 'editingteacher')
        ));

    $ADMIN->add('localplugins', $settings);
}
