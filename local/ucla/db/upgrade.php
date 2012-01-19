<?php
/*
 * Upgrades block.
 */

defined('MOODLE_INTERNAL') || die();

/**
 *  Runs extra commands when upgrading.
 **/
function xmldb_local_ucla_upgrade($oldversion = 0) {
    global $CFG;
    $result = true;

    // copy over latest version of lang file for moodle.php
    if ($result && $oldversion < 2011112800) {
        // copy custom moodle.php to $CFG->dataroot/lang/en_local
        $source = $CFG->dirroot . '/local/ucla/lang/en/moodle.php';
        $dest = $CFG->dataroot . '/lang/en_local';
        
        // first make sure that path to destination exists and source exists
        if ((file_exists($dest) || mkdir($dest, $CFG->directorypermissions, true)) 
                && file_exists($source)) {
            if (!copy($source, $dest . '/moodle.php')) {
                debugging(sprintf('Could not copy %s to %s', $source, $dest));
                $result = false;    // something went wrong
            }                   
        } else {
            debugging('Either cannot create destination or source does not exist');
            $result = false;    // something went wrong
        } 
        
    }    
    
    return $result;
}

