<?php

require_once(dirname(__FILE__).'/registrar_stored_procedure.base.php');

class registrar_cis_subjectareagetall extends registrar_stored_procedure {
    function get_query_params() {
        return array('term');
    }

    function get_stored_procedure() {
        return 'CIS_subjectAreaGetAll';
    }
}
