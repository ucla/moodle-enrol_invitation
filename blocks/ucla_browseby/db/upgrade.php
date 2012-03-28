<?php

require_once($CFG->dirroot . '/blocks/ucla_browseby/db/install.php');
function xmldb_block_ucla_browseby_upgrade($oldversion = 0) {
    global $CFG;

    $result = true;

    xmldb_block_ucla_browseby_install();

   
    // Add automatically to site home
    

    return $result;
}
