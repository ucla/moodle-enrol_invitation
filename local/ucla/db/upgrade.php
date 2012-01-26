<?php
/*
 * Upgrades block.
 */

defined('MOODLE_INTERNAL') || die();

/**
 *  Runs extra commands when upgrading.
 **/
function xmldb_local_ucla_upgrade($oldversion=0) {
    global $CFG, $DB, $OUTPUT;
    $dbman = $DB->get_manager();

    $result = true;

    if ($oldversion < 2011110400) {
        // Define table ucla_reg_subjectarea to be created
        $table = new xmldb_table('ucla_reg_subjectarea');

        // Adding fields to table ucla_reg_subjectarea
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('subjarea', XMLDB_TYPE_CHAR, '7', null, XMLDB_NOTNULL, null, null);
        $table->add_field('subj_area_full', XMLDB_TYPE_CHAR, '60', null, XMLDB_NOTNULL, null, null);
        $table->add_field('home', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('modified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);

        // Adding keys to table ucla_reg_subjectarea
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table ucla_reg_subjectarea
        $table->add_index('dexs', XMLDB_INDEX_NOTUNIQUE, array('subjarea'));

        // Conditionally launch create table for ucla_reg_subjectarea
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

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

        // ucla savepoint reached
        upgrade_plugin_savepoint(true, 2011110400, 'local', 'ucla');
    }
    
    // copy over latest version of lang file for moodle.php
    if ($result && $oldversion < 2011112800) {
        // copy custom moodle.php to $CFG->dataroot/lang/en_local
        $source = $CFG->dirroot . '/local/ucla/lang/en/moodle.php';
        $dest = $CFG->dataroot . '/lang/en_local';
        
        // first make sure that path to destination exists and source exists
        if ((file_exists($dest) || mkdir($dest, $CFG->directorypermissions, true)) 
                && file_exists($source)) {
            if (!copy($source, $dest . '/moodle.php')) {
                debugging(sprintf('Could not copy %s to %s', $source, $dest));
                $result = false;    // something went wrong
            }                   
        } else {
            debugging('Either cannot create destination or source does not exist');
            $result = false;    // something went wrong
        } 
        
    }    

    return $result;
}

