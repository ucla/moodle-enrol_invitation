<?php
/*
 * Generates the settings form for the Library reserves Block
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
            'block_ucla_library_reserves/source_url',
            get_string('headerlibraryreservesurl','block_ucla_library_reserves'),
            get_string('desclibraryreservesurl','block_ucla_library_reserves'),
            'ftp://ftp.library.ucla.edu/incoming/eres/voyager_reserves_data.txt',
            PARAM_URL
        ));

}