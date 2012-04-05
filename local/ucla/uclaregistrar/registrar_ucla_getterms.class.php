<?php

require_once(dirname(__FILE__) . '/registrar_query.class.php');

class registrar_ucla_getterms extends registrar_query {

    function validate($new, $old) {
        // TODO add some validations
        return true;
    }

    function remote_call_generate($args) {
        
        //Parse the args array for the term argument.
        if (isset($args[0])) {
            $term = $args[0];
        } else {
            $term = $args['term'];
        }

        //Exit if the term is not a valid term.
        if (!ucla_validator('term', $term)) {
            return false;
        }

        return "EXECUTE ucla_getterms '$term'";
    }
}
