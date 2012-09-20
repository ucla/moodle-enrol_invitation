<?php
/**
 * Copyright (c) 2012 i>clicker (R) <http://www.iclicker.com/dnn/>
 *
 * This file is part of i>clicker Moodle integrate.
 *
 * i>clicker Moodle integrate is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * i>clicker Moodle integrate is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with i>clicker Moodle integrate.  If not, see <http://www.gnu.org/licenses/>.
 */
/* $Id: upgrade.php 107 2012-04-06 01:48:53Z azeckoski@gmail.com $ */

global $CFG;
require_once ($CFG->dirroot.'/blocks/iclicker/iclicker_service.php');

// This file keeps track of upgrades to this block
function xmldb_block_iclicker_upgrade($oldversion = 0) {
    global $DB;
    $dbman = $DB->get_manager(); /// loads ddl manager and xmldb classes
    if ($oldversion < 2012041700) {
        // Define table iclicker_user_key to be created
        $table = new xmldb_table('iclicker_user_key');

        // Adding fields to table iclicker_user_key
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_key', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table iclicker_user_key
        $table->add_key('pk_id', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('uniq_user_id', XMLDB_KEY_UNIQUE, array('user_id'));

        // Conditionally launch create table for iclicker_user_key
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // iclicker savepoint reached
        upgrade_block_savepoint(true, 2012041700, 'iclicker');
    }
    return true;
}
