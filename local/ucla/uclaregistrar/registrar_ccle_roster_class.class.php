<?php

class registrar_ccle_roster_class extends registrar_query {
    var $unindexed_key_translate = array('term' => 0, 'srs' => 1);
    
    function validate($new, $old) {
        if (empty($new['bolid'])) {
            return false;
        }

        return true;
    }

    function remote_call_generate($args) {
        if (isset($args[0])) {
            $term = $args[0];
        } else {
            $term = $args['term'];
        }

        if (isset($args[1])) {
            $srs = $args[1];
        } else {
            $srs = $args['srs'];
        }

        if (!ucla_validator('term', $term) || !ucla_validator('srs', $srs)) {
            return false;
        }  

        return "EXECUTE ccle_roster_class '$term', '$srs'";
    }
}
