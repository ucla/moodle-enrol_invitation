<?php

require_once(dirname(__FILE__) . '/registrar_stored_procedure.base.php');
//require_once($CFG->dirroot . '/local/ucla/lib.php');

class registrar_ccle_get_primary_srs extends registrar_stored_procedure {
    function get_query_params() {
        return array('term', 'srs');
    }
    
    function get_stored_procedure() {
        return 'ccle_get_primary_srs';
    }
}