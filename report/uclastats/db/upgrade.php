<?php 

function xmldb_report_uclastats_upgrade($oldversion = 0) {
    global $DB;

    $result = true;
    
   
    if ($result && $oldversion < 2013042500) {
    
    // update existing cached results such that
    // file size reports are renamed to system_size reports
    // Note: used execute to avoid overhead of locating all records
    // and then deleting them
    // only use execute if there are no variable params to avoid sql injections
    $DB->execute("UPDATE {ucla_statsconsole} 
                  SET name = 'system_size' 
                  WHERE name = 'file_size'");
        
    }
    
    if ($result && $oldversion < 2013061300) {
    
    // update existing cached results such that
    // inactive site reports are now num site reports

        $DB->execute("UPDATE {ucla_statsconsole} 
                      SET name = 'collab_num_sites' 
                      WHERE name = 'inactive_collab_sites'");
        
        $DB->execute("UPDATE {ucla_statsconsole} 
                      SET name = 'course_num_sites' 
                      WHERE name = 'inactive_course_sites'");

    }

    return $result;
}
