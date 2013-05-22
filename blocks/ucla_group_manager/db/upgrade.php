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

    // Fixes issue with the ever growing ucla_group_members table.
    if ($oldversion < 2013052000) {
        // Create new table with unique column for groups_membersid.
        $newtable = new xmldb_table('ucla_group_members_new');

        $newtable->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $newtable->add_field('groups_membersid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $newtable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $newtable->add_index('groupmembersunique', XMLDB_INDEX_UNIQUE, array('groups_membersid'));
        if ($dbman->table_exists($newtable)) {
            // In instances of failed upgrades, clear out old attempts.
            $dbman->drop_table($newtable);
        }
        $dbman->create_table($newtable);

        // Transfer over unique entries from old ucla_group_members table.
        $results = $DB->get_recordset_select('ucla_group_members', '1=1', array(),
                '', 'DISTINCT groups_membersid');
        if ($results->valid()) {
            // Display progress.
            $pbar = new progress_bar('ucla_group_members_upgrade', 500, true);
            $totalcount = $DB->count_records_select('ucla_group_members', '1=1',
                    array(), $countitem="COUNT(DISTINCT groups_membersid)");
            $i = 1;
            
            foreach ($results as $result) {
                $pbar->update($i, $totalcount, "Upgrading ucla_group_members table - $i/$totalcount.");
                $DB->insert_record('ucla_group_members_new', $result, false, true);
                ++$i;
            }
        }

        // Now drop old table and replace it with the new table.
        $oldtable = new xmldb_table('ucla_group_members');
        $dbman->drop_table($oldtable);
        $dbman->rename_table($newtable, 'ucla_group_members');

        upgrade_block_savepoint(true, 2013052000, 'ucla_group_manager');
    }

    return true;
}
