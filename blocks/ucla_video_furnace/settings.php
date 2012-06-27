<?php
/*
 * Generates the settings form for the UCLA Video Furnace Block
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
            'block_ucla_video_furnace/source_url',
            get_string('headervideofurnaceurl','block_ucla_video_furnace'),
            get_string('descvideofurnaceurl','block_ucla_video_furnace'),
            'http://164.67.141.31/~guest/VF_LINKS.TXT',
            PARAM_URL
        ));
}