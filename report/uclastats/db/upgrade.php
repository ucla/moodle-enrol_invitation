<?php 

function xmldb_report_uclastats_upgrade($oldversion = 0) {
    global $DB;

    $result = true;
    
   
    if ($result && $oldversion < 2013042500) {
    
    //update existing cached results such that
    //file size reports are renamed to system_size reports
    //Note: used execute to avoid overhead of locating all records
    //and then deleting them
    //only use execute if there are no variable params to avoid sql injections
    $DB->execute("UPDATE {ucla_statsconsole} 
                  SET name = 'system_size' 
                  WHERE name = 'file_size'");
        
    }

    return $result;
}
