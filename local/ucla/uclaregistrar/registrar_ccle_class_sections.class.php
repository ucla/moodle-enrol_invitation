<?php

require_once(dirname(__FILE__).'/registrar_query.class.php');

class registrar_ccle_class_sections extends registrar_query {
    function validate($new, $old) {
        return true;
    }

    function remote_call_generate($args) {
        $term = $args['term'];
        $srs = $args['srs'];

        return "EXECUTE ccle_class_sections '$term', '$srs'";
    }
}
