<?php

require_once(dirname(__FILE__) . '/registrar_query.base.php');

class registrar_ccle_coursegetall extends registrar_stored_procedure {
    function get_query_params() {
        return array('term', 'subjarea');
    }

    function get_stored_procedure() {
        return 'ccle_courseGetAll';
    }

    function validate($new, $old) {
        return true;
    }
}

