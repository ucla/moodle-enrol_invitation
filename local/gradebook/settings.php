<?php


defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // speedup for non-admins, add all caps used on this page
    // inject gradebook on/off switch
    // Site administration > Development > Experimental > Experimental settings
    $temp = $ADMIN->locate('experimentalsettings');
    $temp->add(new admin_setting_configcheckbox('gradebook_send_updates', 'Send gradebook updates',
            'When enabled, this will send gradebook notifications to MyUCLA', 0));
}