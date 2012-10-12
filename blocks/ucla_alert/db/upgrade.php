<?php
require_once($CFG->dirroot . '/blocks/ucla_alert/locallib.php');

function xmldb_block_ucla_alert_upgrade($oldversion = 0) {
    global $DB;

    $result = true;
    $dbman = $DB->get_manager();
    
    if ($oldversion < 2012101100) {

        // Define table ucla_alerts to be dropped
        $table = new xmldb_table('ucla_alert');

        // Conditionally launch drop table for ucla_alerts
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table ucla_alerts to be created
        $table = new xmldb_table('ucla_alerts');

        // Adding fields to table ucla_alerts
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('entity', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('render', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('json', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('html', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table ucla_alerts
        $table->add_key('id_key', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for ucla_alerts
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // ucla_alert savepoint reached
        upgrade_block_savepoint(true, 2012101100, 'ucla_alert');
    }

    if ($oldversion < 2012101202) {
        ucla_alert::install_once();
    }

    return $result;
}
