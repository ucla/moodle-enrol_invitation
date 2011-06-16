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
    /** Static variables for the static function **/
    const hook_fn = 'ucla_cp_hook';
    const mod_prefix = 'ucla_cp_mod_';
    const cp_module_blocks = '__blocks__';
    
    function init() {
        $this->title = get_string('pluginname', 'block_ucla_control_panel');
        $this->content_type = BLOCK_TYPE_TEXT;
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
            'blocks-ucla_control_panel' => false,
            'not-really-exists' => true
        );
    }

    /**
        This will return the views defined by a view file.
    **/
    static function load_cp_views($customloc=null) {
        $default = '/cp_views.php';

        if ($customloc != null) {
            if (!preg_match('/.*\.php$/', $customloc)) {
                $customloc = $default;
            }
        } else {
            $customloc = $default;
        }

        $file = dirname(__FILE__) . $customloc;

        if (!file_exists($file)) {
            debugging('Could not find views file: ' . $file);
        } else {
            include($file);
        }

        if (!isset($views)) {
            $views = array();
        }

        if (!isset($views['default'])) {
            $views['default'] = array('ucla_cp_mod_common',
                '__blocks__', 'ucla_cp_mod_other');
        }

        ksort($views);

        return $views;
    }

    /**
        This will create a link to the control panel.
    **/
    static function create_control_panel_link($course) {
        global $CFG;

        $courseid = $course->id;

        return new moodle_url($CFG->wwwroot . '/blocks/ucla_control_panel/'
            . 'view.php', array('courseid' => $courseid));
    }

    /**
        This will load the custom control panel elements, as well as any blocks
        that have the designated hook function to create elements.
    **/
    static function load_cp_elements($course, $context=null, 
            $view='default') {
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

        // Grab the possible collections of modules to display
        $views = block_ucla_control_panel::load_cp_views();

        if (isset($views[$view])) {
            $allowed_views = $views[$view];
        }

        if (!isset($allowed_views)) {
            // This is a back-up default
            $allowed_views = array('ucla_cp_mod_common',
                '__blocks__', 'ucla_cp_mod_other');
        }

        // Load all the control panel modules.
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
                $module_name = $module->get_key();

                if ($module->tags != null) {
                    foreach ($module->tags as $section) {
                        $sections[$section][$module_name] = $module;
                    }
                } else {
                    $tags[$module_name] = $module;
                }
            }
        }
       
        // Eliminate unvalidated sections as well as repeated-displayed
        // sections
        // Note that these sections appear in the order they were placed
        // into cp_modules.php
        $already_used = array();
        foreach ($sections as $tag => $modules) {
            if (!isset($tags[$tag])) {
                unset($sections[$tag]);
                continue;
            }

            foreach ($modules as $mkey => $module) {
                if (isset($already_used[$mkey])) {
                    unset($sections[$tag][$mkey]);
                } else {
                    $already_used[$mkey] = true;
                }
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
