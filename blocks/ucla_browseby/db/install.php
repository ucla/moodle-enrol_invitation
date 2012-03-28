<?php

function xmldb_block_ucla_browseby_install() {
    global $CFG, $DB;

    // Run cron job if installing for the first time yo.
    $browseby = block_instance('ucla_browseby');

    echo "Running sync...<br>";

    ob_start();
    $browseby->sync($browseby->get_all_terms());
    $res = ob_get_clean();

    echo str_replace("\n", "<br>", $res);

    echo "Adding block to site...";
    blocks_add_default_course_blocks(get_site());
    echo "done.<br>";
}
