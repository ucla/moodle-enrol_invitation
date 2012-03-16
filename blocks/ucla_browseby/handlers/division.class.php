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

        if (empty($divisions)) {
            print_error('division_none', 'block_ucla_browseby');
        } else {
            $table = $this->list_builder_helper($divisions, 'code',
                'fullname', 'subjarea', 'division');

            $s .= block_ucla_browseby_renderer::ucla_custom_list_render(
                $table);
        }

        return array($t, $s);
    }
    
    /**
     *  decoupled functions
     **/
    protected function get_divisions() {
        global $DB;

        return $DB->get_records('ucla_reg_division');
    }
}
