<?php

require_once(dirname(__FILE__) . '/registrar_cacheable_stored_procedure.base.php');

class registrar_ccle_roster_class extends registrar_cacheable_stored_procedure {
    function get_query_params() {
        return array('term', 'srs');
    }
    
    function get_result_columns() {
        return array('term_cd', 'stu_id', 'full_name_person', 'enrl_stat_cd', 
            'ss_email_addr', 'bolid');
    }

    function validate($new, $old) {
        if (empty($new['bolid'])) {
            return false;
        }

        return true;
    }
}
