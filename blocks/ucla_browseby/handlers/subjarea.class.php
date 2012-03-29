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
            $where = 'WHERE rci.division = :division';

            $division = $this->get_division($division);

            self::alter_navbar();

            $t = get_string('subjarea_title', 'block_ucla_browseby', 
                $division);
        } else {
            $t = get_string('all_subjareas', 'block_ucla_browseby');
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
            $where
        ";

        $subjectareas = $this->get_records_sql($sql, $conds);

        if (empty($subjectareas)) {
            print_error('noresultsfound');
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
