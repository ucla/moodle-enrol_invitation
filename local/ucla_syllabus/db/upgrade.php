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

/**
 * This file keeps track of upgrades to the UCLA syllabus plugin
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    local
 * @subpackage ucla_syllabus
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute UCLA syllabus plugin upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_ucla_syllabus_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    // And upgrade begins here. For each one, you'll need one
    // block of code similar to the next one. Please, delete
    // this comment lines once this file start handling proper
    // upgrade code.

    // if ($oldversion < YYYYMMDD00) { //New version in version.php
    //
    // }
    
    if ($oldversion < 2012092700) {

        // Define table ucla_syllabus_webservice to be created
        $table = new xmldb_table('ucla_syllabus_webservice');

        // Adding fields to table ucla_syllabus_webservice
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('subject', XMLDB_TYPE_CHAR, '200', null, null, null, null);
        $table->add_field('leadingsrs', XMLDB_TYPE_CHAR, '9', null, null, null, null);
        $table->add_field('url', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('token', XMLDB_TYPE_CHAR, '64', null, null, null, null);
        $table->add_field('contact', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('action', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table ucla_syllabus_webservice
        $table->add_key('id', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table ucla_syllabus_webservice
        $table->add_index('enabled', XMLDB_INDEX_NOTUNIQUE, array('enabled', 'action'));

        // Conditionally launch create table for ucla_syllabus_webservice
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // ucla_syllabus savepoint reached
        upgrade_plugin_savepoint(true, 2012092700, 'local', 'ucla_syllabus');
    }

    // Final return of upgrade result (true, all went good) to Moodle.
    return true;
}
