<?php
/**
 *
 **/
class block_ucla_course_menu extends block_base {
    /** @var int Trim characters from the right **/
    const TRIM_RIGHT = 1;

    /** @var int Trim characters from the left **/
    const TRIM_LEFT = 2;

    /** @var int Trim characters from the center **/
    const TRIM_CENTER = 3;

    /** @var int Trim length that is hard-coded default **/
    const DEFAULT_TRIM_LENGTH = 10;

    /** @var int TODO No idea **/
    const EXPANDABLE_TREE = 0;

    var $contentgenerated = false;
   
    /**
     *  Called by Moodle.
     **/
    function init() {
        global $CFG;
        
        $this->blockname = get_class($this);
        $this->title = get_string('pluginname', $this->blockname);
    }

    /**
     *  Called by Moodle.
     **/
    function instance_allow_multiple() {
        return false;
    }

    /**
     *  Called by Moodle.
     **/
    function instance_allow_config() {
        return true;
    }
    
    /**
     *  Called by Moodle.
     **/
    function has_config() {
        return true;
    }

    /**
     *  Called by Moodle.
     **/
    function applicable_formats() {
        return array(
            'course' => true
        );
    }

    /**
     *  Returns the path to the format file.
     *  @todo this function shouldn't be here...
     **/
    function course_format_file($format) {
        global $CFG;

        return $CFG->dirroot . "/course/format/$format/lib.php";
    }

    /**
     *  Includes the source for the format file library.
     *
     *  @param $courseformat The format to attempt to load.
     **/
    function get_course_format($courseformat=null) {
        if ($courseformat === null) {
            $courseformat = $this->page->course->format;
        }

        $formatfile = $this->course_format_file($courseformat);
        if (file_exists($formatfile)) {
            require_once($formatfile);
        } else {
            // This format always exists, so use this!
            $courseformat = 'topics';
            $formatfile = $this->course_format_file($courseformat);
            require_once($formatfile);
        }

        return $courseformat;
    }

    /**
     *  Gets the GET param that is used to describe which topic
     *  you are viewing for a particular course format.
     *
     *  @todo this function shouldn't be here...
     *  @return string
     **/
    function get_topic_get() {
        $courseformat = $this->get_course_format();
        $fn = 'callback_' . $courseformat . '_request_key';

        if (function_exists($fn)) {
            $format_rk = $fn();
        } else {
            // Just assume it is topic
            $format_rk = callback_topics_request_key();

            debugging('Could not access GET param for format! Using [' 
                . $format_rk . ']');
        }

        return $format_rk;
    }
    
    /**
     *  Called by Moodle.
     **/
    function get_content() {
        if ($this->contentgenerated === true) {
            return $this->content;
        }

        $elements = $this->create_all_elements();

        $renderer = $this->get_renderer();

        $this->content->text = $renderer->navigation_node($elements,
            array('class' => 'block_tree list'));

        $this->contentgenerated = true;

        return $this->content;
    }
    
    /**
     *  Creates a set of all possible elements.
     *  Convenience function.
     **/
    function create_all_elements() {
        $elements = $this->create_section_elements();
        $block_elements = $this->create_block_elements();
        $module_elements = $this->create_module_elements();

        return array_merge($elements, $block_elements, $module_elements);
    }

    /**
     *  Fetches the hard-coded defaults for each of the elements that can be
     *  displayed in the block.
     **/
    function create_section_elements() {
        $course_id = $this->page->course->id;

        // Create section links
        $sections = get_all_sections($course_id);

        // the elements
        $elements = array();

        $topic_param = $this->get_topic_get();

        $viewhiddensections = has_capability(
            'moodle/course:viewhiddensections', $this->page->context);

        foreach ($sections as $section) {
            if (empty($section->name)) {
                $section->name = get_section_name($this->page->course, 
                    $section);
            }

            if (!$viewhiddensections && !$section->visible) {
                continue;
            }

            $sectnum = $section->section;
            $key = 'section-' . $sectnum;
            $elements[$key] = navigation_node::create($section->name,
                new moodle_url('/course/view.php', array(
                    'id' => $course_id,
                    $topic_param => $sectnum
                )), navigation_node::TYPE_SECTION);
        }

        if (defined('UCLA_FORMAT_DISPLAY_ALL')) {
            // Create view-all section link
            $elements['view-all'] = navigation_node::create(
                get_string('show_all', 'format_ucla'),
                new moodle_url('/course/view.php', array(
                    'id' => $course_id,
                    $topic_param => UCLA_FORMAT_DISPLAY_ALL
                )), navigation_node::TYPE_SECTION);
        }

        return $elements;
    }

    /**
     *  Iterates through the blocks and attempts to generate course menu
     *  items.
     **/
    function create_block_elements() {
        global $CFG;

        $elements = array();

        if (!isset($this->page)) {
            return $elements;
        }

        $allblocks = $this->page->blocks->get_installed_blocks();
        $course = $this->page->course;
        $elements = array();

        foreach ($allblocks as $block) {
            $classname = 'block_' . $block->name;

            if (!class_exists($classname)) {
                @include_once($CFG->dirroot . '/blocks/' . $block->name . '/' 
                        . $classname . '.php');
            }

            if (method_exists($classname, 'get_navigation_nodes')) {
                $block_elements = 
                    $classname::get_navigation_nodes($course);

                $elements = array_merge($elements, $blocks_elements);
            }
        }

        return $elements;
    }

    function create_module_elements() {
        global $CFG;

        $courseid = $this->page->course->id;

        get_all_mods($courseid, $mods, $modnames,
            $modnamesplural, $modnamesused);

        if (isset($modnamesused['label'])) {
            unset($modnamesused['label']);
        }

        $navigs = array();

        // Generate these damn navigation nodes
        foreach ($modnamesused as $modname => $modvisible) {
            $modpath = '/mod/' . $modname . '/index.php';

            if (file_exists($CFG->dirroot . $modpath)) {
                $modnameshown = $modnamesplural[$modname];
                $navigs[] = navigation_node::create($modnameshown, 
                    new moodle_url($modpath, array('id' => $courseid)),
                    navigation_node::TYPE_ACTIVITY);

            }
            
        }

        return $navigs;
    }

    /**
     *  Checks if the element is a modular block thing.
     **/
    function is_block_element($element_id) {
        return (substr($element_id, 0, 6) == 'block_');
    }

    /**
     *  Convenience function to get renderer.
     **/
    function get_renderer() {
        if (!isset($this->page)) {
            throw new moodle_exception();
        }

        return $this->page->get_renderer('block_ucla_course_menu');
    }
}

// EOF
