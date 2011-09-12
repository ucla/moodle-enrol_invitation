<?php

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../moodleblock.class.php');

/**
 *  Rearrange library.
 *  Files relevant to using the rearranger.
 **/
class block_ucla_rearrange extends block_base {
    /**
     *  Returns an array of root modnode objects for a particular section.
     *  @param $section     The section number
     *  @param $sequence    The sequence of course modules in the section
     *  @param $mods        The list of mods from get_all_mods().
     *  @param $modinfo     The mod information from get_all_mods().
     **/
    static function mods_to_modnode_tree($section, &$sequence, &$mods, 
            &$modinfo, $course_id) {

        $sectionmods = explode(',', $sequence);
      
        $nodes = array();
        if (empty($sectionmods)) {
            return $nodes; 
        }

        foreach ($sectionmods as $mod_id) {
            if (isset($mods[$mod_id])) {
                $cm =& $mods[$mod_id];

                if ($cm->section != $section) {
                    // For some reason this instance seems to occur 
                    // intrinsically in Moodle.
                    // TODO Figure out why this happens and what we should do
                    // 'bout it.
                    debugging('Mismatching section for ' . $cm->name
                        . "(got {$cm->section} expecting $section)\n");
                    continue;
                }

                if ($cm->modname == 'label') {
                    $display_text = format_text($modinfo->cms[$mod_id]->extra,
                        FORMAT_HTML, array('noclean' => true));
                } else {
                    $display_text = format_string($modinfo->cms[$mod_id]->name,
                        true, $course_id);
                }

                $nodes[] = new modnode($mod_id, $display_text, $cm->indent);
            }
        }

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

    static function get_sections_modnodes($course_id, &$sequences, &$mods,
            &$modinfo) {

        $sectionnodes = array();
        foreach ($sequences as $section => $sequence) {
            $sectionnodes[$section] = 
                self::mods_to_modnode_tree($section, 
                    $sequence, $mods, $modinfo, $course_id);
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
            $local = '';
            foreach ($modnodes as $modnode) {
                $local .= $modnode->render();
            }

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
       
        $sequences = array();
        foreach ($sections as $sectnum => $section) {
            $sequences[$sectnum] = $section->sequence;
        }

        $snodes = self::get_sections_modnodes($course_id, $sequences, $mods,
            $modinfo);

        return self::render_set_modnodes($snodes);
    }


    /**
     *  Adds the required javascript files to the PAGE.
     **/
    static function javascript_requires() {
        global $CFG, $PAGE;

        $jspath = '/blocks/ucla_rearrange/javascript/';

        $PAGE->requires->js($jspath . 'jquery-1.6.2.min.js');
        $PAGE->requires->js($jspath . 'interface-1.2.min.js');
        $PAGE->requires->js($jspath . 'inestedsortable-1.0.1.pack.js');
    }
    
    /**
     *  Convenience function to generate a variable assignment 
     *  statement in JavaScript.
     *  TODO Might want to move this function to rearrange
     **/
    static function js_variable_code($var, $val, $quote = true) {
        if ($quote) {
            $val = '"' . $val . '"';
        }

        return 'M.block_ucla_rearrange.' . $var . ' = ' . $val;
    }
}

/**
 *  Class representing a nested-form of indents and modules in a section.
 **/
class modnode {
    const pagelist = 'page-list';
    const pageitem = 'page-item';

    var $modid;
    var $modtext;
    var $modindent;

    var $children = array();

    function __construct($id, $text, $indent) {
        $this->modid = $id;
        $this->modtext = $text;
        $this->modindent = $indent;
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
                'class' => self::pagelist
            ));
        }

        $self = html_writer::tag('li', $this->modtext . $childrender, array(
            'id' => 'ele-' . $this->modid,
            'class' => self::pageitem
        ));

        return $self;
    }
}
