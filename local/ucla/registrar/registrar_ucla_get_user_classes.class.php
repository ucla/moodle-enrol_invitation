<?php

require_once(dirname(__FILE__) . '/registrar_stored_procedure.base.php');

class registrar_ucla_get_user_classes extends registrar_stored_procedure {
    var $notrim = array('catlg_no');

    function get_query_params() {
        return array('uid');
    }

    function get_stored_procedure() {
        return 'ucla_get_user_classes';
    }
}
