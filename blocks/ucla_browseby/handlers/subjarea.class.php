<?php

class subjarea_handler extends browseby_handler {
    function get_params() {
        return array('division');
    }

    function handle($args) {
        global $OUTPUT;

        $division = false;
        if (isset($args['division'])) {
            $division = $args['division'];
        }

        if (isset($args['term'])) {
            $term = $args['term'];
        } else {
            $term = false;
        }

        $conds = array();
        $where = '';
       
        if ($division) {
            $conds['division'] = $division;
            $where = 'WHERE urs.division = :division';
        } else {
            $division = get_string('all_subjareas', 'block_ucla_browseby');
        }

        if (empty($conds)) {
            $conds = null;
        }

        // This is the content
        $s = '';
        // This is the title
        $t = get_string('subjarea_title', 'block_ucla_browseby', 
            $division);

        // Display a list of things to help us narrow down our path to 
        // destination
        $sql = "
            SELECT DISTINCT
                CONCAT(urs.subjarea, ubc.term) AS rsid,
                urs.subjarea,
                urs.subj_area_full,
                ubc.term
            FROM {ucla_browseall_classinfo} ubc
            INNER JOIN {ucla_reg_subjectarea} urs
                ON ubc.subjarea = urs.subjarea
            $where
        ";

        $subjectareas = $this->get_records_sql($sql, $conds);

        if (empty($subjectareas)) {
            return array(false, false);
        }

        $terms = array();
        foreach ($subjectareas as $k => $subjarea) {
            // Save this query...
            $this->subjareas_pretty[$subjarea->subjarea] = 
                $subjarea->subj_area_full;

            // Figure out which terms we can include in the drop down
            $tt = $subjarea->term;
            $terms[$tt] = $tt;
            if ($term !== false && $tt != $term) {
                unset($subjectareas[$k]);
            }
        }

        list($w, $p) = $this->render_terms_restricted_helper($terms);
        $s .= block_ucla_browseby_renderer::render_terms_selector(
            $args['term'], $w, $p);

        $table = $this->list_builder_helper($subjectareas, 'subjarea',
            'subj_area_full', 'course', 'subjarea', $term);

        $s .= block_ucla_browseby_renderer::ucla_custom_list_render($table);

        return array($t, $s);
    }
}
