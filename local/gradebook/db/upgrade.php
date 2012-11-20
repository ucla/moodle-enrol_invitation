<?php

function xmldb_local_gradebook_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes
    
    if ($oldversion < 2012110700) {

        // Define table ucla_grade_failed_updates to be dropped
        $table = new xmldb_table('ucla_grade_failed_updates');

        // Conditionally launch drop table for ucla_grade_failed_updates
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // gradebook savepoint reached
        upgrade_plugin_savepoint(true, 2012110700, 'local', 'gradebook');
    }

    return true;
}