<?php

/*
 * Generates the settings form for the Bruincast Block
 */

defined('MOODLE_INTERNAL') || die;

if($ADMIN->fulltree) {

    $settings->add(new admin_setting_configcheckbox(
                'block_ucla_bruincast/quiet_mode',
                get_string('quiet_mode_header','block_ucla_bruincast'),
                get_string('quiet_mode_desc','block_ucla_bruincast'),
                0
            ));

    $settings->add(new admin_setting_configtext(
               'block_ucla_bruincast/source_url',
                get_string('source_url_header','block_ucla_bruincast'),
                get_string('source_url_desc','block_ucla_bruincast'),
                'http://www.oid.ucla.edu/help/info/bcastlinks/',
                PARAM_URL 
            ));
    $settings->add(new admin_setting_configtext(
                'block_ucla_bruincast/errornotify_email',
                get_string('errornotify_email_header','block_ucla_bruincast'),
                get_string('errornotify_email_desc','block_ucla_bruincast'),
                '',
                PARAM_EMAIL
            ));

}
