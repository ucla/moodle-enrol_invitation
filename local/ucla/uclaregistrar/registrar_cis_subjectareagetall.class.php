<?php

require_once(dirname(__FILE__).'/registrar_query.class.php');

class registrar_cis_subjectareagetall extends registrar_query {
    function validate($new, $old) {
        return (object) $new;
    }

    function remote_call_generate($args) {
        if (preg_match('/[0-9]{2}[FWS1]/', $args)) {
            $term = $args;
        } else {
            return false;
        }

        return "EXECUTE CIS_subjectAreaGetAll '$term'";
    }
}
