<?php

require_once(dirname(__FILE__) . '/registrar_stored_procedure.base.php');

class registrar_ccle_roster_class extends registrar_stored_procedure {
    function get_query_params() {
        return array('term', 'srs');
    }

    function get_stored_procedure() {
        return 'CCLE_ROSTER_CLASS';
    }

    function validate($new, $old) {
        if (empty($new['bolid'])) {
            return false;
        }

        return true;
    }
}
