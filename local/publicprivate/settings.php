<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // speedup for non-admins, add all caps used on this page
    // inject public/private settings into 
    // Site administration > Development > Experimental > Experimental settings
    $temp = $ADMIN->locate('experimentalsettings');
    include_once($CFG->libdir.'/publicprivate/site.class.php');
    if(PublicPrivate_Site::is_installed() || PublicPrivate_Site::is_enabled()) {
        $temp->add(new admin_setting_configcheckbox('enablepublicprivate', 
                get_string('enablepublicprivate', 'local_publicprivate'), 
                get_string('enablepublicprivate_description', 'local_publicprivate'), 0));
    }    
}
