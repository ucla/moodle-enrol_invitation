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
 *  TODO
 *  - Move all graphics to pix/.
 *  - Consolidate code properly.
 **/
require_once($CFG->dirroot.'/mod/resource/lib.php');

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
   
    /** Cache maintainer **/
    private $contentgenerated = false;
    
    /*
     *  Overrides parent function.
     **/
    function init() {
        global $CFG;
        
        $this->blockname = get_class($this);
        $this->title = get_string('pluginname', $this->blockname);
    }

    /**
     *  Overrides parent function.
     **/
    function instance_allow_multiple() {
        return false;
    }

    /**
     *  Overrides parent function.
     **/
    function instance_allow_config() {
        return true;
    }

    /**
     *  Returns the path to the format file.
     **/
    function course_format_file($format) {
        global $CFG;

        return $CFG->dirroot . "/course/format/$format/lib.php";
    }

    /**
     *  Gets the GET param that is used to describe which topic
     *  you are viewing for a particular course format.
     *
     *  @return string
     **/
    function get_topic_get() {
        $courseformat = $this->course->format;

        // Attempt to load the file (if it is not already loaded) 
        $formatfile = $this->course_format_file($courseformat);
        if (file_exists($formatfile)) {
            require_once($formatfile);
        } else {
            $courseformat = 'topic';
            $formatfile = $this->course_format_file($courseformat);
            require_once($formatfile);
        }

        $fn = 'callback_' . $courseformat . '_request_key';
        if (function_exists($fn)) {
            $format_rk = $fn();
        } else {
            // Just assume it is topic
            $format_rk = 'topic';

            debugging('Could not find the GET parameter for section!');
            // Or crash and burn...
        }

        return $format_rk;
    }
    
    /**
     *  Overrides parent function.
     **/
    function get_content() {
        global $CFG, $USER, $DB, $OUTPUT;
        
        if ($this->contentgenerated) {
            return $this->content;
        }
        
        $this->course = $this->page->course;

        $this->check_default_config();
        
        if (!$this->element_exists('sitepages')) {
            $this->init_default_config();
        }
        
        $sections = $this->get_sections();
        
        $this->page->navigation->initialise();
        $navigation = array(clone($this->page->navigation));
        $node_collection = $navigation[0]->children;
        $settings = $this->page->settingsnav->children;
        
        $format_rk = $this->get_topic_get();
        $displaysection = optional_param($format_rk, 0, PARAM_INT);
        if ($displaysection < 0) {
            $displaysection = course_get_display($this->course->id);
        }

        // section names
        foreach ($sections as $k => $section) {
            $sections[$k]['trimmed_name'] = $this->trim($section['name']);

            foreach ($section['resources'] as $l => $resource) {
                $sections[$k]['resources'][$l]['trimmed_name'] = 
                    $this->trim($resource['name']);
            }
        }

        // links
        $links = $this->config->links;
        
        $sectCount = count($sections);
        $this->check_redo_chaptering($sectCount);
        
        $chapters = $this->config->chapters;
        $sumSection = 0;
        $found = false;
        
        $expandable = array();
       
        // render output
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
            $elename = $this->get_name($eleid);
            $eleicon = $element['icon'];

            $img_src = '';
            if ($eleicon != '') {
                $img_src = call_user_func_array(
                    array($OUTPUT, 'pix_url'),
                    $element['icon']
                );
            }

            $icon = $renderer->icon(
                $img_src, $elename, 
                array('class' => 'smallicon')
            );

            switch ($eleid) {
            case 'tree': 
                // build chapter / subchapter / topic /
                // week structure
                $lis .= $renderer->render_chapter_tree(
                    $this->instance->id, 
                    $this->config, 
                    $chapters, 
                    $sections, 
                    $displaysection
                );

                break;
            case 'showallsections':
                $lis .= $renderer->render_leaf(
                    $elename, 
                    $icon,
                    array('id' => $eleid),
                    $CFG->wwwroot . '/course/view.php?id=' 
                        . $this->course->id . '&' . $format_rk . '=0'
                );
                break;
            case 'calendar':
                $element['url'] = $CFG->wwwroot 
                    . "/calendar/view.php?view=upcoming&course=" 
                    . $this->course->id;
                $lis .= $renderer->render_leaf(
                    $element['name'], 
                    $icon, 
                    array(), 
                    $element['url']
                );
                break;
            case 'showgrades':
                $elements[$k]['url'] = $CFG->wwwroot 
                    . "/grade/index.php?id=".$this->course->id;
                $lis .= $renderer->render_leaf($element['name'], $icon, 
                    array(), $element['url']);
                break;
            default:
                if (substr($eleid, 0, 4) == 'link') {
                    $lis .= $renderer->render_link(
                        $this->config->links[$linkindex], $this->course->id);
                    $linkindex++;
                } 
            }
        }

        $output .= html_writer::tag('ul', $lis, 
            array('class' => 'block_tree list'));

        $output .= '</div>';
        
        $this->contentgenerated = true;
        $this->content->text = $output;
        
        return $this->content;
    }
    
    /**
     *  Initializes the basic chapters setup.
     **/
    function init_chapters() {
        $this->config->chapenable         = 0;
        $this->config->subchapenable      = 0;
        $this->config->subchapterscount   = 1;

        $chapter = array();
        $chapter['name']  = get_string("chapter", $this->blockname)." 1";

        $child = array();
        $child['type'] = "subchapter";
        $child['name'] = get_string("subchapter", $this->blockname) . " 1";
        $child['count'] = count($this->get_sections());
        $chapter['childelements'] = array($child);

        $this->config->chapters = array($chapter);
    }

    /**
     *  This will build an empty configuration, which basically is the
     *  fallback-default.
     **/
    function init_default_config($save_it = true) {
        global $CFG, $USER, $OUTPUT;

        // elements -----------------------------
        $elements   = array();

        $e = 'tree';
        $elements[] = $this->create_element(
            $e, $this->get_name($e), '', 
            '', 
            0
        );

        // showallsections
        $e = 'showallsections';
        $elements[] = $this->create_element(
            $e, $this->get_name($e), "", 
            array('viewall', 'block_course_menu')
        );

        // calendar
        $e = 'calendar';
        $elements[] = $this->create_element(
           $e, $this->get_name($e), "",
           array('cal', 'block_course_menu'), 
           1, 0
        );

        // showgrades
        if ((isset($this->course->showgrades)) && ($this->course->showgrades)) {
            $e = 'showgrades';
            $elements[] = $this->create_element(
                $e, $this->get_name($e), "",
                array('i/grades'), 
                1, 0
            );
        }
        
        // site pages
        $e = 'sitepages';
        $elements []= $this->create_element(
            $e, get_string($e), '', 
            '', 
            1, 0, 1
        );
        
        // my profile
        $e = 'myprofile';
        $elements []= $this->create_element(
            $e, get_string($e), '', 
            '', 
            1, 0, 1
        );
        
        //my course
        $e = 'mycourses';
        $elements []= $this->create_element(
            $e, get_string($e), '', 
            '', 
            1, 0, 1
        );
        
        //my profile settings
        $e = 'myprofilesettings';
        $elements []= $this->create_element(
            $e, get_string($e, $this->blockname), '', 
            '', 
            1, 0, 1
        );
        
        //course administration
        $e = 'courseadministration';
        $elements []= $this->create_element(
            $e, get_string($e, $this->blockname), '', 
            '', 
            1, 0, 1
        );
        
        $config = new stdClass();
        $config->elements = $elements;

        // sections -------------------------------
        $sections = $this->get_sections();
        
        // chaptering -----------------------------
        $this->init_chapters();

        // links ----------------------------------
        $config->linksenable = 0;
        $config->links       = array();

        $config->expandableTree = self::EXPANDABLE_TREE;
        $config->trimmode       = self::TRIM_RIGHT;
        $config->trimlength     = self::DEFAULT_TRIM_LENGTH;

        $this->config = $config;

        if ($save_it) {
            $this->save_config_to_db();
        }
    }
   
    /**
     *  This saves the configuration of the object instance into the
     *  database.
     **/
    function save_config_to_db() {
        global $DB;

        return $DB->set_field('block_instances', 'configdata', 
            base64_encode(serialize($this->config)), 
            array('id' => $this->instance->id));
    }

    /**
     *  This defines the default configuration of all a new instance, if it
     *  has no configurations already within it.
     **/
    function check_default_config() {
        global $CFG;
        
        if (empty($this->config) || !is_object($this->config)) {
            if (!empty($CFG->block_course_menu_global_config)) {
                $this->config = 
                    unserialize($CFG->block_course_menu_global_config);

                $this->init_chapters();
                $this->save_config_to_db();
            } else {
                // Backup-backup configurations
                $this->init_default_config();
            }
        }
    }

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
        $this->save_config_to_db();
    }

    // truncates the description to fit within the given $max_size. 
    // Splitting on tags and \n's where possible
    // @param $string: string to truncate
    // @param $max_size: length of largest piece when done
    // @param $trunc: string to append to truncated pieces
    function truncate_description($string, $max_size=20, $trunc = '...') {
        $split_tags = array('<br>','<BR>','<Br>','<bR>',
            '</dt>','</dT>','</Dt>','</DT>',
            '</p>','</P>', '<BR />', '<br />', '<bR />', '<Br />');

        $temp = $string;

        foreach($split_tags as $tag) {
            list($temp) = explode($tag, $temp, 2);
        }
        
        $rstring = strip_tags($temp);
        $rstring = html_entity_decode($rstring);

        if (strlen($rstring) > $max_size) {
            $rstring = chunk_split($rstring, ($max_size-strlen($trunc)), "\n");
            $temp = explode("\n", $rstring);
            // catches new lines at the beginning
            if (trim($temp[0]) != '') {
                $rstring = trim($temp[0]).$trunc;
            } else {
               $rstring = trim($temp[1]).$trunc;
            }
        }

        if (strlen($rstring) > $max_size) {
            $rstring = substr($rstring, 0, ($max_size - strlen($trunc))).$trunc;
        }
        elseif($rstring == '') {
            // we chopped everything off... lets fall back to a 
            // failsafe but harsher truncation
            $rstring = substr(trim(strip_tags($string)),0,
                ($max_size - strlen($trunc))).$trunc;
        }

        // single quotes need escaping
        return str_replace("'", "\\'", $rstring);
    }

    function clear_enters($string) {
        $newstring = str_replace(array(chr(13), chr(10)), 
            array(' ', ' '), $string));
        return $newstring;
    }

    /**
     *  Takes each of the arguments passed int the function and turns them
     *  into an array.
     **/
    function create_element($id, $name, $url, $icon=array(), $canhide=1, 
            $visible=1, $expandable=0) {
        $elem = array();
        $elem['id']      = $id;
        $elem['name']    = $name;
        $elem['url']     = $url;

        if (is_object($icon)) {
            $icon = $icon->__toString();
        }

        $elem['icon']    = $icon;

        $elem['canhide'] = $canhide;
        $elem['visible'] = $visible;
        $elem['expandable'] = $expandable;

        return $elem;
    }

    function get_name($elementid) {
        global $DB;
        if (isset($this->page->course)) {
            $format = $this->page->course->format;
        } else if (isset($this->instance->pageid)) {
            $course = $DB->get_record('course', 
                array('id' => $this->instance->pageid));
            $format = $course->format;
        } else {
            $format = '';
        }

        switch ($elementid) {
        case 'calendar':     
            return get_string('calendar','calendar');
        case 'showgrades':   
            return get_string('gradebook', 'grades');
        case 'sectiongroup': 
            return get_string("name".$format);
        case 'tree':
            if ($format == 'topics') {
                return get_string('topics', $this->blockname);
            } elseif ($format == 'weeks') {
                return get_string('weeks', $this->blockname);
            } else {
                return get_string('topicsweeks', $this->blockname);
            }
        default:
            if (strstr($elementid, "link") !== false) {
                return get_string("link", $this->blockname);
            }

            if (in_array($elementid, 
                    array('sitepages', 'mycourses', 'myprofile'))) {
                return get_string($elementid);
            }

            return get_string($elementid, $this->blockname);
        }
    }

    function get_sections() {
        global $CFG, $USER, $DB, $OUTPUT;
      
        if (isset($this->sectionscache)) {
            return $this->sectionscache;
        }

        if (empty($this->instance)) {
            return array();
        }
        
        $this_format = $this->course->format;
        if ($this_format == 'social' || $this_format == 'scorm') {
            return array();
        }

        $course_id = $this->course->id;

        get_all_mods($course_id, $mods, $modnames, $modnamesplural, 
            $modnamesused);
        
        $context = get_context_instance(CONTEXT_COURSE, $course_id);
        $isteacher = has_capability('moodle/course:update', $context);
        $canview = has_capability('moodle/course:viewhiddensections', $context);
       
        $get_param = $this->get_topic_get();

        // displaysection - current section
        $displaysection = optional_param($get_param, 0, PARAM_INT);

        $genericname = get_string("name" . $this_format, $this->blockname);
        $allsections = get_all_sections($course_id);
        
        $sections = array();
        $modinfo = unserialize($this->course->modinfo);

        $dont_filter = empty($CFG->filterall);

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

            if (!empty($section->name)) {
                $strsummary = trim($section->name);
            } else {
                // just a default name
                $strsummary = ucwords($genericname) . " " . $k;
            }

            // Clean up the text
            $strsummary = trim($this->clear_enters($strsummary));

            // Shorten the text to displayable levels
            $strsummary = $this->trim($strsummary);

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

                // don't do anything for labels
                if ($mod->modname == 'label') {
                    continue;
                }

                $instancename = urldecode($modinfo[$modnumber]->name);
                if (!$dont_filter) {
                    $instancename = filter_text($instancename, $course_id);
                }

                if (!empty($modinfo[$modnumber]->extra)) {
                    $extra = urldecode($modinfo[$modnumber]->extra);
                } else {
                    $extra = "";
                }

                // Backup name for section module.
                if (strlen(trim($instancename)) === 0) {
                    $instancename = $mod->modfullname;
                }

                // Why is there an arbitrary 200
                $instancename = $this->truncate_description($instancename, 200);

                if ($mod->modname == 'resource') {
                    $info = resource_get_coursemodule_info($mod);
                    $instancename = $this->truncate_description($info->name, 200);
                }

                $resource = array();

                $resource['name'] = $instancename;
                $resource['url'] = $CFG->wwwroot . '/mod/' . $mod->modname 
                    . '/view.php?id=' . $mod->id;

                $icon = $OUTPUT->pix_url("icon", $mod->modname);
                if (is_object($icon)) {
                    $resource['icon'] = $icon->__toString();
                } else {
                    $resource['icon'] = '';
                }

                $newsec['resources'][] = $resource;
            }

            // hide hidden sections from students if the course settings 
            // say that - bug #212
            if ($canview || $section->visible == 1) {
                $sections[] = $newsec;
            }
        }

        // get rid of the first one why?
        array_shift($sections);

        $this->sectionscache = $sections;
        return $sections;
    }

    function get_link_icons() {
        global $CFG, $DB, $OUTPUT;

        $icons = array();
        $icons[0]['name'] = get_string('noicon', $this->blockname);
        $icons[0]['img']  = '';

        $icons[1]['name'] = get_string('linkfileorsite', $this->blockname);
        $icons[1]['img']  = $OUTPUT->pix_url('link', $this->blockname);

        $icons[2]['name'] = get_string('displaydirectory', $this->blockname);
        $icons[2]['img']  = $OUTPUT->pix_url('directory', $this->blockname);

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

        return $icons;
    }

    /**
     *  Truncates the string with elipses, depending on the configuration
     *  as to where the elipses go to.
     *  @param string
     *  @return string The truncated string.
     **/
    function trim($str) {
        $mode = self::TRIM_RIGHT;
        $length = self::DEFAULT_TRIM_LENGTH;

        if (!empty($this->config->trimmode)) {
            $mode = (int)$this->config->trimmode;
        }

        if (!empty($this->config->trimlength)) {
            $length = (int)$this->config->trimlength;
        }

        $textlib = textlib_get_instance();

        switch ($mode) {
        case self::TRIM_RIGHT :
            if ($textlib->strlen($str) > ($length + 3)) {
                return $this->trim_right($textlib, $str, $length);
            }
        case self::TRIM_LEFT :
            if ($textlib->strlen($str) > ($length + 3)) {
                return $this->trim_left($textlib, $str, $length);
            }
        case self::TRIM_CENTER :
            if ($textlib->strlen($str) > ($length + 3)) {
                return $this->trim_center($textlib, $str, $length);
            }
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

    function config_chapters() {
        global $CFG, $USER, $OUTPUT;
        
        $this->course = $this->page->course;
        $this->check_default_config();

        $chapters = $this->config->chapters;
        $sections = $this->get_sections();

        $sectionNames = array();
        foreach ($sections as $section) {
            $sectionNames[] = $section['name'];
        }

        $this->check_redo_chaptering(count($sections));

        ob_start();
        include ("{$CFG->dirroot}/blocks/course_menu/css/styles.php");
        include ("{$CFG->dirroot}/blocks/course_menu/config/chapters.php");
        $cc = ob_get_contents();
        ob_end_clean();

        return $cc;
    }

    /**
     *  Called from within the editing form.
     *  Displays the editing section for each element that can be displayed
     *  within the block.
     **/
    function config_elements() {
        global $CFG, $USER, $OUTPUT;
        
        $this->course = $this->page->course;
        if (!$this->element_exists('sitepages')) {
            $this->init_default_config();
        }
        
        ob_start();
        include ("{$CFG->dirroot}/blocks/course_menu/config/elements.php");
        $cc = ob_get_contents();
        ob_end_clean();
        return $cc;
    }

    /**
     *  Called from within edit_form.php
     *
     **/
    function config_links() {
        global $CFG, $USER, $OUTPUT;
        
        $icons = $this->get_link_icons();

        ob_start();
        include ("{$CFG->dirroot}/blocks/course_menu/config/links.php");
        $cc = ob_get_contents();
        ob_end_clean();
        return $cc;
    }

    /**
     *  Called by Moodle, when going to site administration -> 
     *  plugins -> blocks -> course menu
     **/
    function output_global_config() {
        global $CFG, $THEME, $OUTPUT;
        // required stuff
        $icons = $this->get_link_icons();
        
        // if any config is missing then set eveything to default
        if (empty($CFG->block_course_menu_global_config)) {
            $this->init_default_config(false);
        } else {
            $this->config = unserialize($CFG->block_course_menu_global_config);
            if (!$this->element_exists('sitepages')) {
                $this->init_default_config(false);
            }
        }
        
        // elements: set names
        foreach ($this->config->elements as $k => $element) {
            $this->config->elements[$k]['name'] = $this->get_name($element['id']);
        }

        ob_start();
        include ("{$CFG->dirroot}/blocks/course_menu/config/global.php");
        $cc = ob_get_contents();
        ob_end_clean();

        return $cc;
    }

    /**
     *  Called by moodle whenever saving a block's configuration, usually
     *  by turning editing on, and then editing the block, then saving
     *  the changes.
     **/
    function instance_config_save($data, $nolongerused = false) {
        // This is to compensate for the custom javascript used
        // within the configuration settings
        $ele_ids = required_param('ids', PARAM_RAW);
        $ele_vis = required_param('visibles', PARAM_RAW);
        echo "<pre>Configuration Saved:\n";

        print_r($ele_ids);
        var_dump($data);
        echo "</pre>";
        die;

        return parent::instance_config_save($data, $nolongerused);
    }

    /*
     *  Moodle overridden function.
     **/
    function has_config() {
        return true;
    }

    /*
     *  Verifies that an element exists within the configuration.
     **/
    function element_exists($id) {
        foreach ($this->config->elements as $element) {
            if ($element['id'] == $id) {
                return true;
            }
        }
        return false;
    }
    
    function is_navigation_element($id) {
        // Switch-case statements might be faster, but this is more convenient
        if (in_array($id, array('sitepages', 'myprofile', 'mycourses'))) {
            return true;
        }

        return false;
    }
    
    function is_settings_element($id) {
        if (in_array($id, array('courseadministration', 'myprofilesettings'))) {
            return true;
        }

        return false;
    }
}

// EOF
