<?php

function xmldb_block_ucla_browseby_install() {
    global $DB;

    // Run cron job if installing for the first time yo.
    $browseby = block_instance('ucla_browseby');
    $browseby->sync();
}
