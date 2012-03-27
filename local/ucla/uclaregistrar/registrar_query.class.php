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

    const query_results = 'good';
    const failed_inputs = 'bad';
    const failed_outputs = 'badoutputs';

    // This is a member field that allows unindexed arguments passed in
    // for generation of stored procedure statements to be properly used
    // by this master class, primarily in get_key().
    // It is very preferable to not use this variable, and to index
    // your driving data for the query with more meaningful values.
    var $unindexed_key_translate = null;

    // These are the calls that caused bad inputs
    var $previous_bad_inputs = array();

    // THese are the bad outputs
    var $bad_outputs = array();

    var $notrim = false;

    /**
     *  @return Array (
     *      'good' => array( Good data ),
     *      'bad'  => array( Bad data (might be empty ))
     *  )
     **/
    static function run_registrar_query($queryname, $data, $ignorebad=false) {
        $rq = self::get_registrar_query($queryname);

        if (!$rq) {
            return false;
        }

        $rt = $rq->retrieve_registrar_info($data);

        if ($ignorebad) {
            return $rt;
        }
        
        $er = $rq->get_bad_data();
        $bo = $rq->get_bad_outputs();

        return array(
            self::query_results => $rt, 
            self::failed_inputs => $er,
            self::failed_outputs => $bo
        );
    }

    /**
     *  Finds the file for the query and creates the query connection
     *  object.
     **/
    static function get_registrar_query($queryname) {
        $classname = 'registrar_' . $queryname;
        
        if (!class_exists($classname)) {
            $fn = dirname(__FILE__) . "/$classname.class.php";
            if (file_exists($fn)) {
                require_once($fn);
            } else {
                throw new registrar_stored_procedure_exception(
                    $classname . ' not found'
                );
            }
        }

        if (class_exists($classname)) {
            return new $classname();
        }

        return false;
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
            $this->registrar_conn =& $this->open_registrar_connection();
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
     *  @param $driving_data The data to run a set of queries on.
     *  @return Array( Array( ) )
     **/
    function retrieve_registrar_info($driving_data) {
        // Empty the bad data
        $this->previous_bad_inputs = array();

        $direct_data = array();

        $db_reg =& $this->get_registrar_connection();

        foreach ($driving_data as $driving_datum) {
            $qr = $this->remote_call_generate($driving_datum);

            // Let's not fail hard
            if ($qr === false) {
                $this->previous_bad_inputs[] = $driving_datum;
                continue;
            }

            $qr = self::db_encode($qr);

            $recset = $db_reg->Execute($qr);

            if (!$recset->EOF) {
                while ($fields = $recset->FetchRow()) {
                    if ($this->validate($fields, $driving_datum)) {
                        $res = $this->clean_row($fields);

                        $key = $this->get_key($res, $driving_datum);
                        if ($key == null) {
                            $direct_data[] = $res;
                        } else {
                            $direct_data[$key] = $res;
                        }
                    } else {
                        // We need to return the malevolent data...
                        $this->bad_outputs[count($this->previous_bad_inputs)] 
                            = $fields;
                        $this->previous_bad_inputs[] = $driving_datum;
                    }
                }
            } else {
                // We need to return the malevolent data...
                $this->previous_bad_inputs[] = $driving_datum;
            }
        }

        return $direct_data;
    }

    /**
     *  Returns any bad data whose output did not pass validation.
     **/
    function get_bad_data() {
        return $this->previous_bad_inputs;
    }

    function get_bad_outputs() {
        return $this->bad_outputs;
    }

    /**
     *  Returns an index to use for the return data.
     *  
     *  @param $fields Array The data to be indexed.
     *  @param $oldfields Array The data that was sent in.
     *  @return string The key to use for the index.
     **/
    function get_key($fields, $oldfields) {
        if (is_object($fields)) {
            $fields = get_object_vars($fields);
        }

        $termfield = false;
        if (!isset($fields['term'])) {
            if (isset($oldfields['term'])) {
                $termfield = 'term';
            } else if (isset($this->unindexed_key_translate['term'])
                    && isset($oldfields[
                        $this->unindexed_key_translate['term']
                    ])) {
                $termfield = $this->unindexed_key_translate['term'];
            } 

            $fields['term'] = $oldfields[$termfield];
        }

        $isc = isset($fields['srs']);

        if (isset($fields['term']) && $isc) {
            return make_idnumber($fields);
        }

        if ($isc) {
            return $fields['srs'];
        }

        return null;
    }

    /**
     *  Trims all fields and makes the case of the keys to lower case.
     **/
    function clean_row($fields) {
        $new = array_change_key_case($fields, CASE_LOWER);

        foreach ($new as $k => $v) {
            $new[$k] = trim($v);
        }

        $new = self::db_decode($new);

        return $new;
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
     *  @return boolean
     *      If this returns false, the new entry will not be returned.
     **/
    abstract function validate($new, $old);

    /**
     *  This is the function used to generate the stored procedure.
     *
     *  @see retrieve_registrar_info()
     *
     *  @param $args Array The arguments to be used in generating the 
     *      remote query.
     *      It is prefereable to have them indexed meaningfully:
     *          i.e. 'term', 'subjarea', 'srs'
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

        // This will allow us to share connections hurrah
        $i = 'ucla_extdb_registrar_connection';
        $adodbclass = 'ADONewConnection';

        if (isset($CFG->$i)) {
            return $CFG->$i;
        }

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
        $extdb = $adodbclass($dbtype);

        if (!$extdb) {
            throw new registrar_stored_procedure_exception(
                'Could not connect to registrar!'
            );
        }

        // Uncomment to debug registrar SQL statements
        //if ($CFG->debug > 0) {
        //    $extdb->debug = true;
        //}

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

        $CFG->$i =& $extdb;

        return $extdb;
    }

    const DEFAULT_ENCODING = 'utf-8';

    /**
     *  Go from the utf-8 to the remote db's encoding.
     **/
    static function db_encode($text) {
        $dbenc = self::db_coding_check();
        if (!$dbenc) {
            return $text;
        }

        if (is_array($text)) {
            foreach ($text as $k => $value) {
                $text[$k] = self::db_encode($value);
            }
        } else {
            $text = textlib::convert($text, self::DEFAULT_ENCODING, $dbenc);
        } 

        return $text;
    }

    /**
     *  Come from the remote db's encoding into utf-8.
     **/
    static function db_decode($text) {
        $dbenc = self::db_coding_check();
        if (!$dbenc) {
            return $text;
        }
        
        if (is_array($text)) {
            foreach ($text as $k => $value) {
                $text[$k] = self::db_decode($value);
            }
        } else {
            $text = textlib::convert($text, $dbenc, self::DEFAULT_ENCODING);
        } 

        return $text;
    }

    /**
     *  Checks if we need to do the en/decoding.
     **/
    static function db_coding_check() {
        $dbenc = get_config('', 'registrar_dbencoding');
        if ($dbenc == self::DEFAULT_ENCODING) {
            return false;
        }

        return $dbenc;
    }
}

class registrar_stored_procedure_exception extends moodle_exception {
    // Nothing...
}
