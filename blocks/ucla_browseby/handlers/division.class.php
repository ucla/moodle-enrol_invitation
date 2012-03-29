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

        $divisions = $this->get_divisions();

        $term = $args['term'];

        if (empty($divisions)) {
            print_error('division_none', 'block_ucla_browseby');
        } else {
            $displaydivisions = array();
            $displayterms = array();
            foreach ($divisions as $division) {
                $dterm = $division->term;

                if ($dterm == $term) {
                    $displaydivisions[] = $division;
                }

                $displayterms[$dterm] = $dterm;
            }

            $s .= block_ucla_browseby_renderer::make_terms_selector(
                $displayterms, $term);

            if (empty($displaydivisions)) {
                $s .= get_string('division_noterm', 'block_ucla_browseby');
            } else {
                $table = $this->list_builder_helper($displaydivisions, 'code',
                    'fullname', 'subjarea', 'division');

                $s .= block_ucla_browseby_renderer::ucla_custom_list_render(
                    $table);
            }
        }

        return array($t, $s);
    }
    
    /**
     *  decoupled function... well it used to be one
     **/
    protected function get_divisions() {
        global $DB;

        $sql = "
        SELECT DISTINCT
            CONCAT(di.code, rci.term) AS rsetid,
            di.code, 
            di.fullname, 
            rci.term
        FROM {ucla_reg_division} di
        INNER JOIN {ucla_reg_classinfo} rci
            ON rci.division = di.code
        ";

        return $DB->get_records_sql($sql);
    }
}
