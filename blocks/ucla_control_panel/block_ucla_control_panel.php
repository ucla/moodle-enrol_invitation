<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once(dirname(__FILE__) . '/../moodleblock.class.php');
require_once(dirname(__FILE__) . '/ucla_cp_module.php');

class block_ucla_control_panel extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_ucla_control_panel');
    }
    
    function get_content() {
        // No content
    }

    function instance_allow_config() {
        return true;
    }

    function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'blocks-ucla_control_panel' => false
        );
    }

    /**
        This will create a link to the control panel.
    **/
    static function create_control_panel_link($courseid) {
        global $CFG;

        return new moodle_url($CFG->wwwroot . '/blocks/ucla_control_panel/'
            . 'view.php', array('courseid' => $courseid));
    }

    const hook_fn = 'ucla_cp_hook';
    const mod_prefix = 'ucla_cp_mod_';
    
    static function load_cp_elements($course, $context=null) {
        if (!isset($course->id) && is_string($course)) {
            $course_id = $course;

            $course = new stdClass();
            $course->id = $course_id;
        }

        if ($course->id == SITEID) {
            throw new moodle_exception('Cannot open UCLA control panel '
                 . ' for site home!');
        }

        if ($context === null) {
            $context = get_context_instance(CONTEXT_COURSE, $course_id);
        }

        $file = dirname(__FILE__) . '/cp_modules.php';
        if (!file_exists($file)) {
            debugging('No control panel module list ' . $file);
            return false;
        }

        $modules = array();

        include($file);

        if (empty($modules)) {
            debugging('No modules found in ' . $file);
        }
    
        $sections = array();
        $tags = array();

        // Figure out which elements of the control panel to display and
        // which section to display the element in
        foreach ($modules as $module) {
            if ($module->validate($course, $context)) {
                if ($module->tags != null) {
                    foreach ($module->tags as $section) {
                        $sections[$section][$module->item_name] = $module;

                        // We only go into the first section
                        break;
                    }
                } else {
                    $tags[$module->item_name] = $module;
                }
            }
        }
       
        // Eliminate unvalidated sections
        foreach ($sections as $tag => $modules) {
            if (!isset($tags[$tag])) {
                unset($sections[$tag]);
            }
        }

        // The modular block sections
        $block_sections = block_ucla_control_panel::load_cp_block_elements(); 

        $return_sections = array_merge($block_sections, $sections);

        return $return_sections;
    }

    static function load_cp_block_elements($course=null, $context=null) {
        global $PAGE;

        $all_blocks = $PAGE->blocks->get_installed_blocks();

        $static = block_ucla_control_panel::hook_fn;

        $cp_elements = array();

        foreach ($all_blocks as $block) {
            $block_name = $block->name;

            if (method_exists($block_name, $static)) { 
                $cp_elements[$block_name] = $block->$static($course,
                    $context);
            }
        }

        return $cp_elements;
    }
}

/** eof **/
