<?php

class subjarea_handler extends browseby_handler {
    function get_params() {
        return array('division');
    }

    static function alter_navbar() {
        global $PAGE;

        $urlobj = clone($PAGE->url);
        $urlobj->remove_params(array('division', 'subjarea'));
        $urlobj->params(array('type' => 'division'));
        $PAGE->navbar->add(get_string('division_title', 
            'block_ucla_browseby'), $urlobj);
        
        

    }

    static function get_pretty_division($division) {
        $divisionobj = self::get_division($division);
        $division = to_display_case($divisionobj->fullname);
        return $division;
    }

    function handle($args) {
        global $OUTPUT, $PAGE;
        
        // Load search
        $PAGE->requires->yui_module('moodle-block_ucla_search-search', 'M.ucla_search.init', 
                array(array('name' => 'course-search')));
        
        $division = false;
        if (isset($args['division'])) {
            $division = $args['division'];
        }

        $conds = array();
        $divwhere = '';
        $termwhere = '';
        
        if ($division) {
            $conds['division'] = $division;
            $divwhere = 'WHERE rci.division = :division';

            $division = $this->get_division($division);

            self::alter_navbar();

            $t = get_string('subjarea_title', 'block_ucla_browseby', 
                $division);
        } else {
            $t = get_string('all_subjareas', 'block_ucla_browseby');
        }
        
        if (isset($args['term'])) {
            $term = $args['term'];
            $termwhere = 'AND ubc.term = :term';
            $conds['term'] = $term;
        } else {
            $term = false;
        }

        if (empty($conds)) {
            $conds = null;
        }

        // This is the content
        $s = '';

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
            INNER JOIN {ucla_reg_classinfo} rci
                ON rci.subj_area = urs.subjarea
            $divwhere
            $termwhere
            ORDER BY urs.subj_area_full
        "; 

        $subjectareas = $this->get_records_sql($sql, $conds);
        
        // Query for available terms (for the terms dropdown)
        // Filter by division, if a division selected
        $sql = "SELECT DISTINCT term
                FROM {ucla_reg_classinfo} rci
                $divwhere";

        $s .= block_ucla_browseby_renderer::render_terms_selector(
            $args['term'], $sql, $conds);
        
        if (empty($subjectareas)) {
            $s .= $OUTPUT->notification(get_string('subjarea_noterm',
                    'block_ucla_browseby'));
            return array($t, $s);
        }
        
        $s .= block_ucla_search::search_form('course-search');
        
        $table = $this->list_builder_helper($subjectareas, 'subjarea',
            'subj_area_full', 'course', 'subjarea', $term);

        $s .= block_ucla_browseby_renderer::ucla_custom_list_render($table);

        return array($t, $s);
    }
}
