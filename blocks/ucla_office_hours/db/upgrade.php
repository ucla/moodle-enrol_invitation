<?php

function xmldb_block_ucla_office_hours_upgrade($oldversion = 0) {
    global $DB;
            
    $dbman = $DB->get_manager();
    
    if ($oldversion < 2012060700) {

        // Define key courseid (foreign) to be added to ucla_officehours
        $table = new xmldb_table('ucla_officehours');
        $keys[] = new xmldb_key('courseid', XMLDB_KEY_FOREIGN, array('courseid'), 'course', array('id'));
        $keys[] = new xmldb_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $keys[] = new xmldb_key('modifierid', XMLDB_KEY_FOREIGN, array('modifierid'), 'user', array('id'));
        
        foreach ($keys as $key) {
            $dbman->add_key($table, $key);            
        }

        // ucla_office_hours savepoint reached
        upgrade_block_savepoint(true, 2012060700, 'ucla_office_hours');
    }
   
    return true;
}
