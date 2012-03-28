<?php

require_once($CFG->dirroot . '/blocks/ucla_browseby/db/install.php');
function xmldb_block_ucla_browseby_upgrade($oldversion = 0) {
    global $CFG, $PAGE, $SITE;

    $result = true;

    if ($result && $oldversion < 2012032703) {
        xmldb_block_ucla_browseby_install();
    }

    // This adds an instance of this block to the site page if it
    // doesn't already exist
    block_ucla_browseby::add_to_frontpage();

    return $result;
}
