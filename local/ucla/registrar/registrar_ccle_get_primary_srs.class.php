<?php

require_once(dirname(__FILE__) . '/registrar_cacheable_stored_procedure.base.php');

class registrar_ccle_get_primary_srs extends registrar_cacheable_stored_procedure {
    // set timeout to 1 month/30 days (these results shouldn't change)
    static $registrar_cache_ttl = 2592000;
    
    public function __construct() {
        parent::__construct();
    }
    
    function get_query_params() {
        return array('term', 'srs');
    }

    function get_result_columns() {
        return array('srs_crs_no');
    }    
    
    function get_stored_procedure() {
        return 'ccle_get_primary_srs';
    }
}