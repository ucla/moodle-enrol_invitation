<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/ucla_browseby/'
    . 'browseby_handler_factory.class.php');

$types = browseby_handler_factory::get_available_types();

if ($ADMIN->fulltree) {
    foreach ($types as $type) {
        $settings->add(new admin_setting_configcheckbox(
            'block_ucla_browseby/disable_' . $type,
            get_string('title_' . $type, 'block_ucla_browseby'), 
            get_string('desc_' . $type, 'block_ucla_browseby'), 
            0));
    }
}
