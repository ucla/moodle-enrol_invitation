<?php
/**
 * local/ucla/registrar/registrar_cacheable_stored_procedure.base.php
 * 
 * This class implements a caching layer for any stored procedure that is 
 * derived from it. The caching layer depends on some naming conventions for 
 * its database tables:
 * 
 * <stored procedure name>_cache
 * 
 * The  <stored procedure name>_cache table is outlined as:
 * id
 * param_<param name> Then as many columns as needed by get_query_params()
 * expires_on   UNIX timestamp for how long the cache entries related to this  
 *              query should be valid
 * <return name>    Then as many columns as needed by get_result_columns()
 */

require_once(dirname(__FILE__) . '/registrar_stored_procedure.base.php');

abstract class registrar_cacheable_stored_procedure extends registrar_stored_procedure {
    /**
     * If null, then will use the local_plugin setting. Here so that child
     * classes can override plugin settings.
     * @var int
     */
    static $registrar_cache_ttl = null;
    
    public function __construct() {
        // see if ttl was manually set beforehand, else use plugin default
        if (empty(static::$registrar_cache_ttl)) {
            static::$registrar_cache_ttl = get_config('local_ucla', 'registrar_cache_ttl');
        }
    }

    /**
     *  Returns the array describing the columns that are returned by the
     *  stored procedure.
     *  @return array
     **/
    abstract function get_result_columns();
    
    /**
     * This function will first try to see if there is a valid cache copy of
     * the results for the given set of parameters.
     * 
     * If a valid cache is found, then those results are returned instead. If
     * not, then the regular retrieve_registrar_info is called and a cache copy
     * is saved.
     * 
     * Note, that parameters that return 0 results will always be calling the 
     * registrar.
     *
     *  @param $driving_data The data to run a set of queries on.
     *  @param  $cached Default true. If false, then will return uncached data
     *  @return Array( Array( ) )
     *      false - indicates bad input
     *      empty array() - indicates good input, but no results
     **/    
    function retrieve_registrar_info($driving_data, $cached=true) {
        global $DB;
        $query_params = array();    // store values to pass into $DB object
        $results = array();         // results to return
        
        // see if caching is turned off
        if (!$cached) {
            // just call parent
            return parent::retrieve_registrar_info($driving_data);
        }
        
        // get information and parameters for this call
        $storedproc_cache = $this->get_stored_procedure() . '_cache';      
        $params = $this->unindexed_key_translate($driving_data);
        if (empty($params)) {
            return false;
        }       
        
        // columns to be returned
        $columns_to_return = implode(',', $this->get_result_columns());
        
        // try to see if there is a valid cache copy
        // NOTE: id needs to be returned so that we don't get "Did you remember 
        // to make the first column something unique in your call to 
        // get_records?" errors
        $sql = "SELECT  id, $columns_to_return
                FROM    {{$storedproc_cache}}
                WHERE   expires_on >= UNIX_TIMESTAMP() AND ";
        
        $first_entry = true;
        foreach ($params as $name => $value) {
            // remember that parameters columns with have "param_" prefix
            $first_entry ? $first_entry = false : $sql .= ' AND ';
            $sql .= 'param_' . $name . '= :param_' . $name;
            $query_params['param_' . $name] = $value;
        }        
        
        try {
            $cache_results = $DB->get_records_sql($sql, $query_params);           
        } catch (Exception $e) {
            // query failed, maybe table is missing or columns mismatched
            throw new registrar_stored_procedure_exception(
                    sprintf('Cache query failed for: %s (%s)', $storedproc_cache, 
                            print_r($query_params, true)));
        }
        
        if (empty($cache_results)) {
            //debugging('cache miss');
            
            // no valid cache found, so first delete any cache copy;
            // don't want to have old data laying around
            $DB->delete_records($storedproc_cache, $query_params);
             
            // call stored procedure regularly
            $results = parent::retrieve_registrar_info($driving_data);
            
            if (!empty($results)) {
                //debugging('inserting cache');
                
                // save cache copy
                
                // set cache timeout
                $expires_on = time() + static::$registrar_cache_ttl; 
                
                $query_params['expires_on'] = $expires_on;
                
                foreach ($results as $result) {
                    // take advantage of the fact that result is indexed by column name
                    $insert_params = array_merge($query_params, $result);
                    try {                        
                        $DB->insert_record($storedproc_cache, (object) $insert_params, false, true);
                    } catch (Exception $e) {
                        // insert failed, maybe table is missing or columns mismatched
                        throw new registrar_stored_procedure_exception(
                                sprintf('Cache insert failed for: %s (%s)', $storedproc_cache, 
                                        print_r($insert_params, true)));
                    }
                }
            }
        } else {
            //debugging('cache hit');

            // format results so it is returned as an array
            foreach ($cache_results as $cache_result) {
                $results[] = (array) $cache_result;
            }
        }        
        return $results;
   }
}
