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

    if ($oldversion < 2012070202) {

        // Define table ucla_siteindicator_type to be dropped
        $table = new xmldb_table('ucla_siteindicator_type');

        // Conditionally launch drop table for ucla_siteindicator_type
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        
        $table = new xmldb_table('ucla_siteindicator_request');
        $field = new xmldb_field('support');

        // Conditionally launch drop field support
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        $field = new xmldb_field('type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, 'requestid');

        // Launch change of type for field type
        $dbman->change_field_type($table, $field);

        
        // Define index type (not unique) to be dropped form ucla_siteindicator
        $table = new xmldb_table('ucla_siteindicator');
        $index = new xmldb_index('type', XMLDB_INDEX_NOTUNIQUE, array('type'));

        // Conditionally launch drop index type
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        
        $field = new xmldb_field('type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, 'courseid');

        // Launch change of type for field type
        $dbman->change_field_type($table, $field);
        
        $index = new xmldb_index('type', XMLDB_INDEX_NOTUNIQUE, array('type'));

        // Conditionally launch add index type
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        // uclasiteindicator savepoint reached
        upgrade_plugin_savepoint(true, 2012070202, 'tool', 'uclasiteindicator');
    }
    
    return $result;
}
