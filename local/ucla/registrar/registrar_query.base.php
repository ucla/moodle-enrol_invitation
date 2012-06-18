<?php

defined('MOODLE_INTERNAL') || die;

/**
 *  Registrar Connectivity class.
 *
 *  Essentially a wrapper for a wrapper for ODBC.
 **/
abstract class registrar_query {
    // Holds onto the Registrar connection object.
    private $registrar_conn = null;

    // Flags used to indicate keys of return value when
    // you do not want to ignore invalid returns
    const query_results = 'good';
    // I have an internal struggle of removing "generally unused"
    // data at the this level.
    // Pros: Don't have to it in many places. 
    // Cons: Any time that the "generally unused" data becomes used,
    //      changes expected behavior for all existing tools using this
    //      stored procedure.
    const failed_outputs = 'bad';

    // These are the bad outputs, or outputs that made
    // $this->validate() return false
    var $bad_outputs = array();

    // Used to determine if trimming of stuff is needed.
    // Array( $field, ... )
    var $notrim = false;

    /**
     *  @param  $queryname - The name of the stored procedure.
     *  @param  $data   - The data to pass into stored procedure.
     *  @param  $ignorebad - Changes the return values.
     *  @return Array ( Array( results ) )
     *  @return Array (
     *      'good' => array( Good data ),
     *      'bad'  => array( Bad data,  might be empty )
     *  ) if $ignorebad == false
     *  @return false if the input does not validate
     *  @throws registrar_stored_procedure_exception if no stored procedure
     *      wrapper class is found
     **/
    static function run_registrar_query($queryname, $data, $ignorebad=true) {
        $rq = self::get_registrar_query(strtolower($queryname));
        if (!$rq) {
            return false;
        }

        $rt = $rq->retrieve_registrar_info($data);

        if ($ignorebad) {
            return $rt;
        }

        return array(
            self::query_results => $rt, 
            self::failed_outputs => $rq->get_bad_outputs()
        );
    }
    
    /**
     *  This function will utilize the ODBC connection and retrieve data.
     *
     *  @param $driving_data The data to run a set of queries on.
     *  @return Array( Array( ) )
     *      false - indicates bad input
     *      empty array() - indicates good input, but no results
     **/
    function retrieve_registrar_info($driving_data) {
        $direct_data = array();

        try {
            $db_reg =& $this->get_registrar_connection();
        } catch (registrar_stored_procedure_exception $e) {
            error_log($e->getMessage());
            return false;
        }

        $qr = $this->remote_call_generate($driving_data);

        // Let's not fail hard
        if ($qr === false) {
            debugging('failed to generate query');
            return false;
        }

        $qr = self::db_encode($qr);

        $recset = $db_reg->Execute($qr);

        if (!$recset->EOF) {
            while ($fields = $recset->FetchRow()) {
                if ($this->validate($fields, $driving_data)) {
                    $res = $this->clean_row($fields);

                    $key = $this->get_key($res, $driving_data);
                    if ($key == null) {
                        $direct_data[] = $res;
                    } else {
                        $direct_data[$key] = $res;
                    }
                } else {
                    // We need to return the malevolent data...
                    $this->bad_outputs[] = $fields;
                }
            }
        }

        return $direct_data;
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
     *  Since a single query can return multiple results, if we want to 
     *  allow good results but not bad ones, then we save them here.
     **/
    function get_bad_outputs() {
        return $this->bad_outputs;
    }

    function flush_bad_outputs() {
        $this->bad_outputs = array();
    }

    /**
     *  Returns an index to use for the return data. Default is to not
     *  index the results in any way, and have a default integer index.
     *  
     *  @param $fields Array The data to be indexed.
     *  @param $oldfields Array The data that was sent in.
     *  @return string The key to use for the index.
     **/
    function get_key($fields) {
        return null;
    }

    /**
     *  Trims all fields and makes the case of the keys to lower case.
     **/
    function clean_row($fields) {
        $new = array_change_key_case($fields, CASE_LOWER);

        $notrim = is_array($this->notrim) ? $this->notrim : array();
        
        foreach ($new as $k => $v) {
            if (in_array($k, $notrim)) {
                continue;
            }

            $new[$k] = trim($v);
        }

        $new = self::db_decode($new);

        return $new;
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
     *  This function will be run on every returned Registrar entry.
     *  If this function returns false, the entry from the Registrar will
     *  not be returned, but will be stored specially.
     *
     *  @see retrieve_registrar_info()
     *
     *  @param $new Array The row from the Registrar.
     *  @param $old Array The row from the driving data.
     *  @return boolean
     *      Registrar entries that fail to validate can be accessed
     *      separately.
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

        /* Add '/' to beginning of this line to debug registrar SQL statements
        if ($CFG->debug > 0) {
            $extdb->debug = true;
        }
        //*/

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
                'registrar connection failed!'
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
