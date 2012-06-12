<?php

require_once(dirname(__FILE__).'/registrar_query.base.php');

class registrar_cis_subjectareagetall extends registrar_query {
    function validate($new, $old) {
        return true;
    }

    function remote_call_generate($args) {
        if (ucla_validator('term', $args)) {
            $term = $args;
        } else {
            return false;
        }

        return "EXECUTE CIS_subjectAreaGetAll '$term'";
    }
}
