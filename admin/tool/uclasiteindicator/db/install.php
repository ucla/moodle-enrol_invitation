<?php

require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');

function xmldb_tool_uclasiteindicator_install() {
    global $CFG, $DB;
  
// per discussion with Deborah/Jonathan (7/9/2012), do not want to manually set
// all non-srs sites to be a certain default type    
//    siteindicator_manager::find_and_set_collab_sites();

    return true;
}
