<?php

/**
 * Post installation and migration code.
 *
 * @package    local
 * @subpackage publicprivate
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_local_publicprivate_install() {
    global $CFG;
    require_once($CFG->libdir . '/publicprivate/site.class.php');
    
    if(!PublicPrivate_Site::is_installed()) {
        PublicPrivate_Site::install();
    }    
}
