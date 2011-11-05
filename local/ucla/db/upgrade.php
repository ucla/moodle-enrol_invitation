<?php
/**
 * Keeps track of upgrades to local_ucla.
 * 
 * @package local_ucla
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_local_ucla_upgrade($oldversion)
{
    global $CFG, $DB;

    $result = true;
    $dbman = $DB->get_manager();

    if ($oldversion < 2011110400) {
        // Define table ucla_rolemapping to be created
        $table = new xmldb_table('ucla_rolemapping');

        // Adding fields to table ucla_rolemapping
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('pseudo_role', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('moodle_roleid', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('subject_area', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, '*SYSTEM*');

        // Adding keys to table ucla_rolemapping
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for ucla_rolemapping
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // database savepoint reached
        upgrade_plugin_savepoint(true, 2011110400, 'ucla', 'local');
    }
}

    