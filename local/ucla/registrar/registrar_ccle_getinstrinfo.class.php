<?php

require_once(dirname(__FILE__) . '/registrar_query.base.php');

class registrar_ccle_getinstrinfo extends registrar_query {
    function validate($new, $old) {
        return true;
    }

    function remote_call_generate($args) {
        $term = $args['term'];

        if (!ucla_validator('term', $term)) {
            return false;
        }

        return "EXECUTE ccle_GETINSTRINFO '$term', '{$args['subjarea']}'";
    }
}
