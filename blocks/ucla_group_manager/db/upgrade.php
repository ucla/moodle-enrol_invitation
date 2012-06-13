<?php

function xmldb_block_ucla_group_manager_upgrade($oldversion=0) {
    global $CFG, $DB;

    $dbman = $DB->get_manager(); 
    if ($oldversion < 2012060100) {

        // Define table ucla_group_members to be created
        $table = new xmldb_table('ucla_group_members');

        // Adding fields to table ucla_group_members
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('groups_membersid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);

        // Adding keys to table ucla_group_members
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table ucla_group_members
        $table->add_index('groupmembersindex', XMLDB_INDEX_NOTUNIQUE, array('groups_membersid'));

        // Conditionally launch create table for ucla_group_members
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // ucla_group_manager savepoint reached
        upgrade_block_savepoint(true, 2012060100, 'ucla_group_manager');
    }

    return true;
}
