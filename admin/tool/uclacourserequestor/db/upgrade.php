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
    . '/tool/uclacourserequestor/lib.php');

/**
 *
 * @global stdClass $CFG
 * @global stdClass $USER
 * @global moodle_database $DB
 * @global core_renderer $OUTPUT
 * @param int $oldversion
 * @return bool always true
 */
function xmldb_tool_uclacourserequestor_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes
	
	$result = true;

    //                           YYYYMMDDVV
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

        // uclacourserequestor savepoint reached
        upgrade_plugin_savepoint(true, 2011052717, 'tool', 'uclacourserequestor');
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

        // uclacourserequestor savepoint reached
        upgrade_plugin_savepoint(true, 2011052717, 'tool', 'uclacourserequestor');
    }
   
    //                YYYYMMDDVV
    if ($oldversion < 2011072704) {

        // Changing precision of field instructor on table ucla_request_classes to (600)
        $table = new xmldb_table('ucla_request_classes');
        $field = new xmldb_field('instructor', XMLDB_TYPE_CHAR, '600', null, null, null, null, 'department');

        // Launch change of precision for field instructor
        $dbman->change_field_precision($table, $field);

        // uclacourserequestor savepoint reached
        upgrade_plugin_savepoint(true, 2011072704, 'tool', 'uclacourserequestor');
    }

    //                YYYYMMDDVV
    if ($oldversion < 2011111601) {

        // Define field status to be dropped from ucla_request_classes
        $table = new xmldb_table('ucla_request_classes');
        $field = new xmldb_field('status');

        // Conditionally launch drop field status
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // uclacourserequestor savepoint reached
        upgrade_plugin_savepoint(true, 2011111601, 'tool', 'uclacourserequestor');
    } 

    if ($oldversion < 2011113000) {
        // Sorry, no: Convert old data...

        //////////////////
        // ALTER FIELDS //
        //////////////////

        // Define field force_urlupdate to be dropped from ucla_request_classes
        $table = new xmldb_table('ucla_request_classes');

        // Rename field contact on table ucla_request_classes to requestoremail
        $field = new xmldb_field('contact', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'instructor');

        // Launch rename field contact
        $dbman->rename_field($table, $field, 'requestoremail');

        // Changing precision of field instructor on table ucla_request_classes to (254)
        $field = new xmldb_field('instructor', XMLDB_TYPE_CHAR, '254', null, null, null, null, 'department');

        // Launch change of precision for field instructor
        $dbman->change_field_precision($table, $field);

        // Rename field force_no_urlupdate on table ucla_request_classes to nourlupdate 
        $field = new xmldb_field('force_no_urlupdate', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'hidden');

        // Launch rename field nourlupdate
        $dbman->rename_field($table, $field, 'nourlupdate');

        // Rename field crosslist on table ucla_request_classes to hostcourse 
        $field = new xmldb_field('crosslist', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'instructor');

        // Launch rename field crosslist
        $dbman->rename_field($table, $field, 'hostcourse');

        // Rename field added_at on table ucla_request_classes to timerequested 
        $field = new xmldb_field('added_at', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0', 'instructor');

        // Launch rename field added_at
        $dbman->rename_field($table, $field, 'timerequested');

        ////////////////
        // NEW FIELDS //
        ////////////////

         // Define field setid to be added to ucla_request_classes
        $field = new xmldb_field('setid', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'hostcourse');

        // Conditionally launch add field setid
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

         // Define field courseid to be added to ucla_request_classes
        $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'setid');

        // Conditionally launch add field courseid
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        /////////////////
        // NEW INDICES //
        /////////////////

        // Define index searchme (unique) to be added to ucla_request_classes
        $index = new xmldb_index('searchme', XMLDB_INDEX_UNIQUE, array('term', 'srs', 'setid'));

        // Conditionally launch add index searchme
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index searchforsrs (not unique) to be added to ucla_request_classes
        $index = new xmldb_index('searchforsrs', XMLDB_INDEX_NOTUNIQUE, array('courseid'));

        // Conditionally launch add index searchforsrs
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index searchdept (not unique) to be added to ucla_request_classes
        $index = new xmldb_index('searchdept', XMLDB_INDEX_NOTUNIQUE, array('department'));

        // Conditionally launch add index searchdept
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index searchaction (not unique) to be added to ucla_request_classes
        $index = new xmldb_index('searchaction', XMLDB_INDEX_NOTUNIQUE, array('action'));

        // Conditionally launch add index searchaction
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        /////////////////////
        // DROP STUFF LAST //
        /////////////////////
        $field = new xmldb_field('force_urlupdate');

        // Conditionally launch drop field force_urlupdate
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define table ucla_request_crosslist to be dropped
        $table = new xmldb_table('ucla_request_crosslist');

        // Conditionally launch drop table for ucla_request_crosslist
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // uclacourserequestor savepoint reached
        upgrade_plugin_savepoint(true, 2011113000, 'tool', 'uclacourserequestor');
    }

    //                YYYYMMDDVV
    if ($oldversion < 2011121900) {

        // Define index uniqtermsrs (unique) to be added to ucla_request_classes
        $table = new xmldb_table('ucla_request_classes');
        $index = new xmldb_index('uniqtermsrs', XMLDB_INDEX_UNIQUE, array('term', 'srs'));

        // Conditionally launch add index uniqtermsrs
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // uclacourserequestor savepoint reached
        upgrade_plugin_savepoint(true, 2011121900, 'tool', 'uclacourserequestor');
    }

    //                YYYYMMDDVV
    if ($oldversion < 2012011800) {
        // Changing precision of field requestoremail on table ucla_request_classes to (255)
        $table = new xmldb_table('ucla_request_classes');
        $field = new xmldb_field('requestoremail', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'department');

        // Launch change of precision for field requestoremail
        $dbman->change_field_precision($table, $field);

        $field = new xmldb_field('instructor', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'course');

        // Launch change of precision for field instructor
        $dbman->change_field_precision($table, $field);

        // uclacourserequestor savepoint reached
        upgrade_plugin_savepoint(true, 2012011800, 'tool', 'uclacourserequestor');
    }

	return $result;
}
