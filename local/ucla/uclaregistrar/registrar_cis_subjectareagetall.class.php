<?php

require_once(dirname(__FILE__).'/registrar_query.class.php');

class registrar_cis_subjectareagetall extends registrar_query {
    function validate($new, $old) {
        return true;
    }

    function remote_call_generate($args) {
        // if array, then just get first element.
        if (is_array($args)) {
            $args = array_shift($args);
        }        
        
        if (ucla_validator('term', $args)) {
            $term = $args;
        } else {
            return false;
        }

        return "EXECUTE CIS_subjectAreaGetAll '$term'";
    }
}
