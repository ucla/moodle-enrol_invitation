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

/**
 *
 * @global stdClass $CFG
 * @global stdClass $USER
 * @global moodle_database $DB
 * @global core_renderer $OUTPUT
 * @param int $oldversion
 * @return bool always true
 */
function xmldb_report_courserequestor_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes
	
	$result = true;

	    if ($result && $oldversion < 2011052717) {

        // Define table ucla_request_classes to be created
        $table = new xmldb_table('ucla_request_classes');

        // Adding fields to table ucla_request_classes
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('term', XMLDB_TYPE_CHAR, '3', null, XMLDB_NOTNULL, null, null);
        $table->add_field('srs', XMLDB_TYPE_CHAR, '9', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('department', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('instructor', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('contact', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('crosslist', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('added_at', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0');
        $table->add_field('action', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('mailinst', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1');
        $table->add_field('hidden', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('force_urlupdate', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('force_no_urlupdate', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Adding keys to table ucla_request_classes
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for ucla_request_classes
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // courserequestor savepoint reached
        upgrade_plugin_savepoint(true, 2011052717, 'report', 'courserequestor');
    }
	
	    if ($result && $oldversion < 2011052717) {

        // Define table ucla_request_crosslist to be created
        $table = new xmldb_table('ucla_request_crosslist');

        // Adding fields to table ucla_request_crosslist
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('term', XMLDB_TYPE_CHAR, '3', null, XMLDB_NOTNULL, null, null);
        $table->add_field('srs', XMLDB_TYPE_CHAR, '9', null, XMLDB_NOTNULL, null, null);
        $table->add_field('aliassrs', XMLDB_TYPE_CHAR, '9', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '20', null, null, null, null);
		
        // Adding keys to table ucla_request_crosslist
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for ucla_request_crosslist
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // courserequestor savepoint reached
        upgrade_plugin_savepoint(true, 2011052717, 'report', 'courserequestor');
    }
	
	return $result;
}