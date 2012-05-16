<?php

require_once(dirname(__FILE__) . '/registrar_query.class.php');

class registrar_ucla_getterms extends registrar_query {
    var $unindexed_key_translate = array('term' => 0);
    
    function validate($new, $old) {
        $tests = array('term', 'session','session_start','session_end','instruction_start');
        foreach ($tests as $criteria) {
            if (!isset($new[$criteria])) {
                return false;
            }
        }
        
        if (!ucla_validator('term', $new['term'])) {
            return false;
        }
        
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
