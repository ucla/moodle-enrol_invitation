<?php

class registrar_ccle_roster_class extends registrar_query {
    function validate($new, $old) {
        if (empty($new['bolid'])) {
            return false;
        }

        if (empty($new['stu_id'])) {
            return false;
        }

        return true;
    }

    function remote_call_generate($args) {
        if (!ucla_validator('term', $args[0])) {
            return false;
        }

        $term = $args[0];

        if (!ucla_validator('srs', $args[1])) {
            return false;
        }

        $srs = $args[1];

        return "EXECUTE CCLE_ROSTER_CLASS '$term', '$srs'";
    }
}
