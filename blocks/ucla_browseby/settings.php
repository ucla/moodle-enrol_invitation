<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/ucla_browseby/'
    . 'browseby_handler_factory.class.php');

$types = browseby_handler_factory::get_available_types();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox(
        'block_ucla_browseby/syncallterms',
         get_string('title_syncallterms', 'block_ucla_browseby'),
         get_string('desc_syncallterms', 'block_ucla_browseby'), 0));
    
    foreach ($types as $type) {
        $settings->add(new admin_setting_configcheckbox(
            'block_ucla_browseby/disable_' . $type,
            get_string('title_' . $type, 'block_ucla_browseby'), 
            get_string('desc_' . $type, 'block_ucla_browseby'), 
            0));
    }

    $settings->add(new admin_setting_configcheckbox(
        'block_ucla_browseby/use_local_courses',
         get_string('title_use_local_courses', 'block_ucla_browseby'),
         get_string('desc_use_local_courses', 'block_ucla_browseby'), 0));

    $settings->add(new admin_setting_configtext(
        'block_ucla_browseby/ignore_coursenum',
        get_string('title_ignore_coursenum', 'block_ucla_browseby'),
        get_string('desc_ignore_coursenum', 'block_ucla_browseby'),
        '400'));

    $settings->add(new admin_setting_configtext(
        'block_ucla_browseby/allow_acttypes',
        get_string('title_allow_acttypes', 'block_ucla_browseby'),
        get_string('desc_allow_acttypes', 'block_ucla_browseby'),
        ''));
}
