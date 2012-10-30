<?php

require_once($CFG->dirroot . '/blocks/ucla_alert/locallib.php');

function xmldb_block_ucla_alert_install() {
    global $CFG, $DB;

    return ucla_alert::install_once();
}
