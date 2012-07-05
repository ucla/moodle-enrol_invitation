<?php

require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');

function xmldb_tool_uclasiteindicator_install() {
    global $CFG, $DB;
    
    siteindicator_manager::find_and_set_collab_sites();

    return true;
}
