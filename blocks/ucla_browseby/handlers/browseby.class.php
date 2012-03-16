<?php

/**
 *  This is the base class.
 *  Very abstract indeed...
 **/
abstract class browseby_handler {
    /**
     *  @return Array( ... ) strings that we should require_param, you do
     *      not need to include 'term'
     **/
    abstract function get_params();

    /**
     *  @param Array 
     *  @return Array(string $title, string $content)
     **/
    abstract function handle($args);

    /**
     *  Hook function to do some checks before running the handler.
     **/
    function run_handler($args) {

    }
    
    /**
     *  A highly-specific convenience function. That feeds into the
     *  renderer.
     **/
    function list_builder_helper($data, $keyfield, $dispfield, $type, $get, 
                                 $term=false) {
        $table = array();
        foreach ($data as $datum) {
            $k = $datum->{$keyfield};
            $queryterms = array('type' => $type, $get => $k);
            if ($term) {
                $queryterms['term'] = $term;
            }

            $table[$k] = html_writer::link(
                new moodle_url('/blocks/ucla_browseby/view.php', $queryterms),
                ucwords(strtolower($datum->{$dispfield}))
            );
        }

        return $table;
    }
  
    /** 
     *  Returns a display-ready string for subject areas.
     **/
    function get_pretty_subjarea($subjarea) {
        global $DB;
    
        $sa = $DB->get_record('ucla_reg_subjectarea', 
            array('subjarea' => $subjarea));

        if ($sa) {
            return $sa->subj_area_full;
        }

        return false;
    }

    /**
     *  Decoupled functions.
     **/
    protected function render_terms_restricted_helper($rt=false) {
        return block_ucla_browseby_renderer::render_terms_restricted_helper(
            $rt);
    }

    protected function get_records_sql($sql, $params) {
        global $DB;

        return $DB->get_records_sql($sql, $params);
    }
}

