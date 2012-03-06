<?php

function xmldb_block_ucla_browseby_upgrade($oldversion = 0) {
    global $CFG;

    $result = true;
    if ($result && $oldversion < 2012030500) {
        // Install the tables, because there exists a version
        // before the tables were declared.
        $result = install_from_xmldb_file($CFG->dirroot 
            . '/blocks/ucla_browseby/db/install.xml');
    }

    return $result;
}
