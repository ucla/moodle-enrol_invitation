<?php

defined('MOODLE_INTERNAL') || die();

class division_handler extends browseby_handler {
    function get_params() {
        return array();
    }

    /**
     *  A list of divisions.
     **/
    function handle($args) {
        $s = '';
        $t = get_string('division_title', 'block_ucla_browseby');

        // This is the parameters for one of the two possible query
        // types in this function...
        $params = array();
        
        $term = $args['term'];
        $params['term'] = $term; 
        
        $sql = "
        SELECT DISTINCT
            CONCAT(di.code, rci.term) AS rsetid,
            di.code, 
            di.fullname, 
            rci.term
        FROM {ucla_reg_division} di
        INNER JOIN {ucla_reg_classinfo} rci
            ON rci.division = di.code
        WHERE rci.term = :term
        ORDER BY di.fullname
        ";
        
        $divisions = $this->get_records_sql($sql, $params);
        
        $s .= block_ucla_browseby_renderer::render_terms_selector(
            $args['term']);
                
        if (empty($divisions)) {
            $s .= get_string('division_noterm', 'block_ucla_browseby');
            return array($t, $s);
        } else {
            $table = $this->list_builder_helper($divisions, 'code',
                'fullname', 'subjarea', 'division');
        }
        
        $s .= block_ucla_browseby_renderer::ucla_custom_list_render(
            $table);

        return array($t, $s);
    }
}
