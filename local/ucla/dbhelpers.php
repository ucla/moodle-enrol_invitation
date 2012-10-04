<?php

/**
 *  Class of static functions that help with commonly used mass-database
 *  functionality.
 *  Please don't explicitly include this file, include the local/ucla/lib.php
 *  instead, and then call the function ucla_require_db_helper()
 **/
class db_helper {
    /** 
     *  Will check a table for entries, insert and update entries provided
     *  in the arguments.
     *  @param  $table      The table to work with
     *  @param  $tabledata  
     *      Array( Array(), ... ) to sync the table with. Should have
     *      indices specified in $syncfields.
     *  @param  $syncfields
     *      Array() of fields from table and tabledata to compare old 
     *      and new entries with.
     *  @param  $partialwhere
     *      The where statement in a get_records_select() to synchronize
     *      a smaller part of a table.
     *  @param  $partialparmas
     *      The parameters for a get_records_select() to synchronize
     *      a smaller part of a table.
     *  @return
     *      Array( 
     *          0 => Array(inserted entries ids), 
     *          1 => Array(updated entries ids), 
     *          2 => Array(deleted entries ids) (unless section truncate)
     *      )
     **/
    static function partial_sync_table($table, $tabledata, $syncfields,
            $partialwhere=null, $partialparams=null, $allowfulldelete=false) {
        global $DB;

        $partial = ($partialwhere || $partialparams);

        // Optimization for delete all
        if (empty($tabledata)) {
            $r = 0;

            // These count records could be alleviated if the DB layer
            // used affected_rows of mysqli
            if ($partial) {
                $c = $DB->count_records_select($table,
                    $partialwhere, $partialparams);

                $r = $DB->delete_records_select($table, 
                    $partialwhere, $partialparams);
            } else if ($allowfulldelete) {
                $c = $DB->count_records($table);

                // This means a full delete...
                $r = $DB->delete_records($table);
            } else {
                debugging('full-delete not allowed');
            }

            if ($c > 0) {
                $countarr = array_fill(0, $c, '');
            } else {
                $countarr = array();
            }

            return array(array(), array(), $countarr);
        }

        // Get existing records to determine if we're going to insert or
        // going to update
        $fields = 'id, ' . implode(', ', $syncfields);

        if ($partial) {
            $existingrecords = $DB->get_recordset_select($table, 
                $partialwhere, $partialparams);
        } else {
            $existingrecords = $DB->get_recordset($table);
        }

        // Array of primary keys for the table
        $existing_indexed = array();
        foreach ($existingrecords as $record) {
            $existing_indexed[self::dynamic_hash($record, $syncfields)]
                = $record->id;
        }

        $inserted = array();
        $updated = array();

        // Decide to update if PK exists
        foreach ($tabledata as $data) {
            $hash = self::dynamic_hash($data, $syncfields);

            if (isset($existing_indexed[$hash])) {
                $data['id'] = $existing_indexed[$hash];

                $DB->update_record($table, $data);

                $updated[$hash] = $data['id'];
            } else {
                $id = $DB->insert_record($table, $data);
                $data['id'] = $id;

                $inserted[$hash] = $id;

                $existing_indexed[$hash] = $id;
            }
        }

        // We're going to generate a set of ids of records we're going
        // to obliterate
        if (empty($existing_indexed)) {
            return;
        }

        $delete_ids = array();
        $deleted = array();
        foreach ($existing_indexed as $hash => $existing) {
            if (isset($inserted[$hash]) || isset($updated[$hash])) {
                continue;
            }

            $delete_ids[] = $existing;

            // Loss of reporting data
            $deleted[] = $existing;
        }

        if (!empty($delete_ids)) {
            list($sqlin, $params) = $DB->get_in_or_equal($delete_ids);
            $where = 'id ' . $sqlin;

            $DB->delete_records_select($table, $where, $params);
        }

        return array($inserted, $updated, $deleted);
    }

    /**
     *  Automatically generates a non-optimized hash.
     *  @param  $data   The object/Array to hash. Needs to have
     *      fields provided in $hashfields.
     *  @param  $hashfields The fields to use as a hash.
     *  @return string That should uniquely identify the data.
     **/
    static function dynamic_hash($data, $hashfields) {
        $prehash = array();
        if (is_object($data)) {
            $datarr = get_object_vars($data);
        } else {
            $datarr = $data;
        }

        foreach ($hashfields as $field) {
            if (isset($datarr[$field])) {
                $prehash[$field] = $datarr[$field];
            } else {
                $prehash[$field] = null;
            }
        }

        return serialize($prehash);
    }
}
