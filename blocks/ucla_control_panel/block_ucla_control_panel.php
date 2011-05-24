<?php

class block_ucla_control_panel extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_ucla_control_panel');
    }
    
    function get_content() {
        if ($this->content !== NULL) {
            return $this->content;
        }
        
        $this->content = $this->create_control_panel_link(1);
        return $this->content;
    }

    function instance_allow_config() {
        return true;
    }

    function applicable_formats() {
        return array(
            'site-index' => true,
            'course-view' => true,
            'my' => true,
            'blocks-ucla_control_panel' => true
        );
    }

    function create_control_panel_link($courseid) {
        return 'Content';
    }

    function parse_filter($filter) {
        if (strpos($filter, ',') === false) {
            return array($filter);
        } else {
            $ex = explode(',', $filter);

            $filters = array();
            foreach ($ex as $up) {
                $in = trim($up);

                if ($in != '') {
                    $filters[$in] = $in;
                }
            }
        }

        return $filters;
    }

    /**
        This function will go through all the blocks and look for
        a hooker function, which will be called to display actions
        within the control panel.

        Blocks should have the function ucla_cp_hook().
        The modules should have filenames ucla_cp_mod_<module>.php,
            with classes name ucla_cp_mod_<module>, each with a function
            control_panel_contents().

    **/
    const hook_fn = 'ucla_cp_hook';
    const mod_prefix = 'ucla_cp_mod_';
    
    function load_cp_elements($filter='common,myucla,other') {
        global $PAGE;

        $all_blocks = $PAGE->blocks->get_installed_blocks();

        $static = block_ucla_control_panel::hook_fn;

        $filters = $this->parse_filter($filter);

        $cp_elements = array();

        foreach ($all_blocks as $block) {
            $block_name = $block->name;

            if (in_array($block_name, $filters) 
              && method_exists($block_name, $static)) {
                $cp_elements[$block_name] = $block->$static();
            }
        }

        $prefix = block_ucla_control_panel::mod_prefix;

        foreach ($filters as $afilt) {

            $classname = $prefix . $afilt;
            $module_file = dirname(__FILE__) . '/cp_modules/' . $classname 
                . '.php';

            if (file_exists($module_file)) {
                include_once($module_file);
                
                $cp_elements[$afilt] = new $classname();
            }
        }

        return $cp_elements;
    }

}

/** eof **/
