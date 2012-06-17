<?php

require_once(dirname(__FILE__) . '/registrar_stored_procedure.base.php');

class registrar_ccle_courseinstructorsget extends registrar_stored_procedure {
    function get_query_params() {
        return array('term', 'srs');
    }

    function get_stored_procedure() {
        return 'ccle_CourseInstructorsGet';
    }

    function validate($new, $old) {
        if (!isset($new['srs']) && $new['srs'] != $old['srs']) {
            return false;
        }

        if (!isset($new['ucla_id'])) {
            return false;
        }

        return true;
    }
}
