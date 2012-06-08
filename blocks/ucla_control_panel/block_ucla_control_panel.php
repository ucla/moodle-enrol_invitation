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

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../moodleblock.class.php');
require_once(dirname(__FILE__) . '/ucla_cp_module.php');
require_once($CFG->dirroot.'/local/ucla/lib.php');
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
        if (!isset($this->course)) {
            global $COURSE;
            $this->course = $COURSE;
        }

        return self::get_action_link($this->course);
    }

    function instance_allow_config() {
        return true;
    }

    /**
     *  Returns the applicable places that this block can be added.
     *  This block really cannot be added anywhere, so we just made a place
     *  up (hacky). If we do not do this, we will get this
     *  plugin_devective_exception.
     **/
    function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false,
            'blocks-ucla_control_panel' => false,
            'not-really-applicable' => true
        );
    }

    /**
     *  This will return the views defined by a view file.
     *  Views are each group of command, sorted by tabs.
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
                'ucla_cp_mod_myucla', 'ucla_cp_mod_other','ucla_cp_mod_student');
        }

        ksort($views);

        return $views;
    }

    /**
     *  This will create a link to the control panel.
     **/
    static function get_action_link($course) {
        global $CFG;

        $courseid = $course->id;

        return new moodle_url($CFG->wwwroot . '/blocks/ucla_control_panel/'
            . 'view.php', array('course_id' => $courseid));
    }

    /**
     *  This will load the custom control panel elements, as well as any blocks
     *  that have the designated hook function to create elements.
     *  @return Array ( Views => Array ( Tags => Array ( Modules ) ) )
     **/
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

        // Grab the possible collections of modules to display
        $views = block_ucla_control_panel::load_cp_views();

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
       
        // The modular block sections
        $block_modules = block_ucla_control_panel::load_cp_block_elements(
            $course, $context
        ); 

        foreach ($block_modules as $block => $blocks_modules) {
            $modules = array_merge($modules, $blocks_modules);
        }
        
        // Figure out which elements of the control panel to display and
        // which section to display the element in
        foreach ($modules as $module) {
            //If the modules capability matches that of the current context.
            if ($module->validate($course, $context)) {
                $module_name = $module->get_key();
                
                if (!$module->is_tag()) {
                    // If something fits with more than one tag, add
                    // it to both of them
                    
                    foreach ($module->tags as $section) {
                        $sections[$section][] = $module;
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
            // This means that a module has multiple tags, and one of the tags
            // are not view-valid
            if (!isset($tags[$tag])) {
                unset($sections[$tag]);
                continue;
            }

            // Go through and make sure we're not repeating modules
            foreach ($modules as $index => $module) {
                $mkey = $module->get_key();
                
                if ($mkey == 'row_module') {
                    continue;   // don't dedup myucla links
                }
                
                if (isset($already_used[$mkey])) {
                    unset($sections[$tag][$index]);
                    echo '$mkey = ' . $mkey;
                } else {
                    $already_used[$mkey] = true;
                }
            }
        }
        
        // Now based on each view, sort the tags into their proper
        // tabs
        $all_modules = array();
        $used_tags = array();
        foreach ($views as $view => $tags) {
            foreach ($tags as $tag) {
                if (isset($sections[$tag])) {
                    // If this tag already exists in another tab, 
                    // skip it
                    if (isset($used_tags[$tag])) {
                        continue;
                    }

                    $used_tags[$tag] = true;

                    if (!isset($all_modules[$view])) {
                        $all_modules[$view] = array();
                    }

                    $all_modules[$view][$tag] = $sections[$tag];
                }
            }
        }
        
        // Now we're going to add more tabs based on tags we don't
        // have in our views already
        foreach ($sections as $tag => $modules) {
            if (isset($used_tags[$tag])) {
                continue;
            }

            $all_modules[$tag][$tag] = $modules;
        }
        return $all_modules;
    }

    static function load_cp_block_elements($course=null, $context=null) {
        global $CFG, $PAGE;

        $all_blocks = $PAGE->blocks->get_installed_blocks();

        $static = block_ucla_control_panel::hook_fn;

        $cp_elements = array();

        /**
         *  This functionality is repeated somewhere I don't know where
         *  and it sucks.
         **/
        foreach ($all_blocks as $block) {
            $block_name = 'block_' . $block->name;
            if (!class_exists($block_name)) {
                $filedir = $CFG->dirroot . '/blocks/' . $block->name
                     . '/'; 

                $filename = $filedir . $block_name . '.php';

                if (file_exists($filename)) {
                    require_once($filename);
                }

                $renderclass = $block_name . '_cp_render.php';
                $rendername = $filedir . $renderclass;
                if (!class_exists($renderclass) 
                        && file_exists($rendername)) {
                    require_once($rendername);
                }
            }

            if (method_exists($block_name, $static)) {
                $blockmodules = $block_name::$static($course,
                    $context);

                if (!empty($blockmodules)) {
                    foreach ($blockmodules as $blockmodule) {
                        $module = ucla_cp_module::build($blockmodule);
                        $module->associated_block = $block_name;
                        $cp_elements[$block_name][] = $module;
                    }                    
                }
            }
        }

        return $cp_elements;
    }
}

/** eof **/
