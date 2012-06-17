<?php

require_once(dirname(__FILE__) . '/registrar_stored_procedure.base.php');

class registrar_ucla_getterms extends registrar_stored_procedure {
    function validate($new, $old) {
        $tests = array(
                'term', 
                'session',
                'session_start', 
                'session_end',
                'instruction_start'
            );

        foreach ($tests as $criteria) {
            if (!isset($new[$criteria])) {
                return false;
            }
        }
        
        if (!ucla_validator('term', $new['term'])) {
            return false;
        }
        
        return true;
    }

    function get_query_params() {
        return array('term');
    }
}
