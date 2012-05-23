<?php

/*
 * Generates the settings form for the Bruincast Block
 */

defined('MOODLE_INTERNAL') || die;

if($ADMIN->fulltree) {

    $settings->add(new admin_setting_configcheckbox(
                'quiet_mode',
                get_string('headerquietmode','block_ucla_bruincast'),
                get_string('descquietmode','block_ucla_bruincast'),
                '0'
            ));

    $settings->add(new admin_setting_configtext(
               'bruincast_data',
                get_string('headerbruincasturl','block_ucla_bruincast'),
                get_string('descbruincasturl','block_ucla_bruincast'),
                'http://www.oid.ucla.edu/help/info/bcastlinks/',
                PARAM_URL 
            ));
    $settings->add(new admin_setting_configtext(
                'bruincast_errornotify_email',
                get_string('headererrornotifyemail','block_ucla_bruincast'),
                get_string('descerrornotifyemail','block_ucla_bruincast'),
                '',
                PARAM_EMAIL
            ));

}
