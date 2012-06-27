<?php

defined('MOODLE_INTERNAL') || die();

/**
 *  Tests the connection with the Registrar. NOTHING MORE.
 **/
class registrar_tester extends registrar_query {
    function validate($new, $old) {
        return false;
    }

    function remote_call_generate($args) {
        return false;
    }
}
