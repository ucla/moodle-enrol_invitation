<?php

require_once(dirname(__FILE__).'/registrar_query.base.php');

class registrar_ccle_class_sections extends registrar_query {
    function validate($new, $old) {
        return true;
    }
    
    function get_query_params() {
        return array('term', 'srs');
    }

    function remote_call_generate($args) {
        $term = $args['term'];
        $srs = $args['srs'];

        return "EXECUTE ccle_class_sections '$term', '$srs'";
    }
}
