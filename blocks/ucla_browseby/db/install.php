<?php

require_once($CFG->dirroot . '/blocks/ucla_browseby/eventlib.php');

function xmldb_block_ucla_browseby_install() {
    global $CFG, $DB;

    echo "Running sync...<br>";

    ob_start();

    try {
        run_browseby_sync(null, null, true);
    } catch (dml_exception $de) {
        echo "Deferring sync, most likely local_ucla not installed.\n";
    }

    $res = ob_get_clean();

    echo str_replace("\n", "<br>", $res);

    return true;
}
