<?php

require_once(dirname(__FILE__) . '/registrar_query.class.php');

class registrar_ccle_getinstrinfo extends registrar_query {
    function validate($new, $old) {
        return true;
    }

    function remote_call_generate($args) {
        if (!isset($args['term']) && isset($args[0])) {
            $args['term'] = $args[0];
        } else {
            return false;
        }

        $term = $args['term'];

        if (!ucla_validator('term', $term)) {
            return false;
        }

        if (!isset($args['subjarea']) && isset($args[1])) {
            $args['subjarea'] = $args[1];
        } else {
            return false;
        }

        return "EXECUTE ccle_GETINSTRINFO '$term', '{$args['subjarea']}'";
    }
}
