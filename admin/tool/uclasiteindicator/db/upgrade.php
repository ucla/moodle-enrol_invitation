<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/' . $CFG->admin 
    . '/tool/uclasiteindicator/lib.php');

function xmldb_tool_uclasiteindicator_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes
	
    $result = true;

    //                           YYYYMMDDVV
    if ($result && $oldversion < 2012042306) {
        $table = new xmldb_table('ucla_siteindicator_type');
        
        // Drop old table
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        
        // Define table ucla_siteindicator_type to be created
        $table = new xmldb_table('ucla_siteindicator_type');

        // Adding fields to table ucla_siteindicator_type
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('shortname', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table ucla_siteindicator_type
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table ucla_siteindicator_type
        $table->add_index('short_name', XMLDB_INDEX_NOTUNIQUE, array('shortname'));

        // Create table for ucla_siteindicator_type
        $dbman->create_table($table);

        // Populate table
        ucla_indicator_admin::sql_populate_types();
        
        // Find collab sites
        ucla_indicator_admin::find_and_set_collab_sites();

        // uclasiteindicator savepoint reached
        upgrade_plugin_savepoint(true, 2012042306, 'tool', 'uclasiteindicator');
        
    }
    
    if ($result && $oldversion < 2012062900) {

        // Define field courseid to be added to ucla_siteindicator_request
        $table = new xmldb_table('ucla_siteindicator_request');
        $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'requester');

        // Conditionally launch add field courseid
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Define key requestid (unique) to be dropped form ucla_siteindicator_request
        $key = new xmldb_key('requestid', XMLDB_KEY_UNIQUE, array('requestid'));

        // Launch drop key requestid
        $dbman->drop_key($table, $key);

        // Changing nullability of field requestid on table ucla_siteindicator_request to null
        $field = new xmldb_field('requestid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'id');

        // Launch change of nullability for field requestid
        $dbman->change_field_notnull($table, $field);

        // Define index requestid_idx (not unique) to be added to ucla_siteindicator_request
        $index = new xmldb_index('requestid_idx', XMLDB_INDEX_NOTUNIQUE, array('requestid'));

        // Conditionally launch add index requestid_idx
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // uclasiteindicator savepoint reached
        upgrade_plugin_savepoint(true, 2012062900, 'tool', 'uclasiteindicator');
    }
    
    return $result;
}
