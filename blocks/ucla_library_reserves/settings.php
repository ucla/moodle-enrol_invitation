<?php

/*
 * Generates the settings form for the Library reserves Block
 */

 $settings->add(new admin_setting_configtext(
        'libraryreserves_data',
        get_string('headerlibraryreservesurl','block_ucla_library_reserves'),
        get_string('desclibraryreservesurl','block_ucla_library_reserves'),
        'ftp://ftp.library.ucla.edu/incoming/eres/voyager_reserves_data.txt',
        PARAM_TEXT
    ));
