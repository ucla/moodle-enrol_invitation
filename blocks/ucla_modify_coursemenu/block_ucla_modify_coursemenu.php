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
        $this->title = get_string('pluginname', 
            'block_ucla_modify_coursemenu');
    }

    public function get_content() {
        if ($this->content !== null) {
          return $this->content;
        }

        $this->content = new object();

        return $this->content;
    }

    /**
     *  Fetches the course_module information of a section.
     *  Note: there is a way to get it just with modinfo, and bypassing
     *      sections completely... 
     *  @param  $section    {section} entry
     *  @param  $course     {course} entry, optional if $section->course exists
     *  @param  $modinfo    results of get_fast_modinfo(), optional
     *  @return array() of cm_info, can be used with get_print_section_cm_text
     *      or anything else...
     **/
    function get_section_content($section, $course=null, $modinfo=null) {
        global $DB;
        if ($modinfo == null) {
            if ($course == null) {
                if (empty($section->course)) {
                    // We can have logic that figures out the modules anyway, 
                    // but yea
                    throw new moodle_exception('sectioncontentnocourse');
                }
            } else {
                $course = $DB->get_record('course', array('id' => $courseid),
                    '*', MUST_EXIST);
            }

            $modinfo = get_fast_modinfo($course);
        }

        if (empty($section->sequence)) {
            return array();
        }

        $cminstances = array_map('trim', explode(',', 
                $section->sequence));
        $cminfos = array();

        foreach ($cminstances as $cminstanceid) {
            $cminfos[] = $modinfo->cms[$cminstanceid];
        }

        return $cminfos;
    }

    /**
     *  Convenience function to overwrite values of oldsection with
     *  values in newsection, but only if oldsection has the field already.
     **/
    function section_apply($oldsection, $newsection) {
        foreach ($newsection as $f => $v) {
            if (isset($oldsection->{$f})) {
                $oldsection->{$f} = $v;
            }
        }

        return $oldsection;
    }

    /**
     *  Checks if section is empty, used to be called "section_cannot_delete"
     *  but behavior changed.
     *  
     **/
    function section_is_empty($section) {
        return empty($section->sequence);
    }

    /**
     *  Convenience function for generating code that sets data to
     *  associated js object.
     **/
    function js_init_code_helper($varname, $value) {
        global $PAGE;

        $PAGE->requires->js_init_code(
                js_writer::set_variable(
                    'M.block_ucla_modify_coursemenu.' . $varname, 
                    $value
                )
            );
    }

    /**
     *  Convenience function.
     **/
    function many_js_init_code_helpers($vararr) {
        foreach ($vararr as $vn => $vd) {
            self::js_init_code_helper($vn, $vd);
        }
    }

    function ucla_cp_hook($course, $context) {
        return array(array(
            'item_name' => 'modify_course_sections', 
            'action' => new moodle_url(
                    '/blocks/ucla_modify_coursemenu/modify_coursemenu.php', 
                    array('courseid' => $course->id)
                ),
            'tags' => array('ucla_cp_mod_common'),
            'required_cap' => 'moodle/course:update'
        ));
    }

} 
