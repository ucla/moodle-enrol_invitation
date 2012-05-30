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

    return $result;
}
