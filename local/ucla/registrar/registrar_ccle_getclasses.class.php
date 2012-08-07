<?php
require_once(dirname(__FILE__).'/registrar_stored_procedure.base.php');

class registrar_ccle_getclasses extends registrar_stored_procedure {
    function get_query_params() {
        return array('term', 'srs');
    }

    function get_stored_procedure() {
        return 'ccle_getClasses';
    }

    function validate($new, $old) {
        $tests = array('srs', 'term');

        foreach ($tests as $criteria) {
            if (empty($new[$criteria]) 
                    || $new[$criteria] != $old[$criteria]) {
                return false;
            }
        }
               
        return true;
    }
}
