<?php

require_once(dirname(__FILE__) . '/registrar_query.base.php');

class registrar_ucla_get_user_classes extends registrar_query {
    var $skip_clean_fields = array('catlg_no');

    function validate($new, $old) {
        return true;
    }

    function remote_call_generate($args) {
        $uid = $args['uid'];

        $termstr = '';
        if (isset($args['term']) && ucla_validator('term', $args['term'])) {
            $termstr = ", '" . $args['term'] . "'";
        }

        return "EXECUTE ucla_get_user_classes '$uid' $termstr";
    }
}
