<?php
/**
 * -----------------------------------------------------------------------------
 *
 * This file is part of the Course Menu block for Moodle
 *
 * The Course Menu block for Moodle software package is Copyright 2008
 * onwards NetSapiensis AB and is provided under the terms of the GNU GENERAL 
 * PUBLIC LICENSE Version 3 (GPL). This program is free software: you can 
 * redistribute it and/or modify it under the terms of the GNU General Public 
 * License as published by the Free Software Foundation, either version 3 of 
 * the License, or (at your option) any later version.
 *
 * This program is free software: you can redistribute it and/or modify it 
 * under the terms of the GNU General Public License as published by the Free 
 * Software Foundation, either version 3 of the License, or (at your option) 
 * any later version. This program is distributed in the hope that it will be 
 * useful, but WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * See the GNU General Public License for more details. You should have 
 * received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/>.
 * -----------------------------------------------------------------------------
 **/

/**
 *  This is the course menu block, written by "NetSapiensis."
 *
 **/

class block_course_menu extends block_base {
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
   
    /**
     *  Overrides parent.
     **/
    function init() {
        global $CFG;
        
        $this->blockname = get_class($this);
        $this->title = get_string('pluginname', $this->blockname);
    }

    /**
     *  Moodle overridden.
     **/
    function instance_allow_multiple() {
        return false;
    }

    /**
     *  Moodle overridden.
     **/
    function instance_allow_config() {
        return true;
    }
    
    /**
     *  Moodle overridden.
     **/
    function has_config() {
        return true;
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
            $courseformat = $this->course->format;
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
     *  Overrides parent function.
     *  This is called to display the actual block.
     **/
    function get_content() {
        global $CFG, $USER, $DB, $OUTPUT;
        
        if (isset($this->content) && !empty($this->content)) {
            return $this->content;
        }
        
        $this->course = $this->page->course;

        $this->check_default_config();        

        $sections = $this->get_sections();

        $format_rk = $this->get_topic_get();
        $displaysection = optional_param($format_rk, 0, PARAM_INT);
        if ($displaysection < 0) {
            $displaysection = course_get_display($this->course->id);
        }

        // Make all the display names pretty
        foreach ($sections as $k => $section) {
            $sections[$k]['trimmed_name'] = $this->trim($section['name']);

            foreach ($section['resources'] as $l => $resource) {
                $sections[$k]['resources'][$l]['trimmed_name'] = 
                    $this->trim($resource['name']);
            }
        }

        $chapters = $this->config->chapters;
     
        $renderer = $this->page->get_renderer('block_course_menu');
        $output = html_writer::start_tag('div', array(
            'class' => 'block_navigation'
        ));

        $lis = '';
        $linkindex = 0;

        $expansionlimit = 1;

        foreach ($this->config->elements as $element) {
            if (!$element['visible']) {
                continue;
            }

            $eleid = $element['id'];
            $elename = $element['name'];
            $eleicon = $this->get_icon($element, $renderer);

            $leafurl = null;
            $attrs = array();

            switch ($eleid) {
            case 'tree': 
                // build chapter / subchapter / topic /
                // week structure
                $lis .= $renderer->render_chapter_tree(
                    $this->instance->id, 
                    $this->config, 
                    $chapters,  // Is this line necessary?
                    $sections, 
                    $displaysection
                );

                break;
            case 'showallsections':
                // If -1 is not handled, then it should default to 0
                $leafurl = $CFG->wwwroot . '/course/view.php?id='
                    . $this->course->id . '&' . $format_rk . '=-1';

                $attrs = array('id' => $eleid);

                break;
            case 'calendar':
                $leafurl = $CFG->wwwroot 
                    . "/calendar/view.php?view=upcoming&course=" 
                    . $this->course->id;

                break;
            case 'showgrades':
                $leafurl = $CFG->wwwroot . "/grade/index.php?id="
                    . $this->course->id;

                break;
            default:
                if (substr($eleid, 0, 4) == 'link') {
                    // This isn't used yet, but it may be used later.
                    $lis .= $renderer->render_link(
                        $this->config->links[$linkindex], 
                        $this->course->id);
                    $linkindex++;
                } else if ($this->is_block_element($eleid)) {
                    if (!class_exists($eleid)) {
                        debugging('Class: ' . $eleid . ' not found!');
                    } else {
                        $leafurl = $eleid::get_action_link($this->course);
                    }
                } else {
                    debugging('Could not respond to item: ' . $eleid);
                }
            }

            // Render something if we need to
            if ($leafurl !== null) {
                $lis .= $renderer->render_leaf($elename, $eleicon, 
                    $attrs, $leafurl);
            }
        }

        $output .= html_writer::tag('ul', $lis, 
            array('class' => 'block_tree list'));

        $output .= html_writer::end_tag('div');
        
        $this->contentgenerated = true;
        $this->content->text = $output;
        
        return $this->content;
    }
    
    /**
     *  Initializes the basic chapters setup.
     *  Affects the configuration structure.
     **/
    function init_chapters() {
        // TODO see if all these configurations are essential
        $this->config_set('chapenable', 0);
        $this->config_set('subchapenable', 0);
        $this->config_set('subchapterscount', 1);

        $chapter = array();
        $chapter['name']  = get_string("chapter", $this->blockname)." 1";

        $child = array();
        $child['type'] = "subchapter";
        $child['name'] = get_string("subchapter", $this->blockname) . " 1";
        $child['count'] = count($this->get_sections());
        $chapter['childelements'] = array($child);

        $this->config_set('chapters', array($chapter));

        $this->check_redo_chaptering($child['count']);
    }

    /**
     *  Returns the corresponding display information for an element in the 
     *  tree.
     *  @todo add more icons if wanted...
     **/
    function get_icon($element, $renderer) {
        global $OUTPUT;

        $iconinfo = null;

        switch($element['id']) {
        case 'calendar':
            $iconinfo = array('cal', $this->blockname);
            break;
        default:
            return '';
        }

        $img_src = '';
        if ($iconinfo != null) {
            $img_src = call_user_func_array(
                array($OUTPUT, 'pix_url'),
                $iconinfo
            );

            $icon = $renderer->icon(
                $img_src, $element['name'], 
                array('class' => 'smallicon')
            );
        } else {
            return '';
        }

        return $icon;
    }

    /**
     *  Set up the configuration for this block to have a set of minimal
     *  configuration options.
     **/
    function update_config($save_it=true) {
        global $CFG, $USER, $OUTPUT;

        // elements
        $elements = $this->create_all_elements();

        // Add elements that are not already in the configuration.
        if (!isset($this->config->elements)) {
            $this->config_set('elements', $elements);
        } else {
            $indexed = array();
            foreach ($this->config->elements as $ele) {
                $indexed[$ele['id']] = true;
            }

            foreach ($elements as $newel) {
                if (!isset($indexed[$newel['id']])) {
                    $this->config->elements[] = $newel;
                }
            }
        }
       
        $this->init_chapters();

        if ($save_it && isset($this->instance)) {
            $this->instance_config_commit();
        }
    }

    /**
     *  Creates a set of all possible elements.
     *  Convenience function.
     **/
    function create_all_elements() {
        $elements = $this->create_default_elements();
        $block_elements = $this->create_block_elements();

        return array_merge($elements, $block_elements);
    }

    /**
     *  Fetches the hard-coded defaults for each of the elements that can be
     *  displayed in the block.
     **/
    function create_default_elements() {
        // elements
        $elements = array();

        $elements[] = $this->create_element('tree', 0, 1);
        $elements[] = $this->create_element('showallsections', 1, 1);
        $elements[] = $this->create_element('calendar');

        if ((isset($this->course->showgrades)) && ($this->course->showgrades)) {
            $elements[] = $this->create_element('showgrades');
        }

        // This next one is very important
        $elements[] = $this->create_element('sitepages');

        $elements[] = $this->create_element('myprofile');
        $elements[] = $this->create_element('mycourses');
        $elements[] = $this->create_element('myprofilesettings');
        $elements[] = $this->create_element('courseadministration');

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
        $elements = array();

        foreach ($allblocks as $block) {
            $classname = 'block_' . $block->name;

            if (!class_exists($classname)) {
                @include_once($CFG->dirroot . '/blocks/' . $block->name . '/' 
                        . $classname . '.php');
            }

            if (method_exists($classname, 'get_action_link')) {
                $elements[] = $this->create_element($classname);
            }
        }

        return $elements;
    }

    /**
     *  This defines the default configuration of all a new instance, if it
     *  has no configurations already within it.
     **/
    function check_default_config($commit=true) {
        global $CFG;
        
        if (empty($this->config) || !is_object($this->config)) {
            if (!empty($CFG->block_course_menu_global_config)) {
                $this->config = 
                    unserialize($CFG->block_course_menu_global_config);
            }
        }

        $this->update_config($commit);
        $this->init_chapters();
    }

    /**
     *  Restructures chaptering if the number of sections changed.
     *  Currently obsolete and not used.
     **/
    function check_redo_chaptering($sectcount) {
        // redo chaptering if the number of the sections changed
        $sumchapsections = 0;
        $subchapcount = 0;
        $chapcount = 0;

        if (empty($this->config->chapters)) {
            $this->init_chapters();
        }

        $chapters =& $this->config->chapters;

        // Count the number of subchapters and sections
        foreach ($chapters as $chapter) {
            foreach ($chapter['childelements'] as $child) {
                if ($child['type'] == "subchapter") {
                    $subchapcount ++;
                    $sumchapsections += $child['count'];
                } else {
                    $sumchapsections ++;
                }
            }

            $chapcount++;
        }
       
        // This means our chapters are divided incorrectly
        if ($sumchapsections == $sectcount) {
            return true;
        }

        $divyupchapters = ($chapcount <= $sectcount);
        if ($divyupchapters) {
            // We want enough chapters to hold our sections
            $sectionsperchapter = ceil($sectcount / $chapcount);
            $chapcountminusone = $chapcount - 1;
        }

        for ($i = 0; $i < $chapcount; $i++) {
            $j = 0;
            while ($chapters[$i]['childelements'][$j]['type'] == "topic") {
                $j++;
            }

            $newchild = $chapters[$i]['childelements'][$j];

            if ($diyupchapters) {
                // Parition sections evenly, unless there are not enough 
                // sections left to partition
                $sectionsinchapter = $i < $chapcountminusone 
                    ? $sectionsperchapter 
                    : $sectcount - $sectionsperchapter * $chapcountminusone;
            } else {
                $sectionsinchapter = 1;
            }

            $newchild['count'] = $sectionsinchapter;
            $chapters[$i]['childelements'] = array();
            $chapters[$i]['childelements'][0] = $newchild;
        }

        // Eliminate empty chapters
        for ($i = $sectcount; $i < $chapcount; $i++) {
            unset($this->config->chapters[$i]);
        }

        $this->config->subchapterscount = count($chapters);
    }

    // truncates the description to fit within the given $max_size. 
    // Splitting on tags and \n's where possible
    // @param $string: string to truncate
    // @param $max_size: length of largest piece when done
    // @param $trunc: string to append to truncated pieces
    function truncate_description($string, $max_size=20, $trunc='...') {
        $split_tags = array('<br>', '</dt>', '</p>', '<br />');

        $first = strtolower($string);

        // We are only going to grab the text up to the first newline
        foreach($split_tags as $tag) {
            $group = explode($tag, $first);

            $first = $group[0];
            if ($first == '') {
                $first = $group[1];
            }
        }
        
        $clean = strip_tags($first);
        $rstring = trim(html_entity_decode($clean));

        // This means that the first line is too long
        if (strlen($rstring) > $max_size) {
            // We're going to attempt to split the string automatically
            // with "\n"
            $rstring = chunk_split($rstring, 
                ($max_size - strlen($trunc)), "\n");

            $temp = explode("\n", $rstring);
            $tempcnt = count($temp);
            for ($i = 0; $i < $tempcnt; $i++) {
                $trimmed = trim($temp[$i]);

                if ($trimmed != '') {
                    break;
                }
            }

            // We split, but for some magical reason it is still too long
            if (strlen($trimmed) > $max_size) {
                $rstring = substr($rstring, 0, 
                    ($max_size - strlen($trunc))) . $trunc;
            } 
        }

        $clean = strip_tags($string);
        $fstring = html_entity_decode($clean);

        $fstring = substr($fstring, 0, strlen($rstring));

        // single quotes need escaping
        return str_replace("'", "\\'", $fstring);
    }

    /**
     *  Removes all instances of newline and carriage return.
     *  @param string
     *  @return string The cleaned up string.
     **/
    function clear_enters($string) {
        $newstring = str_replace(array(chr(13), chr(10)), 
            array(' ', ' '), $string);
        return $newstring;
    }

    /**
     *  Takes each of the arguments passed int the function and turns them
     *  into an array.
     *  @param $id
     *  @param $canhide
     *  @param $visible
     *  @param $expandable
     *  @return Array All the params folded into an array.
     **/
    function create_element($id, $canhide=1, $visible=0, $expandable=0) {
        $elem = array();
        $elem['id']         = $id;
        $elem['name']       = $this->get_name($id);
        $elem['canhide']    = $canhide;
        $elem['visible']    = $visible;
        $elem['expandable'] = $expandable;
    
        return $elem;
    }

    /**
     *  This creates an element of configuration data.
     **/
    function config_set($key, $data=null, $overwrite=true) {
        if (!isset($this->config)) {
            $this->config = new stdclass();
        }
        
        if ($data !== null && $overwrite) {
            $this->config->$key = $data;
        } else if (!isset($this->config->$key)) {
            $this->config->$key = array();
        }
    }

    /**
     *  Converts a certain idcode into a coherent moodle string.
     *  @param $elementid The element code.
     *  @return string The Moodle string.
     **/
    function get_name($elementid) {
        global $DB;

        if (isset($this->page->course)) {
            $format = $this->page->course->format;
        } else if (isset($this->instance->pageid)) {
            $course = $DB->get_record('course', 
                array('id' => $this->instance->pageid));
            $format = $course->format;
        } else {
            $format = false;
        }

        switch ($elementid) {
        case 'calendar':     
            return get_string('calendar','calendar');
        case 'showgrades':   
            return get_string('gradebook', 'grades');
        case 'sectiongroup': 
            return get_string("name" . $format);
        case 'tree':
            // Only if somehow we couldn't figure out the format.
            if (!$format) {
                return get_string('topicsweeks', $this->blockname);
            }

            return get_string('sectionname', 'format_' . $format);
        default:
            if (strstr($elementid, "link") !== false) {
                return get_string("link", $this->blockname);
            }

            if ($this->is_block_element($elementid)) {
                return get_string('pluginname', $elementid);
            }

            // Global strings
            if (in_array($elementid, 
                    array('sitepages', 'mycourses', 'myprofile'))) {
                return get_string($elementid);
            }

            return get_string($elementid, $this->blockname);
        }
    }

    /**
     *  Gets the sections of the course, populated with each of their
     *  resources for the course.
     *  @return mixed The sections, with resources.
     **/
    function get_sections() {
        global $CFG, $USER, $DB, $OUTPUT;
      
        if (isset($this->sectionscache)) {
            return $this->sectionscache;
        }

        if (empty($this->instance)) {
            return array();
        }
        
        $this_format = $this->get_course_format();

        // These don't really have sections, I guess...
        if ($this_format == 'social' || $this_format == 'scorm') {
            return array();
        }
        
        $fullformat = 'format_' . $this_format;

        $genericname = get_string('sectionname', $fullformat);

        $course_id = $this->course->id;

        get_all_mods($course_id, $mods, $modnames, $modnamesplural, 
            $modnamesused);
        
        $context = get_context_instance(CONTEXT_COURSE, $course_id);
        $canview = has_capability('moodle/course:viewhiddensections', $context);
       
        $get_param = $this->get_topic_get();

        $allsections = get_all_sections($course_id);

        if (get_string_manager()->string_exists('section0name', $fullformat)) {
            $sectionzeroname = get_string('section0name', $fullformat);
        }
        
        $sections = array();
        $modinfo = unserialize($this->course->modinfo);

        $filterall = !empty($CFG->filterall);

        $coursenumsections = $this->course->numsections;

        foreach ($allsections as $k => $section) {
            // get_all_sections() may return sections that are in the 
            // db but not displayed because the number of the sections 
            // for this course was lowered - bug [CM-B10]
            if ($k > $coursenumsections) {
                break;
            }

            if (empty($section)) {
                continue;
            }

            $newsec = array();
            $newsec['visible'] = $section->visible;

            if ($k == 0 && isset($sectionzeroname)) {
                $strsummary = trim($sectionzeroname);
            } else if (!empty($section->name)) {
                $strsummary = trim($section->name);
            } else {
                // just a default name
                $strsummary = ucwords($genericname) . " " . $k;
            }

            // Clean up the text
            $strsummary = trim($this->clear_enters($strsummary));

            // Shorten the text to displayable levels
            $newsec['name'] = $strsummary;

            // The URL link for the section 
            $newsec['url'] = $CFG->wwwroot 
                . "/course/view.php?id=" . $course_id 
                . '&' . $get_param . '=' . $k;

            $sectionmods = explode(",", $section->sequence);

            $newsec['resources'] = array();

            // Pile on each of the sections modules
            foreach ($sectionmods as $modnumber) {
                if (empty($mods[$modnumber])) {
                    continue;
                }

                $mod = $mods[$modnumber];
                if (!$mod->visible) {
                    continue;
                }

                // Do nothing for labels
                if ($mod->modname == 'label') {
                    continue;
                }

                $instancename = urldecode($modinfo[$modnumber]->name);
                $modname = $mod->modname;

                // Try to clean up the name of the instance.
                if ($filterall) {
                    $instancename = filter_text($instancename, $course_id);
                }

                // Backup name for section module.
                if (strlen(trim($instancename)) === 0) {
                    $instancename = $mod->modfullname;
                }

                // Special treatment for resources.
                if ($modname == 'resource') {
                    $info = resource_get_coursemodule_info($mod);
                    $instancename = $info->name;
                }
                
                $instancename = $this->truncate_description($instancename, 200);

                $resource = array();

                $resource['name'] = $instancename;
                $resource['url'] = $CFG->wwwroot . '/mod/' . $modname 
                    . '/view.php?id=' . $mod->id;

                $icon = $OUTPUT->pix_url("icon", $modname);
                if (is_object($icon)) {
                    $resource['icon'] = $icon->__toString();
                } else {
                    $resource['icon'] = $icon;
                }

                $newsec['resources'][] = $resource;
            }

            // hide hidden sections from students if the course settings 
            // say that - bug #212
            if ($canview || $section->visible == 1) {
                $sections[] = $newsec;
            }
        }

        // get rid of the first one (it's the zero section)
        //array_shift($sections);

        $this->sectionscache = $sections;
        return $sections;
    }

    /**
     *  Fetches a few icons.
     *  @return Array The icons
     **/
    function get_link_icons() {
        global $CFG, $DB, $OUTPUT;

        // Let's cache this, this looks fairly expensive
        if (isset($this->icons)) {
            return $this->icons;
        }

        $icons = array();
        $icons[0]['name'] = get_string('noicon', $this->blockname);
        $icons[0]['img']  = '';

        $icons[1]['name'] = get_string('linkfileorsite', $this->blockname);
        $icons[1]['img']  = $OUTPUT->pix_url('link', $this->blockname);

        $icons[2]['name'] = get_string('displaydirectory', $this->blockname);
        $icons[2]['img']  = $OUTPUT->pix_url('directory', $this->blockname);

        // This seem expensive
        $allmods = $DB->get_records("modules");
        foreach ($allmods as $mod) {
            $icon = array();
            $icon['name'] = get_string("modulename", $mod->name);
            $obj = $OUTPUT->pix_url('icon', $mod->name);
            if (is_object($obj)) {
                $icon['img']  = $obj->__toString();
            } else {
                $icon['img'] = '';
            }

            $icons[] = $icon;
        }

        $this->icons = $icons;
        return $icons;
    }

    /**
     *  Truncates the string with elipses, depending on the configuration
     *  as to where the elipses go to.
     *  @param string
     *  @return string The truncated string.
     **/
    function trim($str) {
        $mode = get_config($this->blockname, 'trimmode');
        $length = get_config($this->blockname, 'trimlength');

        if (!empty($this->config->trimmode)) {
            $mode = (int)$this->config->trimmode;
        }

        if (!empty($this->config->trimlength)) {
            $length = (int)$this->config->trimlength;
        }

        $textlib = textlib_get_instance();

        switch ($mode) {
        case self::TRIM_RIGHT :
            $mode_str = 'right';
            break;
        case self::TRIM_LEFT :
            $mode_str = 'left';
            break;
        case self::TRIM_CENTER :
            $mode_str = 'center';
            break;
        }

        if ($textlib->strlen($str) > ($length + 3)) {
            $fn_name = 'trim_' . $mode_str;

            return $this->$fn_name($textlib, $str, $length);
        }

        return $str;
    }

    /**
     * Truncate a string from the left
     * @param textlib $textlib
     * @param string $string The string to truncate
     * @param int $length The length to truncate to
     * @return string The truncated string
     **/
    protected function trim_left($textlib, $string, $length) {
        return '...' 
            . $textlib->substr($string, $textlib->strlen($string) - $length);
    }

    /**
     * Truncate a string from the right
     * @param textlib $textlib
     * @param string $string The string to truncate
     * @param int $length The length to truncate to
     * @return string The truncated string
     **/
    protected function trim_right($textlib, $string, $length) {
        return $textlib->substr($string, 0, $length) . '...';
    }

    /**
     * Truncate a string in the center
     * @param textlib $textlib
     * @param string $string The string to truncate
     * @param int $length The length to truncate to
     * @return string The truncated string
     **/
    protected function trim_center($textlib, $string, $length) {
        $trimlength = ceil ($length / 2);
        $start = $textlib->substr($string, 0, $trimlength);
        $end = $textlib->substr($string, $textlib->strlen($string) - $trimlength);
        $string = $start . '...' . $end;
        return $string;
    }

    /**
     *  Called from within the editing form.
     *  Displays the editing section for each element that can be displayed
     *  within the block.
     **/
    function config_elements() {
        global $CFG, $USER, $OUTPUT;
        
        $this->course = $this->page->course;
        $this->check_default_config();

        ob_start();
        include ("{$CFG->dirroot}/blocks/course_menu/config/elements.php");
        $cc = ob_get_contents();
        ob_end_clean();
        return $cc;
    }

    /**
     *  Called by Moodle, when going to site administration -> 
     *  plugins -> blocks -> course menu
     *  And every page, called by settings.php
     **/
    function output_global_config() {
        global $CFG, $THEME, $OUTPUT, $PAGE;

        // required stuff
        $icons = $this->get_link_icons();

        $this->check_default_config(false);
        
        ob_start();
        //include ("{$CFG->dirroot}/blocks/course_menu/config/global.php");
        $cc = ob_get_contents();
        ob_end_clean();

        return $cc;
    }

    /**
     *  Called by moodle whenever saving a block's configuration, usually
     *  by turning editing on, and then editing the block, then saving
     *  the changes.
     *
     *  This is overwritten because of some of the custom form data.
     **/
    function instance_config_save($data, $nolongerused=false) {
        // This is to compensate for the custom javascript used
        // within the configuration settings
        $ele_ids = optional_param('ids', false, PARAM_RAW);
        $ele_vis = optional_param('visibles', false, PARAM_RAW);
        
        if ($ele_ids !== false) {
            $new_elements = array();

            $default_elements = $this->create_all_elements();
            $indexed = array();

            foreach ($default_elements as $de) {
                $indexed[$de['id']] = $de;
            }

            // Empty the old one
            $this->config_set('elements', array());

            $elements = array();
            foreach ($ele_ids as $index => $id) {
                $def = $indexed[$id];
                $elements[] = $this->create_element($id, $def['canhide'], 
                        $ele_vis[$index], $def['expandable']);
            }

            $data->elements = $elements;
        }

        return parent::instance_config_save($data, $nolongerused);
    }

    /**
     *  Verifies that an element exists within the configuration.
     *  Or elsewhere.
     *  @param $id string The 'id' of the element.
     *  @param $destination Array The array to search through.
     *      Set as null to search this configuration.
     *  @return int, false Returns the index of the element or
     *      false if the element was not found.
     **/
    function element_exists($id, $destination=null) {
        if ($destination === null) {
            if (!isset($this->config->elements)) {
                return false;
            }

            $destination = $this->config->elements;
        }

        foreach ($destination as $location => $element) {
            if ($element['id'] == $id) {
                return $location;
            }
        }

        return false;
    }

    /**
     *  Checks if the element is a modular block thing.
     **/
    function is_block_element($element_id) {
        return (substr($element_id, 0, 6) == 'block_');
    }
}

// EOF
