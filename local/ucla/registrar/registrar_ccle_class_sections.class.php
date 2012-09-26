<?php

require_once(dirname(__FILE__).'/registrar_query.base.php');

class registrar_ccle_class_sections extends registrar_stored_procedure {
    function get_query_params() {
        return array('term', 'srs');
    }

    function get_stored_procedure() {
        return 'ccle_class_sections';
    }
}
