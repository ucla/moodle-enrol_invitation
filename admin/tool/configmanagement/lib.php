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
 * Library functions for saving and loading configuration settings.
 *
 * The following tables are saved:
 *   config, config_plugins,
 *   role, role_allowassign, role_allowoverride, role_assignments, 
 *       role_capabilities, role_names, role_sortorder,
 *   block, modules,
 *   user
 * 
 * $excludeconfiglist contains the fields in config that are skipped
 * 
 * The following fields in config_plugins are skipped:
 *   open_ssl
 # In the role_capabilities tables, all modifierids are set to null.
 * In the user table, only rows where auth='manual' are saved.
 * This corresponds to those users with normal Moodle logins, as opposed to, for example, Shibboleth.
 *
 * The following tables are loaded:
 *   Config - Completely replaced by backup
 *   Config Plugins - Completely replaced by backup
 *   Role - Completely replaced by backup
 *   Role Allow Assign - Completely replaced by backup
 *   Role Allow Override - Completely replaced by backup
 *   Role Assignments - Only update roleid field to match role:id
 *   Role Capabilities - Replace all site-wide contexts in database with
 *                       site-wide contexts found in backup.  Non-site-wide is unchanged.
 *                       Modifierid is set to the user performing the restore.
 *   Role Names - Replace all site-wide contexts in database with
 *                site-wide contexts found in backup.  Non-site-wide is unchanged.
 *   Role Sort Order - Only update roleid field to match role:id
 *   Block - not restored
 *   Modules - not restored
 *   User - Only manual accounts (auth = 'manual') which do not exist in table
 *          (according to unique condition: mnethostid + username) are added.
 *          No existing rows are changed.
 *
 * @package   configmanagement
 * @copyright 2009 Jeffrey Su
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//Saving Configuration settings
/**
 * Write records from get_records() to an open file
 *
 * @param resource $fp - A file pointer opened by fopen
 * @param string $records - The array of objects returned by get_records()
 * @return none
 */
function write_records_to_file($fp, &$records) {
    //foreach, just for a little more human readability
    foreach ($records as $record) {
        fwrite($fp, json_encode($record)."\n");
    }
}

/**
 * Checks if a user is an admin before writing to file
 * The records must contain an id field, which corresponds to mdl_user:id
 *
 * @param resource $fp - A file pointer opened by fopen
 * @param string $records - The array of objects returned by get_records()
 * @return array - An array of the user id's which were written
 */
function write_admin_users_to_file($fp, &$records) {
    $admins = array();
    foreach ($records as $record) {
        if (property_exists($record, 'id') && is_siteadmin($record->id)) {
            fwrite($fp, json_encode($record)."\n");
            $admins[] = $record->id;
        }
    }
    return $admins;
}

/**
 * Write all role tables to open file.
 *
 * @param resource $fp - A file pointer opened by fopen
 * @param string $divider - The string which is placed around section headers
 * @param string endl - An optional string which is appended to each line.
 * @return none
 */
function write_roles($fp, $divider, $endl="<br />\n") {
    //Roles
    fwrite($fp, $divider.'Roles'.$divider."\n");
    $records = get_records('role', '', '', 'id');
    if ($records) {
        write_records_to_file($fp, $records);
        echo get_string('configroletable', 'tool_configmanagement')." written.$endl";
    }
    else {
        echo get_string('configroletable', 'tool_configmanagement')." skipped.$endl";
    }

    //Role Allow Assign
    fwrite($fp, $divider.'Role_Allow_Assign'.$divider."\n");
    $records = get_records('role_allow_assign', '', '', 'id');
    if ($records) {
        write_records_to_file($fp, $records);
        echo get_string('configroleallowassigntable', 'tool_configmanagement')." written.$endl";
    }
    else {
        echo get_string('configroleallowassigntable', 'tool_configmanagement')." skipped.$endl";
    }

    //Role Allow Override
    fwrite($fp, $divider.'Role_Allow_Override'.$divider."\n");
    $records = get_records('role_allow_override', '', '', 'id');
    if ($records) {
        write_records_to_file($fp, $records);
        echo get_string('configroleallowoverridetable', 'tool_configmanagement')." written.$endl";
    }
    else {
        echo get_string('configroleallowoverridetable', 'tool_configmanagement')." skipped.$endl";
    }

    //Role Assignments
    fwrite($fp, $divider.'Role_Assignments'.$divider."\n");
    //Use get_recordset because role_assignments could be large
    unset($records);    //Clean-up
    $rs = get_recordset('role_assignments', '', '', 'id');
    if ($rs) {
        while ($record = rs_fetch_next_record($rs)) {
            fwrite($fp, json_encode($record)."\n");
        }
        echo get_string('configroleassignmentstable', 'tool_configmanagement')." written.$endl";
    }
    else {
        echo get_string('configroleassignmentstable', 'tool_configmanagement')." skipped.$endl";
    }
    unset($rs); //Clean-up

    //Role Capabilities
    fwrite($fp, $divider.'Role_Capabilities'.$divider."\n");
    $records = get_records('role_capabilities', '', '', 'roleid');
    if ($records) {
        foreach ($records as $entry) {
            //Set modifierid to null, it doesn't make sense in other servers
            if (isset($entry->modifierid)) {
                $entry->modifierid = NULL;
            }
        }
        write_records_to_file($fp, $records);
        echo get_string('configrolecapabilitiestable', 'tool_configmanagement')." written.$endl";
    }
    else {
        echo get_string('configrolecapabilitiestable', 'tool_configmanagement')." skipped.$endl";
    }

    //Role Names
    fwrite($fp, $divider.'Role_Names'.$divider."\n");
    $records = get_records('role_names', '', '', 'id');
    if ($records) {
        write_records_to_file($fp, $records);
        echo get_string('configrolenamestable', 'tool_configmanagement')." written.$endl";
    }
    else {
        echo get_string('configrolenamestable', 'tool_configmanagement')." skipped.$endl";
    }

    //Role Sort Order
    fwrite($fp, $divider.'Role_SortOrder'.$divider."\n");
    $records = get_records('role_sortorder', '', '', 'id');
    if ($records) {
        write_records_to_file($fp, $records);
        echo get_string('configrolesortordertable', 'tool_configmanagement')." written.$endl";
    }
    else {
        echo get_string('configrolesortordertable', 'tool_configmanagement')." skipped.$endl";
    }
}

//Loading Configuration Settings
/**
 * Shifts a field to begin numbering at an offset
 * The field must be a numeric type.
 * The current minimum value of that field will be set to the offset
 * All others will be decreased (or increased) by the appropriate value
 *
 * @param string $table - The name of the table to update- Do not add the prefix!
 * @param string $field - The field to update
 * @param uint $offset - The new starting value of field
 * @uses CFG
 * @return bool|int - False if failure, The difference between old min and offset otherwise
 */
function change_field_offset($table, $field='id', $offset=1) {
    if (is_string($table) && is_string($field) && is_numeric($offset)) {
        if ($offset >= 0) {
            global $CFG;
            $sql = "SELECT MIN($field) as min FROM $CFG->prefix$table";
            $recordset = get_records_sql($sql, 0, 1);
            
            if (!empty($recordset)) {
                $record = current($recordset);
                if (isset($record->min)) {
                    $delta = $record->min-$offset;

                    $command = "UPDATE $CFG->prefix$table SET $field = $field - $delta;";
                    $success = execute_sql($command, false);
                    if ($success === true) {
                        return $delta;
                    }
                }
            }
        }
    }
    return false;
}

/**
 * Creates an associative array mapping a field to a row
 * The key is the field, the value is an array of all records matching that field
 * This is the equivalent of a C++ multimap
 *
 * @param string $tablename - The name of the table to index
 * @param string $fieldname - The name of the field to create the index on
 * @return bool|array - False if failure, An associative array of arrays otherwise
 */
function create_table_index($tablename, $fieldname) {
    if (!is_string($tablename) || !is_string($fieldname)) {
        return false;
    }

    $map = array();

    $recordset = get_records($tablename);    
    if ($recordset) {
        foreach ($recordset as $record) {
            $field = $record->$fieldname;
            if (isset($map[$field])) {
                $map[$field][] = $record;
            }
            else {
                $map[$field] = array($record);
            }
        }
    }
    
    return $map;
}

/**
 * Checks that an object contains the specified fields
 *
 * @param object $obj - The object to check
 * @param array $fields - An array, whose keys are the strings to check
 * @return bool - Whether all fields were found
 */
function exists_fields($obj, $fields) {
    foreach ($fields as $field=>$isstring) {
        if (!isset($obj->$field)) {
            return false;
        }
    }
    return true;
}

/**
 * Returns a table as an associative array
 * The key is the primary key of the table
 * The values is the entire row (including primary key)
 *
 * @param string $table - The name of the table, without the prefix
 * @return bool|array - False if failure, the array otherwise
 */
function get_records_as_assoc_array($table) {
    if (is_string($table)) {
        $recordset = get_records($table);
        if ($recordset) {
            if (isset(current($recordset)->id)) {
                
                $rowfromid = array();
                foreach ($recordset as $record) {
                    $rowfromid[$record->id] = $record;
                }
                
                return $rowfromid;
            }
            else {
                return false;
            }
        }
        else {
            return array();
        }
    }
    return false;
}

/**
 * Shorthand for INSERT INTO table VALUES values
 * Allows insertion of one or more rows into a table
 * Calling insert one time with many values is MUCH MUCH faster than 
 * calling insert many times with one value
 *
 * @param string $table - The table to insert into
 * @param string $values - The values to insert.  It may be one or more than one.
 * @param bool $print - If true, then an error will be printed on failure
 * @uses CFG
 * @return bool - Whether the insert was successful
 */
function insert_values($table, $values, $print=true) {
    global $CFG; 
    $command = "INSERT INTO $CFG->prefix$table VALUES $values";
    $success = execute_sql($command, false);
    if(!$success) {
        $success = insert_values_onerror($table, $values);
    }
    if (!$success && $print) {
        echo "<p>Warning: Could not insert into $CFG->prefix$table: $values</p>";
    }
    return $success;
}

/**
 * Breaks up the $values string into packets of $maxpacketsize before inserting
 * into sql.  This function is only called if insert_values() fails.  It's
 * assumed the failure is because we reached the max_allowed_packet in sql.
 *
 * @uses $CFG
 * @param string $table The table to insert to.
 * @param string $values The values being inserted into the table.
 * @return bool Success
 */
function insert_values_onerror($table, $values) {
    global $CFG;
    $maxpacketsize = 1000000;   //Maximum number of bytes MySQL can handle
    
    $offset = 0;
    $length = strlen($values);
    $success = true;

    while ($length - $offset > $maxpacketsize && $success) {
        $index = $offset + $maxpacketsize;
        
        //Look for last occurence of comma within $maxpacketsize chars
        while ($index >= $offset && $values[$index] != ')') {
            $index--;
        }
        if ($index < $offset) {
            //No comma, so send the rest and hope for best
            break;
        }
        //now index points to the last comma

        $index++;   // bump so we incude the ')'
        $someids = substr($values, $offset, $index - $offset);

        $command = "INSERT INTO $CFG->prefix$table VALUES $someids;";
        $success = execute_sql($command, false);

        $offset =  $index + 1;
    }
    if($success) {
        // Finish off the remaining values
        $remainingids = substr($values, $offset);
        $command = "INSERT INTO $CFG->prefix$table VALUES $remainingids";
        $success = execute_sql($command, false);
    }
    return $success;
}

/**
 * Converts an object into (field1, field2, ...) form
 * Strings will be enclosed by single quotes
 *
 * @param object $obj - The object to output
 * @param array $fields - An array whose keys (string) are the fieldnames to output
 *                       Output will be in the order specified by fields
 *                       Each element is a bool specifying whether the output value is a string
 *                       
 * @return bool|string - False if error, a string representation of obj otherwise
 */
function make_value_from_obj($obj, $fields) {
    if (!exists_fields($obj, $fields)) {
        return false;
    }

    $len = count($fields);
    if ($len === 0) {
        $value =  '()';
    }
    else if ($len === 1) {
        $field = current($fields);
        $value = '('.$obj->$field.')';
    }
    else {
        $buffer = array();
        foreach ($fields as $field=>$isstring) {
            if ($isstring) {
                $buffer[] = "'".$obj->$field."'";
            }
            else {
                $buffer[] = $obj->$field;
            }
        }

        $value = '('.implode(',', $buffer).')';
    }

    return $value;
}

/**
 * Converts an object into (field1, field2, ...) form
 * Strings will be enclosed by single quotes
 * Automatically determines fields and field order, therefore please verify each table
 *
 * @param object $obj - The object to output
 * @return bool|string - False if error, a string representation of obj otherwise
 */
function make_value_from_obj_unchecked($obj) {
    $buffer = array();
    foreach ($obj as $key=>$value) {
        if (is_string($value)) {
            $buffer[] = "'$value'";
        }
        else {
            $buffer[] = $value;
        }
    }

    return '('.implode(',', $buffer).')';
}

/**
 * Merges rows from the database with contextid not equal to 1 with those from
 * the backup where contextid equal to 1
 *
 * @param string $table - The table to merge
 * @param array $fields - An array whose keys (string) are the fieldnames to output
 *                       Output will be in the order specified by fields
 *                       Each element is a bool specifying whether the output value is a string
 * @param array $unsafebackupsite - An array of objects from the backup to merge
 *                                 They must have contextid equal to 1
 * @param array $backupfromdb - An associative array mapping from the db's role id t the backup's role id
 * @uses USER
 * @return bool Whether the function was successful
 */
function merge_db_nonsite_backup_site($table, $fields, $unsafebackupsite, $backupfromdb) {
    global $USER;
    if (!is_string($table) || !is_array($fields) || !is_array($unsafebackupsite)) {
        return false;
    }
    $valuesbuffer = array();
    $newids = array();
    // CCLE-164 - aroman
    // This may return more than one record.
    // NOTE: need to use recordset_to_array(...) in order to extract the records
    // from a get_recordset_select call...
    $dbnonsite = get_recordset_select($table, 'contextid <> 1');
    $dbnonsite = recordset_to_array($dbnonsite);

    if ($dbnonsite) {
        if (!is_array($dbnonsite)) {
            //Only one record
            $tmp = $dbnonsite;
            $dbnonsite = array($tmp);
        }
        foreach ($dbnonsite as $saferow) {
            //Remap roleid
            if (isset($saferow->roleid)) {
                $field = $saferow->roleid;
                if (array_key_exists($field, $backupfromdb)) {
                    if ($field != $backupfromdb[$field]) {
                        //!= allows automatic type conversion
                        $saferow->roleid = $backupfromdb[$field];
                    }
                }
            }

            //Save its id
            if (!isset($saferow->id)) {
                return false;
            }
            $newids[$saferow->id] = NULL;

            //Add to list of rows to insert
            $value = make_value_from_obj($saferow, $fields);
            if ($value === false) {
                continue;
            }
            $valuesbuffer[] = $value;
        }
    }

    foreach ($unsafebackupsite as $unsaferow) {
        //Find an unused id
        if (!isset($unsaferow->id)) {
            return false;
        }
        $id = $unsaferow->id;
        while (array_key_exists($id, $newids)) {
            ++$id;
        }
        $unsaferow->id = $id;
        $newids[$id] = NULL;

        //Sanity Check
        if (isset($unsaferow->contextid) && $unsaferow->contextid != 1) {
            //Use !=, not !==
            continue;
        }

        //Change modifier id to the user performing the backup
        if (property_exists($unsaferow, 'modifierid') && is_null($unsaferow->modifierid)) {
            $unsaferow->modifierid = $USER->id;
        }

        //Add to list of rows to insert
        $value = make_value_from_obj(safe_from_unsafe($unsaferow), $fields);
        if ($value === false) {
            continue;
        }
        
        $valuesbuffer[] = $value;
    }
    $success = replace_table($table, implode(', ', $valuesbuffer), false);
    return $success;
}

/**
 * Remap the foreign keys of a table according to a mapping
 *
 * @param string $table - The name of the table, without the prefix
 * @param array $fieldnames - An array of strings specifying the fields to adjust
 * @param array $schema - An array of all fieldnames, in the order stored by database
 * @param array $map - A associative array specifying old -> new values
 * @return bool - Success
 */
function remap_foreign_keys($table, $fields, $backupfromdb) {
    if (!is_string($table) || !is_array($fields) || !is_array($backupfromdb)) {
        return false;
    }

    $recordset = get_records($table);
    if ($recordset) {
        $newrecordset = array();
        foreach ($recordset as $record) {
            foreach ($fields as $fieldname=>$isstring) {
                if (!is_string($fieldname)) {
                    return false;
                }

                $field = $record->$fieldname;
                if (array_key_exists($field, $backupfromdb)) {
                    if ($field != $backupfromdb[$field]) {
                        //!= allows automatic type conversion
                        $record->$fieldname = $backupfromdb[$field];
                    }
                }
            }
            $newrecordset[] = $record;
        }

        $valuesbuffer = array();
        if (!empty($newrecordset)) {
            foreach ($newrecordset as $record) {
                if (!is_null($record)) {
                    //Since these values are coming directly out of the table,
                    //it must be clean
                    $value = make_value_from_obj($record, $fields);
                    if ($value === false) {
                        echo "<p>Warning: Line in unexpected format: $line</p>";
                        continue;
                    }
                    $valuesbuffer[] = $value;
                }
            }
        }
        replace_table($table, implode(', ', $valuesbuffer), false);
    }
    return true;
}

/**
 * Deletes a table and inserts the values specified
 *
 * @param string $table - The table to modify
 * @param string $values - A string of value(s) to insert
 * @param bool $usetransactions - True to use transactions, false to disable
 * @return bool - Whether the transaction was committed
 */
function replace_table($table, $values, $usetransactions = true) {
    if (!is_string($table) || !is_string($values) || !is_bool($usetransactions)) {
        return false;
    }
    
    if ($usetransactions) {
        //Start Transaction
        begin_sql();
    }

    //Perform Transaction
    $success = delete_records($table);
    if ($success === false) {
        if ($usetransactions) {
            rollback_sql();
        }
        echo "<p class=\"mdl-align redfont\">".get_string('congierrordeletingrecord', 'tool_configmanagement')."</p>";
        return false;
    }
    if (!empty($values)) {
        $success = insert_values($table, $values);
        if ($success === false) {
            if ($usetransactions) {
                rollback_sql();
            }
            echo "<p class=\"mdl-align redfont\">".get_string('configerrorinsertingrecord', 'tool_configmanagement')."</p>";
            return false;
        }
    }

    if ($usetransactions) {
        //Finish transaction
        commit_sql();
    }
    return true; 
    
}

/**
 * Makes each field of an object safe for MySQL
 *
 * @param object $obj- The object to make safe
 * @return object - The safe version of the object
 */
function safe_from_unsafe($obj) {
    foreach ($obj as &$field) {
        $field = mysql_real_escape_string($field);
    }
    return $obj;
}

/**
 * Replaces the config table with the one from the backup
 * Precondition: Just read ===Config===
 * Postcondition: Point just before next ===
 * 
 * @uses divider dividerlen fp
 * @return none
 */
function update_config() {
    global $divider;
    global $dividerlen;
    global $fp;

    $fields = array('id'=>false, 'name'=>true, 'value'=>true);
    $valuesbuffer = array();
    while (!feof($fp)) {
        $line = fgets($fp);
        if (substr($line, 0, $dividerlen) == $divider) {
            fseek($fp, -strlen($line), SEEK_CUR);
            break;
        }

        $backuprow = safe_from_unsafe(json_decode($line));
        if (is_null($backuprow)) {
            //Empty line
            continue;
        }
        // Use strpos() for faster & less memory intensive performance.
        if($rec = get_record('config','name',$backuprow->name)) {
            if(strpos($backuprow->name, 'version') !== false || strpos($backuprow->name, 'cache') !== false || strpos($backuprow->name, 'version') !== false
            || strpos($backuprow->name, 'statsrolesupgraded') !== false || strpos($backuprow->name, 'auth') !== false || strpos($backuprow->name, 'proxy') !== false
            || strpos($backuprow->name, 'login') !== false || strpos($backuprow->name, 'siteidentifier') !== false || strpos($backuprow->name, 'geoipfile') !== false
            || strpos($backuprow->name, 'mnet') !== false ) {
                $backuprow->value = $rec->value;
            }
        }

        $value = make_value_from_obj($backuprow, $fields);
        if ($value === false) {
            echo "<p>Warning: Line in unexpected format: $line</p>";
            continue;
        }
        $valuesbuffer[] = $value;
    }

    if(!empty($valuesbuffer))
        replace_table('config', implode(', ', $valuesbuffer));
    else
        echo "<p class=\"mdl-align redfont\">There are no Config values to write.</p>\n";

    //Only to fix tables
    //change_field_offset('config', 'id', 1);
}

/**
 * Replaces the config_plugins table with the one from the backup
 * Precondition: Just read ===Plugins===
 * Postcondition: Point just before next ===
 *
 * @uses divider dividerlen fp
 * @return none 
 */
function update_config_plugins() {
    global $divider;
    global $dividerlen;
    global $fp;

    $fields = array('id'=> false, 'plugin'=>true, 'name'=>true, 'value'=>true);
    $valuesbuffer = array();

    while (!feof($fp)) {
        $line = fgets($fp);
        if (substr($line, 0, $dividerlen) == $divider) {
            fseek($fp, -strlen($line), SEEK_CUR);
            break;
        }

        $backuprow = safe_from_unsafe(json_decode($line));
        if (is_null($backuprow)) {
            //Empty line
            continue;
        }

        $value = make_value_from_obj($backuprow, $fields);
        if ($value === false) {
            echo "<p>Warning: Line in unexpected format: $line</p>";
            continue;
        }
        $valuesbuffer[] = $value;
    }

    if(!empty($valuesbuffer))
        replace_table('config_plugins', implode(', ', $valuesbuffer));
    else
        echo "<p class=\"mdl-align redfont\">There are no Config Plugins values to write.</p>\n";

    //Only to fix tables
    //change_field_offset('config_plugins', 'id', 1);
}


/**
 * Updates the 7 role tables, role, role_allow_assign, role_allow_override,
 * role_assignments, role_capabilities, role_names, and role_sortorder 
 * Precondition: Just read  ===Roles===
 * Postcondition: Will point to right before ===Blocks===
 *
 * @uses CFG, divider, dividerlen, fp
 * @return bool - Whether the function succeeded
 */
function update_role_tables() {
    //Initialization
    global $CFG;
    global $divider;
    global $dividerlen;
    global $fp;

    $rowfromid = get_records_as_assoc_array('role');
    if ($rowfromid === false) {
        return false;
    }
    if (!empty($rowfromid) && (!isset(current($rowfromid)->id) || !isset(current($rowfromid)->name))) {
        return false;
    }

    //A map of the database's role table, mapping name->id
    $idfromname = array();
    foreach ($rowfromid as $row) {
        $idfromname[$row->name] = $row->id;
    }

    //IMPORTANT:
    //  If the Role Table is modified, then all 7 tables must be modified!
    begin_sql();    //Start a transaction
    
    //Read the backup file
    $backupfromdb = array();
    $dbfrombackup = array();

    $fields = array('id'=>false, 'name'=>true, 'shortname'=>true, 'description'=>true, 'sortorder'=>false);
    $valuesbuffer = array();

    // aroman - delete the records from the role table first
    $success = delete_records( 'role' );

    $line = fgets($fp);
    while (substr($line, 0, $dividerlen) !== $divider) {
        //Role- Replace with backup
        $unsafebackuprow = json_decode($line);

        //Save the primary key mapping, if it exists
        if (array_key_exists($unsafebackuprow->name, $idfromname)) {
            $backupfromdb[$idfromname[$unsafebackuprow->name]] = $unsafebackuprow->id;
            $dbfrombackup[$unsafebackuprow->id] = $idfromname[$unsafebackuprow->name];
        }
        else {
            $dbfrombackup[$unsafebackuprow->id] = NULL;
        }

        //Insert the line into the database, forcing id to be the backup's
        $backuprow = safe_from_unsafe($unsafebackuprow);
        $value = make_value_from_obj($backuprow, $fields);
        if ($value === false) {
            echo "<p>Warning: Line in unexpected format: $line</p>";
            continue;
        }
        $valuesbuffer[] = $value;

        //Next Line
        $line = fgets($fp);
    }
    $success = replace_table('role', implode(', ', $valuesbuffer), false);
    if (!$success) {
        echo "<p>".get_string('configrollbackerrormsg', 'tool_configmanagement')." role</p>";
        rollback_sql();
        return false;
    }
    

    assert (strpos($line, $divider.'Role_Allow_Assign'.$divider) !== false);
    $valuesbuffer = array();
    $line = fgets($fp);
    while (substr($line, 0, $dividerlen) !== $divider) {
        //Role_Allow_Assign - Replace with backup
        $unsafebackuprow = json_decode($line);

        //Check Foreign Key constraints
        if (!array_key_exists($unsafebackuprow->roleid, $dbfrombackup)) {
            echo "Warning: id=$unsafebackuprow->id - role_allow_assign:roleid=$unsafebackuprow->roleid not found in Role table.<br />\n";
        }
        if (!array_key_exists($unsafebackuprow->allowassign, $dbfrombackup)) {
            echo "Warning: id=$unsafebackuprow->id - role_allow_assign:allowassign=$unsafebackuprow->allowassign not found in Role table.<br />\n";
        }

        //Insert the line into the database
        $backuprow = safe_from_unsafe($unsafebackuprow);
        $fields = array('id'=>false, 'roleid'=>true, 'allowassign'=>true);
        $value = make_value_from_obj($backuprow, $fields);
        if ($value === false) {
            echo "<p>Warning: Line in unexpected format: $line</p>";
            continue;
        }
        $valuesbuffer[] = $value;

        $line = fgets($fp);
    }
    $success = replace_table('role_allow_assign', implode(', ', $valuesbuffer), false);
    if (!$success) {
        echo "<p>".get_string('configrollbackerrormsg', 'tool_configmanagement')." role_allow_assign</p>";
        rollback_sql();
        return false;
    }

    assert (strpos($line, $divider.'Role_Allow_Override'.$divider) !== false);
    $valuesbuffer = array();
    $line = fgets($fp);
    while (substr($line, 0, $dividerlen) !== $divider) {
        //Role_Allow_Override - Replace with backup
        $unsafebackuprow = json_decode($line);

        //Check Foreign Key constraints
        if (!array_key_exists($unsafebackuprow->roleid, $dbfrombackup)) {
            echo "Warning: id=$unsafebackuprow->id - role_allow_override:roleid=$unsafebackuprow->roleid not found in Role table.<br />\n";
        }
        if (!array_key_exists($unsafebackuprow->allowoverride, $dbfrombackup)) {
            echo "Warning: id=$unsafebackuprow->id - role_allow_override:allowassign=$unsafebackuprow->allowoverride not found in Role table.<br />\n";
        }

        //Insert the line into the database
        $backuprow = safe_from_unsafe($unsafebackuprow);
        $fields = array('id'=>false, 'roleid'=>true, 'allowoverride'=>true);
        $value = make_value_from_obj($backuprow, $fields);
        if ($value === false) {
            echo "<p>Warning: Line in unexpected format: $line</p>";
            continue;
        }
        $valuesbuffer[] = $value;

        $line = fgets($fp);
    }
    $success = replace_table('role_allow_override', implode(', ', $valuesbuffer), false);
    if (!$success) {
        echo "<p>".get_string('configrollbackerrormsg', 'tool_configmanagement')." role_allow_override</p>";
        rollback_sql();
        return false;
    }

    assert (strpos($line, $divider.'Role_Assignments'.$divider) !== false);
    $line = fgets($fp);
    while (substr($line, 0, $dividerlen) !== $divider) {
        //Role Assignments- Just skip lines, fix the table only
        $line = fgets($fp);
    }
    $fields = array('id'=>false, 'roleid'=>false, 'contextid'=>false, 'userid'=>false,
                    'hidden'=>false, 'timestart'=>false, 'timeend'=>false, 'timemodified'=>false,  
                    'modifierid'=>false, 'enrol'=>true, 'sortorder'=>false);
    $success = remap_foreign_keys('role_assignments', $fields, $backupfromdb);
    if (!$success) {
        echo get_string('configrollbackerrormsg', 'tool_configmanagement')." role_assignments.<br />\n";
        rollback_sql();
        return false;
    }

    assert (strpos($line, $divider.'Role_Capabilities'.$divider) !== false);
    $unsafebackupsitecaps = array();
    $line = fgets($fp);
    while (substr($line, 0, $dividerlen) !== $divider) {
        //Role_Capabilities- If contextid=1 use backup only, else use DB only 
        $unsafebackuprow = json_decode($line);
        if (isset($unsafebackuprow->contextid) && $unsafebackuprow->contextid == 1) {
            //Use ==, not ===
            $unsafebackupsitecaps[] = $unsafebackuprow;
        }
        $line = fgets($fp);
    }

    $fields = array('id'=>false, 'contextid'=>false, 'roleid'=>false, 'capability'=>true,
                    'permission'=>false, 'timemodified'=>false, 'modifierid'=>true);
    $success = merge_db_nonsite_backup_site('role_capabilities', $fields, $unsafebackupsitecaps, $backupfromdb);
    if (!$success) {
        echo get_string('configrollbackerrormsg', 'tool_configmanagement')." role_capabilities<br />\n";
        rollback_sql();
        return false;
    }

    assert (strpos($line, $divider.'Role_Names'.$divider) !== false);
    $unsafebackupsitenames = array();
    $line = fgets($fp);
    while (substr($line, 0, $dividerlen) !== $divider) {
        //Role_Names- If contextid=1 use backup only, else use DB only 
        $unsafebackuprow = json_decode($line);
        if (isset($unsafebackuprow->contextid) && $unsafebackuprow->contextid === 1) {
            $unsafebackupsitenames[] = $unsafebackuprow;
        }
        $line = fgets($fp);
    }
    $fields = array('id'=>false, 'roleid'=>false, 'contextid'=>false, 'name'=>true);
    $success = merge_db_nonsite_backup_site('role_names', $fields, $unsafebackupsitenames, $backupfromdb);
    if (!$success) {
        echo get_string('configrollbackerrormsg', 'tool_configmanagement')." role_names<br />\n";
        rollback_sql();
        return false;
    }

    assert (strpos($line, $divider.'Role_SortOrder'.$divider) !== false);
    $line = fgets($fp);
    while (substr($line, 0, $dividerlen) !== $divider) {
        //Role SortOrder- Just skip lines, fix the table only
        $line = fgets($fp);
    }
    $fields = array('id'=>false, 'userid'=>false, 'roleid'=>false, 'contextid'=>false, 'sortorder'=>false);
    $success = remap_foreign_keys('role_sortorder', $fields, $backupfromdb);
    if (!$success) {
        echo get_string('configrollbackerrormsg', 'tool_configmanagement')." role_sortorder<br />\n";
        rollback_sql();
        return false;
    }

    //Commit the Transaction
    commit_sql();

    //Move file pointer back one line- Test it
    fseek($fp, -strlen($line), SEEK_CUR);
    $line = fgets($fp);
    if (!assert(strpos($line, $divider.'Blocks'.$divider) !== false)) {
        die;
    }

    //Move file pointer back one line- Do it for real
    fseek($fp, -strlen($line), SEEK_CUR);

    return true;
}

/**
 * Only insert special case logins from backup to database if they don't already exist
 *
 * @uses divider, dividerlen, fp
 * @return bool - Returns false if error occurred
 */
function update_special_case_logins() {
    global $divider;
    global $dividerlen;
    global $fp;

    $useridsarray = array();

    $valuesbuffer = array();
/*
    $rowfromusername = create_table_index('user', 'username');
    $rowfromid = create_table_index('user', 'id');

    if ($rowfromusername === false || $rowfromid === false) {
        return false;
    }
*/
    $mnetid = get_record('config','name','mnet_localhost_id');
    $mnetid = $mnetid->value;

    $fields = array('id'=>false, 'auth'=>true, 'confirmed'=>false, 'policyagreed'=>false, 'deleted'=>false, 'mnethostid'=>false,
        'username'=>true, 'password'=>true, 'idnumber'=>true, 'firstname'=>true, 'lastname'=>true, 'email'=>true,
        'emailstop'=>false, 'icq'=>true, 'skype'=>true, 'yahoo'=>true, 'aim'=>true, 'msn'=>true,
        'phone1'=>true, 'phone2'=>true, 'institution'=>true, 'department'=>true, 'address'=>true, 'city'=>true,
        'country'=>true, 'lang'=>true, 'theme'=>true, 'timezone'=>true, 'firstaccess'=>false, 'lastaccess'=>false,
        'lastlogin'=>false, 'currentlogin'=>false, 'lastip'=>true, 'secret'=>true, 'picture'=>true, 'url'=>true,
        'description'=>true, 'mailformat'=>false, 'maildigest'=>false, 'maildisplay'=>false, 'maildisplay'=>false, 'htmleditor'=>false,
        'ajax'=>false, 'autosubscribe'=>false, 'trackforums'=>false, 'timemodified'=>false, 'trustbitmask'=>false, 'imagealt'=>true,
        'screenreader'=>false );

    while (!feof($fp)) {
        $line = fgets($fp);
        if (substr($line, 0, $dividerlen) == $divider) {
            fseek($fp, -strlen($line), SEEK_CUR);
            break;
        }

        // this is a quick fix for some broken records from PROD
        if(strpos($line, ';')!==false)
            $line = preg_replace('/;/','',$line);

        $unsafebackuprow = json_decode($line);
        if (is_null($unsafebackuprow)) {
            //Empty line
            continue;
        }

        // Keep the same passwords for admin and guest
        if($unsafebackuprow->username == 'tool_configmanagement') {
            $rec = get_record('user','username','tool_configmanagement');
            $unsafebackuprow->password = $rec->password;
            $unsafebackuprow->email = $rec->email;
        } else if($unsafebackuprow->username == 'tool_configmanagement') {
            $rec = get_record('user','username','guest');
            $unsafebackuprow->password = $rec->password;
        }
        // Set the mnet_host ID to current install ID so that new users can log in without problems
        $unsafebackuprow->mnethostid = $mnetid;
        $useridsarray[] = $unsafebackuprow->id;

        $backuprow = safe_from_unsafe($unsafebackuprow);
        $value = make_value_from_obj($backuprow, $fields);
        if ($value === false) {
            echo "<p>Warning: Line in unexpected format: $line</p>";
            continue;
        }
        $valuesbuffer[] = $value;
    }

    if (!empty($valuesbuffer)) {
        if(replace_table('user', implode(', ', $valuesbuffer))) {
            echo "<p class=\"mdl-align\">".get_string('configusertablewrittenmsg', 'tool_configmanagement')."</p>";
            if(write_user_role_assignments($useridsarray)) {
                echo "<p class=\"mdl-align\">".get_string('configroleassignmenttablemsg', 'tool_configmanagement')."</p>";
            }
        }
    }

    return true;
}

/**
 * Replaces the role_assignments table.  It will only write the records that contain
 * a valid userid from the replaced Users table.
 *
 * @uses $fp, $dividerlen, $divider
 * @param array $useridsarray An array of int that contains valid user IDs
 * @return bool on success or fail
 */
function write_user_role_assignments(&$useridsarray) {
    global $fp;
    global $dividerlen;
    global $divider;

    $success = true;

    rewind($fp);

    $valuesbuffer = array();

    // Find the role_assignments in the file.
    $line = fgets($fp);
    while(substr($line, 0, strlen('===Role_Assignments===')) != '===Role_Assignments==='){
        $line = fgets($fp);
    }
    
    // Once we're at Role Assignments, start going through lines
    $fields = array('id'=>false, 'roleid'=>false, 'contextid'=>false, 'userid'=>false,
                    'hidden'=>false, 'timestart'=>false, 'timeend'=>false, 'timemodified'=>false,
                    'modifierid'=>false, 'enrol'=>true, 'sortorder'=>false);

    $line = fgets($fp);

    while (substr($line, 0, $dividerlen) !== $divider) {
        $unsafebackuprow = json_decode($line);
        $line = fgets($fp);

        // Check (for older config files) that we're only getting roles from IDs in user table
        if (is_null($unsafebackuprow) || !in_array($unsafebackuprow->userid, $useridsarray)) {
            //Empty line
            continue;
        }

        $backuprow = safe_from_unsafe($unsafebackuprow);

        $value = make_value_from_obj($backuprow, $fields);
        if ($value === false) {
            echo "<p>Warning: Line in unexpected format: $line</p>";
            continue;
        }
        $valuesbuffer[] = $value;
    } 

    // We'll only have roles from users that were imported
    if(!empty($valuesbuffer)) {
        $success = replace_table('role_assignments', implode(', ', $valuesbuffer), false);
    }
    // Go to the end of the file, so we can finish the process...
    fseek($fp, 0, SEEK_END);
    
    return $success;
}

/**
 * Writes values from config.php in JSON format
 * Reads config.php and picks up values from $CFG using regex
 *
 * @uses $fp
 * @return void
 */
function write_configphp($fp) {

    $configfile = dirname(dirname(dirname(dirname(__FILE__))))."/config.php";
    $fpconfig = fopen($configfile, 'r');

    if(!$fpconfig) {
        echo get_string('configerrorcannotopenfile', 'tool_configmanagement')."<br/>";
        return;
    }

    $search = array(' ', '=', '-', '>','.');
    $search2 = array('\'','"');

    $line = fgets($fpconfig);
    while(!feof($fpconfig)) {
        if( preg_match('/->.*=/',$line, $var) && 
                preg_match("/'.*'|TRUE|true|FALSE|false|[0-9]|\".*\"|array.*\)/", $line, $value)) {
            $var = str_replace($search, '', $var[0]);
            $value = str_replace($search2,'',$value[0]);

            // create object to write in json format
            $newobj = new stdClass();
            $newobj->$var = $value;
            fwrite($fp, json_encode($newobj)."\n");
        }

        $line = fgets($fpconfig);
    }
}

/**
 * Comparison function that will help sort by capabilities
 *
 * @param Object $a
 * @param Object $b
 * @return int 
 */
function rolecap_cmp($a, $b) {
    if($a->capability == $b->capability){return 0;}
    return ($a->capability < $b->capability) ? -1 : 1;
}
