<?php

/*
 * Generates the settings form for the Bruincast Block
 */

$settings->add(new admin_setting_configcheckbox(
            'quiet_mode',
            get_string('headerquietmode','block_bruincast'),
            get_string('descquietmode','block_bruincast'),
            '0'
        ));

$settings->add(new admin_setting_configtext(
            'bruincast_data',
            get_string('headerbruincasturl','block_bruincast'),
            get_string('descbruincasturl','block_bruincast'),
            'http://www.oid.ucla.edu/help/info/bcastlinks/',
            PARAM_TEXT 
        ));
$settings->add(new admin_setting_configtext(
            'bruincast_errornotify_email',
            get_string('headererrornotifyemail','block_bruincast'),
            get_string('descerrornotifyemail','block_bruincast'),
            '',
            PARAM_TEXT
        ));
