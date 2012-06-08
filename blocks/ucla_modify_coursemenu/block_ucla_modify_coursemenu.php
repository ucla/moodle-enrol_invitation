<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');

class block_ucla_modify_coursemenu extends block_base {
    const primary_domnode = 'ucla-modifycoursemenu-main';
    const maintable_domnode = 'sections-table';
    const newnodes_domnode = 'new-sections';
    const sectionsorder_domnode = 'sections-order';

    const landingpage_domnode = 'landing-page';
    const serialized_domnode = 'serialized-data';

    const add_section_button = 'add-section-button';

    public function init() {
        $this->title = get_string('ucla_modify_course_menu', 
            'block_ucla_modify_coursemenu');
    }

    public function get_content() {
        if ($this->content !== null) {
          return $this->content;
        }

        $this->content = new object();

        return $this->content;
    }

    function section_apply($oldsection, $newsection) {
        foreach ($newsection as $f => $v) {
            if (isset($oldsection->{$f})) {
                $oldsection->{$f} = $v;
            }
        }

        return $oldsection;
    }

    function section_can_delete($section) {
        return empty($section->sequence);
    }

    function js_init_code_helper($varname, $value) {
        global $PAGE;

        $PAGE->requires->js_init_code(
                js_writer::set_variable(
                    'M.block_ucla_modify_coursemenu.' . $varname, 
                    $value
                )
            );
    }

    function many_js_init_code_helpers($vararr) {
        foreach ($vararr as $vn => $vd) {
            self::js_init_code_helper($vn, $vd);
        }
    }

} 
