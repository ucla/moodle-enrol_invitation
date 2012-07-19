<?php

require_once(dirname(__FILE__) . '/registrar_query.class.php');

class registrar_ccle_getinstrinfo extends registrar_query {
    var $unindexed_key_translate = array('term' => 0, 'subjarea' => 1);
    
    function validate($new, $old) {
        return true;
    }

    function remote_call_generate($args) {
        if (isset($args[0])) {
            $term = $args[0];
        } else {
            $term = $args['term'];
        }

        if (isset($args[1])) {
            $sa = $args[1];
        } else {
            $sa = $args['subjarea'];
        }

        if (!ucla_validator('term', $term)) {
            return false;
        }
        
        return "EXECUTE ccle_getinstrinfo '$term', '$sa'";
    }
}
