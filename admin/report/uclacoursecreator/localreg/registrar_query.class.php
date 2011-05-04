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
 *  Registrar Connectivity class.
 *
 *  Essentially a wrapper for ODBC.
 **/
abstract class registrar_query {
    // Holds onto the Registrar connection object.
    private $registrar_conn = null;

    function __construct() {
        // Test registrar connections
        $this->get_registrar_connection();
        $this->close_registrar_connection();
    }

    /**
     *  Returns the ADOConnection object for registrar connection.
     *
     *  Wrapper for @see open_registrar_connection().
     *  
     *  May change state of object.
     *
     *  @return ADOConnection The connection to the registrar.
     **/
    function get_registrar_connection() {
        if ($this->registrar_conn == null) {
            $this->registrar_conn = $this->open_registrar_connection();
        }

        return $this->registrar_conn;
    }
    
    /**
     *  Closes the ADOConnection object for Registrar connection.
     *
     *  May change the state of object.
     **/
    function close_registrar_connection() {
        if ($this->registrar_conn == null) {
            return false;
        }

        $this->registrar_conn->Close();

        $this->registrar_conn = null;

        return true;
    }

    /**
     *  This function will utilize the ODBC connection and retrieve data.
     *
     *  @param $stored_proc string The stored procedure to use.
     **/
    function retrieve_registrar_info($driving_data) {
        $direct_data = array();

        $db_reg = $this->get_registrar_connection();

        foreach ($driving_data as $driving_datum) {
            $qr = $this->remote_call_generate($driving_datum);

            $recset = $db_reg->Execute($qr);

            if (!$recset->EOF) {
                while ($fields = $recset->FetchRow()) {
                    $res = $this->validate($fields, $driving_datum);

                    if ($res !== false) {
                        $key = $this->get_key($fields);
                        $direct_data[$key] = $res;
                    }
                }
            } else {
                throw new registrar_stored_procedure_exception(
                    "$qr returned 0 rows."
                );
            }
        }

        $this->close_registrar_connection();

        return $direct_data;
    }

    /**
     *  Returns an index to use for the return data.
     *  
     *  @param $fields Array The data to be indexed.
     *  @return string The key to use for the index.
     **/
    function get_key($fields) {
        if (isset($fields['srs'])) {
            return $fields['srs'];
        }

        return 'null';
    }

    /**
     *  This function will be run on every returned Registrar entry.
     *  If this function returns false, the entry from the Registrar will
     *  not be returned.
     *
     *  @see retrieve_registrar_info()
     *
     *  @param $new Array The row from the Registrar.
     *  @param $old Array The row from the driving data.
     *  @return boolean|Array
     *      If this returns false, the new entry will not be returned.
     *      Otherwise, it will format the entry in some way.
     **/
    abstract function validate($new, $old);

    /**
     *  This is the function used to generate the stored procedure.
     *
     *  @see retrieve_registrar_info()
     *
     *  @param $args Array The arguments to be used in generating the 
     *      remote query.
     **/
    abstract function remote_call_generate($args);
    
    /**
     *  Create a Registrar connection object.
     *
     *  Stolen from enrol/database/lib.php:enrol_database_plugin.init_db()
     *  @return ADOConnection 
     */
    function open_registrar_connection() {
        global $CFG;

        require_once($CFG->libdir . '/adodb/adodb.inc.php');

        $dbtype = get_config('', 'registrar_dbtype');
        if ($dbtype == '') {
            throw new registrar_stored_procedure_exception(
                'Registrar DB not set!'
            );
        }

        // Manually coded check for odbc functionality, since moodle doesn't 
        // seem to like exceptions
        if (strpos($dbtype, 'odbc') !== false) {
            if (!function_exists('odbc_exec')) {
                $this->handle_locking(false);

                throw new Exception('FATAL ERROR: ODBC not installed!');
            }
        }

        // Connect to the external database 
        $extdb = ADONewConnection($dbtype);

        if (!$extdb) {
            throw new registrar_stored_procedure_exception(
                'Could not connect to registrar!'
            );
        }

        if ($CFG->debug > 0) {
            $extdb->debug = true;
        }

        // If the stored procedures are not working, uncomment this line
        //$extdb->curmode = SQL_CUR_USE_ODBC;

        $status = $extdb->Connect(
            get_config('', 'registrar_dbhost'), 
            get_config('', 'registrar_dbuser'), 
            get_config('', 'registrar_dbpass'),
            get_config('', 'registrar_dbname')
        );

        if ($status == false) {
            throw new registrar_stored_procedure_exception(
                'Registrar connection failed!'
            );
        }

        $extdb->SetFetchMode(ADODB_FETCH_ASSOC);

        return $extdb;
    }
}

class registrar_stored_procedure_exception extends moodle_exception {
    // Nothing...
}
