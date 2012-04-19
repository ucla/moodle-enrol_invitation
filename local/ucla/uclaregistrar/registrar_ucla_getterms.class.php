<?php

require_once(dirname(__FILE__) . '/registrar_query.class.php');

class registrar_ucla_getterms extends registrar_query {
    var $unindexed_key_translate = array('term' => 0);
    
    function validate($new, $old) {
        // TODO add some validations
        return true;
    }

    function remote_call_generate($args) {
        
        //Exit if the term is not a valid term.
        if (ucla_validator('term', $args) == false) {
            return false;
        }

        return "EXECUTE ucla_getterms '$args'";
    }
}
