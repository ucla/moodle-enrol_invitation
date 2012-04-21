<?php

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../moodleblock.class.php');

/**
 *  Rearrange library.
 *  Files relevant to using the rearranger.
 **/
class block_ucla_rearrange extends block_base {
    // This is where the entire section stuff should go
    const primary_domnode = 'major-ns-container';
    const default_targetjq = '.ns-primary';
   
    // This is the id of top UL
    const pagelist = 'ns-list';

    // This the class of UL
    const pagelistclass = 'ns-list-class';

    // This the class of LI
    const pageitem = 'ns-list-item';

    // This the id of UL
    const sectionlist = 's-list';
    
    // this the class of UL
    const sectionlistclass = 's-list-class';

    // This is the LI class
    const sectionitem = 's-list-item';

    // Non-nesting class
    const nonnesting = 'ns-invisible';
    
    // Style "hidden" indicator for non-visisble sections/modules
    const hiddenclass = 'ucla_rearrange_hidden';

    /**
     *  Required for Moodle.
     **/
    function init() {
        $this->title = get_string('pluginname', 'block_ucla_rearrange');
        $this->cron = 0;
    }

    /**
     *  Do not allow block to be added anywhere
     */
    function applicable_formats() {
        return array(
            'site-index' => false,
            'course-view' => false,
            'my' => false
        );
    }        
    
    /**
     *  Returns an array of root modnode objects for a particular section.
     *  @param $section     The section number
     *  @param $sectinfo    Section info that includes the sequence of course 
     *                      modules in the section
     *  @param $mods        The list of mods from get_all_mods().
     *  @param $modinfo     The mod information from get_all_mods().
     **/
    static function mods_to_modnode_tree($section, &$sectinfo, &$mods, 
            &$modinfo, $course_id) {

        $nodes = array();
        $sectionmods = array();

        $sectionmods = explode(',', $sectinfo->sequence);

        foreach ($sectionmods as $mod_id) {
            if (isset($mods[$mod_id])) {
                $cm =& $mods[$mod_id];

                if ($cm->section != $sectinfo->id) {
                    // For some reason code branch seems to occur 
                    // intrinsically in Moodle.
                    // TODO Figure out why this happens and what we should do
                    // 'bout it.
                    debugging('Mismatching section for ' . $cm->name
                        . "(got {$cm->section} expecting {$sectinfo->id})\n");
                    continue;
                }

                $display_text = format_string($modinfo->cms[$mod_id]->name,
                    true, $course_id);
                $is_hidden = !$modinfo->cms[$mod_id]->visible;

                $nodes[] = new modnode($mod_id, $display_text, $cm->indent, 
                        false, $is_hidden);
            }
        }

        $root_nodes = modnode::build($nodes);

        // Add a pseudo-node that is required for section-to-section movement
        // of modules
        $root_nodes = array_merge(array(
                new modnode($section . "-" . 0, '', 0, true)
            ), $root_nodes);


        return $root_nodes;
    }

    static function get_sections_modnodes($course_id, &$sections, &$mods,
            &$modinfo) {

        $sectionnodes = array();
        foreach ($sections as $section) {
            $sectionnodes[$section->id] = 
                self::mods_to_modnode_tree($section->section, 
                    $section, $mods, $modinfo, $course_id);
        }

        return $sectionnodes;
    }

    /**
     *  Takes an array of an array of modnode objects and renders in such
     *  a way that you get back an array of HTML.
     *  @return Array of HTML.
     **/
    static function render_set_modnodes(&$setmodnodes) {
        $rendered = array();
        foreach ($setmodnodes as $index => $modnodes) {
            $local = html_writer::start_tag('ul',
                array('class' => self::pagelistclass,
                    'id' => self::pagelist . '-' . $index));

            foreach ($modnodes as $modnode) {
                $local .= $modnode->render();
            }

            $local .= html_writer::end_tag('ul');

            $rendered[$index] = $local;
        }

        return $rendered;
    }

    /**
     *  Convenience function, returns an array of the HTML rendered
     *  UL and LI DOM Objects ready to be spit out into JSON.
     **/
    static function get_section_modules_rendered(&$course_id, &$sections, 
            &$mods, &$modinfo) {        
        $snodes = self::get_sections_modnodes($course_id, $sections, $mods,
            $modinfo);

        return self::render_set_modnodes($snodes);
    }

    /**
     *  Adds the required javascript files.
     *  This is the actual jQuery scripts along with the nestedsortable
     *  javascript files.
     **/
    static function javascript_requires() {
        global $CFG, $PAGE;

        $jspath = '/blocks/ucla_rearrange/javascript/';

        $PAGE->requires->js($jspath . 'jquery-1.6.2.min.js');
        $PAGE->requires->js($jspath . 'interface-1.2.min.js');
        if (debugging()) {
            $PAGE->requires->js($jspath . 'inestedsortable-1.0.1.js');
        } else {
            $PAGE->requires->js($jspath . 'inestedsortable-1.0.1.pack.js');
        }
    }
    
    /**
     *  Convenience function to generate a variable assignment 
     *  statement in JavaScript.
     **/
    static function js_variable_code($var, $val, $quote = true) {
        if ($quote) {
            $val = '"' . addslashes($val) . '"';
        }

        return 'M.block_ucla_rearrange.' . $var . ' = ' . $val;
    }

    /**
     *  Adds all the javascript code for the global PAGE object.
     *  This does not add any of the functionality calls, it just
     *  makes the NestedSortable API available to all those who dare
     *  to attempt to use it.
     **/
    static function setup_nested_sortable_js($sectionslist, $targetobj=null,
            $customvars=array()) {
        global $PAGE;

        // Include all the jQuery, interface and nestedSortable files
        self::javascript_requires();

        // This file contains all the library functions that we need
        $PAGE->requires->js('/blocks/ucla_rearrange'
            . '/javascript/block_ucla_rearrange.js');

        // These are all the sections, neatly split by section
        $PAGE->requires->js_init_code(self::js_variable_code(
            'sections', json_encode($sectionslist), false));

        // This is the jQuery query used to find the object to 
        // run NestedSortable upon.
        $PAGE->requires->js_init_code(self::js_variable_code(
            'targetjq', $targetobj));

        // This is a list of customizable variables, non-essential
        // Hooray for lack of convention, so I have to link each
        // of the PHP variables to its JavaScript variable!
        // Note to self: Try to stick with convention
        $othervars = array(
            'sortableitem' => self::sectionitem,
            'nestedsortableitem' => self::pageitem,
            'nonnesting' => self::nonnesting
        );

        // This API has some variables
        foreach ($othervars as $variable => $default) {
            $rhs = $default;
            if (isset($customvars[$variable])) {
                $rhs = $customvars[$variable];
            }

            $PAGE->requires->js_init_code(
                self::js_variable_code($variable, $rhs)
            );
        }

        // And what the hell you can spec out some of your own variables
        foreach ($customvars as $variable => $custom) {
            if (!isset($othervars[$variable])) {
                $PAGE->requires->js_init_code(
                    self::js_variable_code($variable, $custom)
                );
            }
        }

        $PAGE->requires->js_init_code(
            'M.block_ucla_rearrange.build_ns_config();'
        );
    }

    /** 
     *  Wraps around PHP native function parse_str();
     **/
    static function parse_serial($serializedstr) {
        parse_str($serializedstr, $returner);

        return $returner;
    }

    static function clean_section_order($sections) {
        if (empty($sections)) {
            return array();
        }

        $clean = array();
        foreach ($sections as $sectionold => $sectiontext) {
            // Accounting for the unavailablity of section 0
            $clean[$sectionold] = end(explode('-', $sectiontext));
        }

        return $clean;
    }
    
    /** 
     *  Moves a bunch of course modules to a different section
     *  There should already be a function for this, but there is not.
     *  @param $sectionmodules
     *      An Array of [ OLD_SECTION_DESTINATION ] 
     *          => Array ( MODULE->id, indent )
     *  @param $ordersections
     *      An Array of [ NEW_SECTION_ORDER ] => OLD_SECTION ID 
     **/
    static function move_modules_section_bulk($sectionmodules, 
            $ordersections=array()) {
        global $DB;

        // Split the arrary of oldsections with new modules into
        // an array of section sequences, and module indents?
        $coursemodules = array();
        $sections = array();

        foreach ($sectionmodules as $section => $modules) {
            $modulearr = array();
            $sectionarr = array();
            $sectseq = array();

            foreach ($modules as $module) {
                // Repitch the values
                foreach ($module as $k => $v) {
                    $modulearr[$k] = $v;
                }

                // This should never hit
                if (!isset($modulearr['id'])) {
                    print_error(get_string('error_module_consistency',
                        'block_ucla_rearrange'));

                    return false;
                }

                // Add section
                $modulearr['section'] = $section;

                // Create the sequence
                $sectseq[] = $modulearr['id'];

                $coursemodules[] = $modulearr;
            }

            // Get the sequence
            $sectionarr['sequence'] = trim(implode(',', $sectseq));
           
            // Move the section itself.
            if (isset($ordersections[$section])) {
                $sectionarr['section'] = $ordersections[$section];
            }

            $sectionarr['id'] = $section;

            // Save the new section
            $sections[$section] = $sectionarr;
        }

        foreach ($coursemodules as $module) {
            // Note the boolean at the end is not used in mysql 
            // (as of moodle 2.1.1) also, this always returns true...
            $DB->update_record('course_modules', $module, true);
        }

        foreach ($sections as $sectnum => $section) {
            $DB->update_record('course_sections', $section, true);
        }
    }

    static function ucla_cp_hook($course, $context) {
        global $CFG;

        $thispath = '/blocks/ucla_rearrange/rearrange.php';

        $allmods = array();
        $allmods[] = array(
            'item_name' => 'rearrange',
            'tags' => array('ucla_cp_mod_common'),
            'action' => new moodle_url($thispath, array(
                'course_id' => $course->id
            )),
            'required_cap' => 'moodle/course:update'
        );

        return $allmods;
    }
}

/**
 *  Class representing a nested-form of indents and modules in a section.
 **/
class modnode {
    var $modid;
    var $modtext;
    var $modindent;
    var $invis = false;
    var $is_hidden = false;

    var $children = array();

    /**
     * Constructor
     * 
     * @param type $id          ID of module
     * @param type $text        Text to display for node
     * @param type $indent      How far to indent node
     * @param type $invis       If true, then applies invisible class for node
     * @param type $is_hidden   If true, then adds in text to indicate if given
     *                          node/module if hidden
     */
    function __construct($id, $text, $indent, $invis=false, $is_hidden=false) {
        $this->modid = $id;
        $this->modtext = $text;
        $this->modindent = $indent;
        $this->invis = $invis;
        $this->is_hidden = $is_hidden;
    }

    function add_child(&$node) {
        $this->children[] =& $node;
    }

    function render() {
        $childrender = '';

        if (!empty($this->children)) {
            $insides = '';

            foreach ($this->children as $child) {
                $insides .= $child->render();
            }

            $childrender = html_writer::tag('ul', $insides, array(
                'class' => block_ucla_rearrange::pagelistclass
            ));
        }

        $class = block_ucla_rearrange::pageitem;
        if ($this->invis) {
            $class .= ' ' . block_ucla_rearrange::nonnesting;

        }

        $is_hidden_text = '';
        if ($this->is_hidden) {
            $is_hidden_text = ' ' . html_writer::tag('span', 
                    '(' . get_string('hidden', 'calendar') . ')', 
                    array('class' => block_ucla_rearrange::hiddenclass));
        }
        
        $self = html_writer::tag('li', $this->modtext . $is_hidden_text . 
                $childrender, array('id' => 'ele-' . $this->modid,
                                    'class' => $class
        ));
        
        return $self;
    }

    /**
     *  Translates a root nodes into a flattened list with indents.
     **/
    static function flatten($root, $indent=0) {
        $set = array();

        if (empty($root)) {
            return array();
        }

        foreach ($root as $node) {
            if (!$node['id']) {
                continue;
            }

            $node_indent = new stdclass();

            $node_indent->id = $node['id'];
            $node_indent->indent = $indent;
            
            $set[] = $node_indent;

            if (isset($node['children']) && !empty($node['children'])) {
                $return = self::flatten($node['children'], $indent + 1);

                $set = array_merge($set, $return);
            }
        }

        return $set;
    }

    /**
     *  Translates a flat list with indents into a set of root nodes.
     **/
    static function build(&$nodes) {
        $parent_stack = array();
        $root_nodes = array();

        // Take the numerated depth structure and get a nested tree
        foreach ($nodes as $index => &$node) {
            if (sizeof($parent_stack) == 0) {
                array_push($root_nodes, $node);
            } else {
                $indentdiff = $node->modindent - $nodes[$index - 1]->modindent;
                
                if ($indentdiff <= 0) {
                    // Goto the previous possible parent at the same 
                    // indentation level
                    for ($i = abs($indentdiff) + 1; $i > 0; $i--) {
                        array_pop($parent_stack);
                    }

                    if (sizeof($parent_stack) == 0) {
                        array_push($root_nodes, $node);
                    } else {
                        $nodes[end($parent_stack)]->add_child($node);
                    }
                } else {
                    $nodes[end($parent_stack)]->add_child($node);
                }
            }

            array_push($parent_stack, $index);
        }

        return $root_nodes;
    }
}
