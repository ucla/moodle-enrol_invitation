<?php

require_once(dirname(__FILE__) . '/registrar_query.class.php');

class registrar_cis_coursegetall extends registrar_query {
    function validate($new, $old) {
        // TODO add some validations
        return true;
    }

    function remote_call_generate($args) {
        $term = $args[0];
        $sa = $args[1];

        return "EXECUTE CIS_courseGetAll '$term', '$sa'";
    }

}
