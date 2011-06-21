<?php
/*
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
 */

/*
 *  This is the course menu block, written by "NetSapiensis."
 *
 *  TODO
 *  - Move all graphics to pix/.
 *  - Consolidate code properly.
 */
class block_course_menu extends block_base {
    /** @var int Trim characters from the right */
    const TRIM_RIGHT = 1;

    /** @var int Trim characters from the left */
    const TRIM_LEFT = 2;

    /** @var int Trim characters from the center */
    const TRIM_CENTER = 3;

    /** @var int Trim length that is hard-coded default */
    const DEFAULT_TRIM_LENGTH = 10;

    /** @var int TODO No idea **/
    const EXPANDABLE_TREE = 0;
   
    /** Cache maintainer **/
    private $contentgenerated = false;
    
    function init() {
        global $CFG;
        
        $this->blockname = get_class($this);
        $this->title = get_string('pluginname', $this->blockname);
    }

    function instance_allow_multiple() {
        return false;
    }

    function instance_allow_config() {
        return true;
    }

    function course_format_file($format) {
        global $CFG;

        return $CFG->dirroot . "/course/format/$courseformat/lib.php";
    }
    
    function get_content() {
        global $CFG, $USER, $DB;
        
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
        
        // courseFormat TODO Fix this to be compatible with other FORMATs
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
        
        // displaysection - current section
        $week = optional_param($format_rk, -1, PARAM_INT);
        
        // TODO This section needs to be reprimanded and relocated.
        if ($week != -1) {
            $displaysection = course_set_display($this->course->id, $week);
        } else {
            if (isset($USER->display[$this->course->id])) {
                $displaysection = $USER->display[$this->course->id];
            } else {
                $displaysection = course_set_display($this->course->id, 0);
            }
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
        foreach ($chapters as $k => $chapter) {
            $chapters[$k]['expanded'] = 
                in_array(md5($chapter['name']), $sessionVar);

            foreach ($chapter['childElements'] as $i => $child) {
                if ($child['type'] == "subchapter") {
                    $chapters[$k]['childElements'][$i]['expanded'] = 
                        in_array(md5($child['name']), $sessionVar);
                    $sumSection += $child['count'];
                } else {
                    $sumSection++;
                }

                if ($sumSection >= $displaysection && !$found) {
                    $found = true;
                    $chapters[$k]['expanded'] = true;
                    $chapters[$k]['childElements'][$i]['expanded'] = true;
                }
            }
        }
        
        $expansionlimit = null;
        if (!empty($this->config->expansionlimit)) {
            $expansionlimit = $this->config->expansionlimit;
            $navigation->set_expansion_limit($expansionlimit);
        }
        
        $expandable = array();
        $this->find_expandable($this->page->navigation, $expandable);
        if ($expansionlimit) {
            foreach ($expandable as $key=>$node) {
                if ($node['type'] > $expansionlimit 
                  && !($expansionlimit == navigation_node::TYPE_COURSE 
                  && $node['type'] == $expansionlimit 
                  && $node['branchid'] == SITEID)) {
                    unset($expandable[$key]);
                }
            }
        }
       
        // YUI Javascript Initializations
        $this->page->requires->yui2_lib('dom');
        
        $module = array(
            'name' => 'block_course_menu', 
            'fullpath' => '/blocks/course_menu/course_menu.js', 
            'requires' => array(
                'core_dock', 'io', 'node', 'dom', 'event-custom', 'json-parse'
            ), 
            'strings' => array(
                array('viewallcourses', 'moodle')
            )
        );

        $limit = 20;
        $arguments = array(
            $this->instance->id, 
            array(
                'expansions' => $expandable, 
                'instance' => $this->instance->id, 
                'candock' => $this->instance_can_be_docked(), 
                'courselimit' => $limit
            )
        );

        $this->page->requires->js_init_call(
            'M.block_course_menu.init_add_tree', $arguments, false, $module);
        
        // render output
        $renderer = $this->page->get_renderer('block_course_menu');
        $output = html_writer::start_tag('div', array(
            'class' => 'block_navigation'
        ));

        $lis = '';
        $linkIndex = 0;

        foreach ($this->config->elements as $element) {
            $element['name'] = $this->get_name($element['id']);
            $element['children'] = array();

            if ($element['visible']) {
                $icon = $renderer->icon(
                    $element['icon'], 
                    $element['name'], 
                    array('class' => 'smallicon')
                );

                switch ($element['id']) {
                    case 'tree': 
                        // build chapter / subchapter / topic 
                        // / week structure
                        $lis .= $renderer->render_chapter_tree(
                            $this->instance->id, $this->config, $chapters, 
                            $sections, $displaysection);
                        break;
                    case 'showallsections':
                        // show element just in case there is only one 
                        // topic / week visible
                        if ($displaysection) {
                            $_SESSION['cm_tree'][$this->instance->id]
                                ['last_active'] = $displaysection;

                            $element['name'] = get_string(
                                "showall{$format_rk}s", $this->blockname);

                            $element['url'] = $CFG->wwwroot 
                                . '/course/view.php?id=' . $this->course->id
                                . '&' . $format_rk . '=all#section-'
                                . $displaysection;

                            $lis .= $renderer->render_leaf(
                                $element['name'], $icon, 
                                array('id' => 'showallsections'), 
                                $element['url']);
                        } else {
                            $ss = !empty($_SESSION['cm_tree'][$this->instance->id]['last_active']) ? $_SESSION['cm_tree'][$this->instance->id]['last_active'] : '1';
                            $element['name'] = get_string("showonly{$format_rk}", $this->blockname) . " ";
                            $element['url'] = "$CFG->wwwroot/course/view.php?id={$this->course->id}&{$format_rk}={$ss}";
                            $weekNr = html_writer::tag('span', $ss, array('id' => 'showonlysection_nr'));
                            $lis .= $renderer->render_leaf($element['name'], $icon, array('id' => 'showallsections'), $element['url'], false, $weekNr);
                        }
                        break;
                    case 'calendar':
                        $element['url'] = "$CFG->wwwroot/calendar/view.php?view=upcoming&course=" .$this->course->id;
                        $lis .= $renderer->render_leaf($element['name'], $icon, array(), $element['url']);
                        break;
                    case 'showgrades':
                        $elements[$k]['url'] = $CFG->wwwroot."/grade/index.php?id=".$this->course->id;
                        $lis .= $renderer->render_leaf($element['name'], $icon, array(), $element['url']);
                        break;
                    default:
                        if (substr($element['id'], 0, 4) == 'link') {
                            $lis .= $renderer->render_link($this->config->links[$linkIndex], $this->course->id);
                            $linkIndex++;
                        } else {
                            //check for special links (navigation, settings)
                            if ($this->is_navigation_element($element['id'])) {
                                $type = 0;
                                if ($element['id'] == 'sitepages') {
                                    $type = global_navigation::TYPE_COURSE;
                                } elseif ($element['id'] == 'myprofile') {
                                    $type = global_navigation::TYPE_USER;
                                } elseif ($element['id'] == 'mycourses') {
                                    $type = global_navigation::TYPE_ROOTNODE;
                                }
                                $all = $node_collection->type($type);
                                $good = array();
                                $elements[$k]['children'] = array();
                                if (is_array($all) && count($all)) {
                                    foreach ($all as $item) {
                                        if ($item->text == get_string($element['id'])) {
                                            $good = $item;
                                            break;
                                        }
                                    }
                                }
                                if ($good instanceof navigation_node && $good->children->count()) {
                                    $lis .= $renderer->render_navigation_node($good, $expansionlimit);
                                }
                            } elseif ($this->is_settings_element($element['id'])) {
                                $type = 0;
                                $key = '';
                                if ($element['id'] == 'myprofilesettings') {
                                    $type = global_navigation::TYPE_CONTAINER;
                                    $key = 'usercurrentsettings';
                                } elseif ($element['id'] == 'courseadministration') {
                                    $key = 'courseadministration';
                                    $type = global_navigation::TYPE_COURSE;
                                }
                                $all = $settings->type($type);
                                $s = array();
                                $elements[$k]['children'] = array();
                                if (is_array($all) && count($all)) {
                                    foreach ($all as $item) {
                                        if ($item->text == get_string($key)) {
                                            $s = $item;
                                            break;
                                        }
                                    }
                                }
                                if ($s instanceof navigation_node && $s->children->count()) {
                                    $lis .= $renderer->render_navigation_node($s, $expansionlimit);
                                }
                            }
                        }
                    // End Switch
                }
            }
        }
        $output .= html_writer::tag('ul', $lis, array('class' => 'block_tree list'));
        $output .= '</div>';
        
        $this->contentgenerated = true;
        $this->content->text = $output;
        
        return $this->content;
        
    }
    
    function find_expandable($navigation, array &$expandable) {
        foreach ($navigation->children as &$child) {
            if ($child->nodetype == global_navigation::NODETYPE_BRANCH && $child->children->count()==0 && $child->display) {
                $child->id = 'cm_expandable_branch_'.(count($expandable)+1);
                $navigation->add_class('canexpand');
                $expandable[] = array('id'=>$child->id,'branchid'=>$child->key,'type'=>$child->type);
            }
            $this->find_expandable($child, $expandable);
        }
    }

    /*
     *  Initializes the basic chapters setup.
     */
    function init_chapters() {
        $config->chapEnable         = 0;
        $config->subChapEnable      = 0;
        $config->subChaptersCount   = 1;
        $config->chapters           = array();

        $chapter = array();
        $chapter['name']  = get_string("chapter", $this->blockname)." 1";

        $child = array();
        $child['type'] = "subchapter";
        $child['name'] = get_string("subchapter", $this->blockname) . " 1";
        $child['count'] = count($sections);
        $chapter['childElements'] = array($child);

        return $chapter;
    }

    /*
     *  This will build an empty configuration, which basically is the
     *  fallback-default.
     */
    function init_default_config($save_it = true) {
        global $CFG, $USER, $OUTPUT;

        // elements -------------------------------------------------------------------------
        $elements   = array();

        $e = 'tree';
        $elements[] = $this->create_element(
            $e, $this->get_name($e), '', 
            '', 0);

        // TODO user OUTPUT->pix_url for all these gifs...
        // showallsections
        $e = 'showallsections';
        $elements[] = $this->create_element(
            $e, $this->get_name($e), "", 
            "{$CFG->wwwroot}/blocks/course_menu/icons/viewall.gif"
        );

        // calendar
        $e = 'calendar';
        $elements[] = $this->create_element(
           $e, $this->get_name($e), "",
           "{$CFG->wwwroot}/blocks/course_menu/icons/cal.gif", 1, 0
        );

        // showgrades
        if ((isset($this->course->showgrades)) && ($this->course->showgrades)) {
            $e = 'showgrades';
            $elements[] = $this->create_element(
                $e, $this->get_name($e), "",
                $OUTPUT->pix_url('i/grades'), 1, 0
            );
        }
        
        // site pages
        $e = 'sitepages';
        $elements []= $this->create_element(
            $e, get_string($e), '', 
            '', 1, 0, 1
        );
        
        // my profile
        $e = 'myprofile';
        $elements []= $this->create_element(
            $e, get_string($e), '', 
            '', 1, 0, 1
        );
        
        //my course
        $e = 'mycourses';
        $elements []= $this->create_element(
            $e, get_string($e), '', 
            '', 1, 0, 1
        );
        
        //my profile settings
        $e = 'myprofilesettings';
        $elements []= $this->create_element(
            $e, get_string($e, $this->blockname), '', 
            '', 1, 0, 1
        );
        
        //course administration
        $e = 'courseadministration';
        $elements []= $this->create_element(
            $e, get_string($e, $this->blockname), '', 
            '', 1, 0, 1
        );
        
        $config = new stdClass();
        $config->elements = $elements;

        // sections -------------------------------------------------------------------------
        $sections = $this->get_sections();
        
        // chaptering -----------------------------------------------------------------------
        $config->chapters[] = $this->init_chapters();

        // links ----------------------------------------------------------------------------
        $config->linksEnable = 0;
        $config->links       = array();

        $config->expandableTree = self::EXPANDABLE_TREE;
        $config->trimmode       = self::TRIM_RIGHT;
        $config->trimlength     = self::DEFAULT_TRIM_LENGTH;

        $this->config = $config;
        if ($save_it) {
            $this->save_config_to_db();
        }
    }
   
    /*
     *  This saves the configuration of the object instance into the
     *  database.
     */
    function save_config_to_db() {
        global $DB;

        return $DB->set_field('block_instances', 'configdata', 
            base64_encode(serialize($this->config)), 
            array('id' => $this->instance->id));
    }

    /*
     *  This defines the default configuration of all a new instance, if it
     *  has no configurations already within it.
     */
    function check_default_config() {
        global $CFG;
        
        if (empty($this->config) || !is_object($this->config)) {
            // try global config
            if (!empty($CFG->block_course_menu_global_config)) {
                $this->config = 
                    unserialize($CFG->block_course_menu_global_config);

                // chaptering --------------------------------------------------
                $this->config->chapters[] = $this->init_chapters();
                
                $this->save_config_to_db();
            } else {
                // Backup-backup configurations
                $this->init_default_config();
            }
        }
    }

    function check_redo_chaptering($sectCount) {
        // redo chaptering if the number of the sctions changed
        $sumChapSections = 0;
        $subChapterCount = 0;
        
        foreach ($this->config->chapters as $k => $chapter) {
            foreach ($chapter['childElements'] as $kk => $child) {
                if ($child['type'] == "subchapter") {
                    $subChapterCount ++;
                    $sumChapSections += $child['count'];
                } else {
                    $sumChapSections ++;
                }
            }
        }
        $chapCount = count($this->config->chapters);
        
        if ($sumChapSections != $sectCount) {

            if ($chapCount <= $sectCount) {

                $c = floor($sectCount / $chapCount);

                if (($sectCount - ($c*($chapCount - 1)) > $c) && ($sectCount - (($c+1)*($chapCount - 1)) > 0)) {
                    $c++;
                }

                for ($i = 0; $i < $chapCount; $i++) {
                    $temp = $i < $chapCount - 1 ? $c : $sectCount - ($c*($chapCount - 1));
                    $j = 0;
                    while ($this->config->chapters[$i]['childElements'][$j]['type'] == "topic") {
                        $j++;
                    }
                    $newChild = $this->config->chapters[$i]['childElements'][$j];
                    $newChild['count'] = $temp;
                    $this->config->chapters[$i]['childElements'] = array();
                    $this->config->chapters[$i]['childElements'][0] = $newChild;
                }


            } else {
                // make 1 section / chapter; eliminate ($chapCount - $sectCount) chapters, from the last ones
                for ($i = 0; $i < $sectCount; $i++) {
                    $j = 0;
                    while ($this->config->chapters[$i]['childElements'][$j]['type'] == "topic") {
                        $j++;
                    }
                    $newChild = $this->config->chapters[$i]['childElements'][$j];
                    $newChild['count'] = 1;
                    $this->config->chapters[$i]['childElements'] = array();
                    $this->config->chapters[$i]['childElements'][0] = $newChild;
                }
                for ($i = $sectCount; $i < $chapCount; $i++) {
                    unset($this->config->chapters[$i]);
                }
                $chapCount = $sectCount;
            }
            $this->config->subChaptersCount = count($this->config->chapters);

            // the number of sections has changed so the chaptering has changed so write the new changes to the database
            $this->save_config_to_db();
        }
    }

    // truncates the description to fit within the given $max_size. Splitting on tags and \n's where possible
    // @param $string: string to truncate
    // @param $max_size: length of largest piece when done
    // @param $trunc: string to append to truncated pieces
    function truncate_description($string, $max_size=20, $trunc = '...') {
        $split_tags = array('<br>','<BR>','<Br>','<bR>','</dt>','</dT>','</Dt>','</DT>','</p>','</P>', '<BR />', '<br />', '<bR />', '<Br />');
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
            }
            else {
               $rstring = trim($temp[1]).$trunc;
            }
        }
        if (strlen($rstring) > $max_size) {
            $rstring = substr($rstring, 0, ($max_size - strlen($trunc))).$trunc;
        }
        elseif($rstring == '') {
            // we chopped everything off... lets fall back to a failsafe but harsher truncation
            $rstring = substr(trim(strip_tags($string)),0,($max_size - strlen($trunc))).$trunc;
        }

        // single quotes need escaping
        return str_replace("'", "\\'", $rstring);
    }

    function clearEnters($string) {
        $newstring = str_replace(chr(13),' ',str_replace(chr(10),' ',$string));
        return $newstring;
    }

    function create_element($id, $name, $url, $icon = "", $canhide = 1, $visible = 1, $expandable = 0) {
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

    function get_name($elementId) {
        global $DB;
        if (isset($this->instance->pageid)) {
            $course = $DB->get_record('course', array('id' => $this->instance->pageid));
            $format = $course->format;
        } else {
            $format = '';
        }

        switch ($elementId) {
            case 'calendar':     return get_string('calendar','calendar');
            case 'showgrades':   return get_string('gradebook', 'grades');
            case 'sectiongroup': return get_string("name".$format);
            case 'tree':
                if ($format == 'topics') {
                    return get_string('topics', $this->blockname);
                } elseif ($format == 'weeks') {
                    return get_string('weeks', $this->blockname);
                } else {
                    return get_string('topicsweeks', $this->blockname);
                }
            default:
                if (strstr($elementId, "link") !== false) {
                    return get_string("link", $this->blockname);
                }
                if (in_array($elementId, array('sitepages', 'mycourses', 'myprofile'))) {
                    return get_string($elementId);
                }
                return get_string($elementId, $this->blockname);
        }
    }

    function get_sections() {
        global $CFG, $USER, $DB, $OUTPUT;
        
        if (!empty($this->instance)) {
            get_all_mods($this->course->id, $mods, $modnames, $modnamesplural, 
                $modnamesused);
            
            $context = get_context_instance(CONTEXT_COURSE, $this->course->id);
            $isteacher = has_capability('moodle/course:update', $context);
           
            // TODO Fix this... to be more compatible with other formats
            $courseFormat = $this->course->format == 'topics' ? 'topic' : 'week';

            // displaysection - current section
            $week = optional_param($courseFormat, -1, PARAM_INT);
            if ($week != -1) {
                $displaysection = course_set_display($this->course->id, $week);
            } else {
                if (isset($USER->display[$this->course->id])) {
                    $displaysection = $USER->display[$this->course->id];
                } else {
                    $displaysection = course_set_display($this->course->id, 0);
                }
            }

            $genericName = get_string("name" . $this->course->format, 
                $this->blockname);

            $allSections  = get_all_sections($this->course->id);
            
            $sections = array();
            if ($this->course->format != 'social' 
              && $this->course->format != 'scorm') {

                foreach ($allSections as $k => $section) {
                    // get_all_sections() may return sections that are in the 
                    // db but not displayed because the number of the sections 
                    // for this course was lowered - bug [CM-B10]
                    if ($k <= $this->course->numsections) { 
                        if (!empty($section)) {
                            $newSec = array();
                            $newSec['visible'] = $section->visible;

                            if (!empty($section->name)) {
                                $strsummary = trim($section->name);
                            } else {
                                // just a default name
                                $strsummary = ucwords($genericName) . " " . $k;
                            }

                            $strsummary = $this->trim($strsummary);
                            $strsummary = trim($this->clearEnters($strsummary));
                            $newSec['name'] = $strsummary;

                            // url
                            if ($displaysection != 0) {
                                $newSec['url'] = $CFG->wwwroot 
                                    . "/course/view.php?id=" 
                                    . $this->course->id . '&' . $courseFormat
                                    . '=' . $k;
                            } else {
                                $newSec['url'] = "#section-$k";
                            }

                            // resources
                            $modinfo = unserialize($this->course->modinfo);

                            $newSec['resources'] = array();
                            $sectionmods = explode(",", $section->sequence);
                            foreach ($sectionmods as $modnumber) {
                                if (empty($mods[$modnumber])) {
                                    continue;
                                }
                                $mod = $mods[$modnumber];
                                if ($mod->visible or $isteacher) {
                                    $instancename = urldecode($modinfo[$modnumber]->name);
                                    if (!empty($CFG->filterall)) {
                                        $instancename = filter_text($instancename, $this->course->id);
                                    }

                                    if (!empty($modinfo[$modnumber]->extra)) {
                                        $extra = urldecode($modinfo[$modnumber]->extra);
                                    }
                                    else {
                                        $extra = "";
                                    }

                                    // don't do anything for labels
                                    if ($mod->modname != 'label') {
                                        // Normal activity
                                        if ($mod->visible) {
                                            if (!strlen(trim($instancename))) {
                                                $instancename = $mod->modfullname;
                                            }
                                            $instancename = $this->truncate_description($instancename);

                                            $resource = array();
                                            if ($mod->modname != 'resource') {
                                                $resource['name'] = $this->truncate_description($instancename, 200);
                                                $resource['url']  = "$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id";
                                                $icon = $OUTPUT->pix_url("icon", $mod->modname);
                                                if (is_object($icon)) {
                                                    $resource['icon'] = $icon->__toString();
                                                } else {
                                                    $resource['icon'] = '';
                                                }
                                            } else {
                                                require_once($CFG->dirroot.'/mod/resource/lib.php');
                                                $info = resource_get_coursemodule_info($mod);
                                                if (isset($info->icon)) {
                                                    $resource['name'] = $this->truncate_description($info->name, 200);
                                                    $resource['url']  = "$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id";
                                                    $icon = $OUTPUT->pix_url("icon", $mod->modname);
                                                    if (is_object($icon)) {
                                                        $resource['icon'] = $icon->__toString();
                                                    } else {
                                                        $resource['icon'] = '';
                                                    }
                                                } else if(!isset($info->icon)) {
                                                    $resource['name'] = $this->truncate_description($info->name, 200);
                                                    $resource['url']  = "$CFG->wwwroot/mod/$mod->modname/view.php?id=$mod->id";
                                                    $icon = $OUTPUT->pix_url("icon", $mod->modname);
                                                    if (is_object($icon)) {
                                                        $resource['icon'] = $icon->__toString();
                                                    } else {
                                                        $resource['icon'] = $OUTPUT->pix_url("icon", $mod->modname);
                                                    }
                                                }
                                            }
                                            $newSec['resources'][] = $resource;
                                        }
                                    }

                                }
                            }
                            //hide hidden sections from students if the course settings say that - bug #212
                            $coursecontext = get_context_instance(CONTEXT_COURSE, $this->course->id);
                            if (!($section->visible == 0 && !has_capability('moodle/course:viewhiddensections', $coursecontext))) {
                                $sections[] = $newSec;
                            }
                        }
                    }
                }

                // get rid of the first one
                array_shift($sections);
            }

            return $sections;
        }

        return array();
    }

    function get_link_icons() {
        global $CFG, $DB, $OUTPUT;

        $icons = array();
        $icons[0]['name'] = get_string('noicon', $this->blockname);
        $icons[0]['img']  = '';

        $icons[1]['name'] = get_string('linkfileorsite', $this->blockname);
        $icons[1]['img']  = "{$CFG->wwwroot}/blocks/course_menu/icons/link.gif";

        $icons[2]['name'] = get_string('displaydirectory', $this->blockname);
        $icons[2]['img']  = "{$CFG->wwwroot}/blocks/course_menu/icons/directory.gif";

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
     */
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
     */
    protected function trim_right($textlib, $string, $length) {
        return $textlib->substr($string, 0, $length) . '...';
    }
    /**
     * Truncate a string in the center
     * @param textlib $textlib
     * @param string $string The string to truncate
     * @param int $length The length to truncate to
     * @return string The truncated string
     */
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

    function config_elements() {
        // This is pretty useful
        global $CFG, $USER, $OUTPUT;
        
        $this->course = $this->page->course;
        if (!$this->element_exists('sitepages')) {
            $this->init_default_config();
        }
        
        ob_start();
        // Why this method?
        include ("{$CFG->dirroot}/blocks/course_menu/config/elements.php");
        $cc = ob_get_contents();
        ob_end_clean();
        return $cc;
    }

    function config_links() {
        // Getting rid of links ?
        global $CFG, $USER, $OUTPUT;
        
        $icons = $this->get_link_icons();

        ob_start();
        include ("{$CFG->dirroot}/blocks/course_menu/config/links.php");
        $cc = ob_get_contents();
        ob_end_clean();
        return $cc;
    }

    function instance_config_save($data, $nolongerused = false) {
        //append stuff to data - this is BAD
        //chapters
        $chapters = array();
        $lastIndex = 0;
        $total = 0;
        if ($data->chapEnable == 0) {
            $data->subChapEnable = 0;
        }
        // TODO Fix this
        foreach ($_POST['chapterNames'] as $k => $name) {
            $chapter = array();
            $chapter['name'] = $name;
            $chapter['childElements'] = array();

            for ($i = $lastIndex; $i < $lastIndex + $_POST['chapterChildElementsNumber'][$k]; $i++) {
                $child = array();
                if ($data->chapEnable == 0) { //only one subchapter
                    $child['type'] = "subchapter";
                    $child['count'] = count($this->get_sections());
                    $child['name'] = get_string("subchapter", "block_course_menu") . " 1-1";
                } elseif ($data->subChapEnable == 0) {
                    $child['type'] = "subchapter";
                    $xx = $k + 1;
                    $child['name'] = get_string("subchapter", "block_course_menu") . " {$xx}-1";
                    $child['count'] = $_POST['chapterCounts'][$k];
                } else {
                    $child['type'] = $_POST['childElementTypes'][$i];
                    if ($child['type'] == "subchapter") {
                        $child['count'] = $_POST['childElementCounts'][$i];
                        $total += $child['count'];
                        $child['name'] = $_POST['childElementNames'][$i];
                    }
                }
                $chapter['childElements'][] = $child;
            }
            $lastIndex = $i;
            $chapters[] = $chapter;
        }
        $data->chapters = $chapters;

        // elements
        $data->elements = array();
        foreach ($_POST['ids'] as $k => $id) {
            $url     = $_POST['urls'][$k];
            $icon    = $_POST['icons'][$k];
            $canhide = $_POST['canhides'][$k];
            $visible = $_POST['visibles'][$k];
            $name    = $this->get_name($id);
            $data->elements[] = $this->create_element($id, $name, $url, $icon, $canhide, $visible);
        }
        
        //links
        $data->links = array();
        if (isset($_POST['linkNames'])) { // means: if instance config. we don't have links in global config
            foreach ($_POST['linkNames'] as $k => $name) {
                $link = array();
                $link['name']   = $name;
                $link['target'] = $_POST['linkTargets'][$k];
                $link['icon']   = $_POST['linkIcons'][$k];

                // url
                $link['url'] = $_POST['linkUrls'][$k];
                if (strpos($_POST['linkUrls'][$k], "://") === false) {
                    // if no protocol then add "http://" - [CM-TD2]
                    $link['url'] = "http://" . $link['url'];
                }

                // checkbox configs
                $idx = "keeppagenavigation$k";
                $link['keeppagenavigation'] = (isset($_POST[$idx])) && ($_POST[$idx] == "on") ? 1 : 0;

                $idx = "allowresize$k";
                $link['allowresize'] = (isset($_POST[$idx])) && ($_POST[$idx] == "on") ? 1 : 0;

                $idx = "allowresize$k";
                $link['allowresize'] = (isset($_POST[$idx])) && ($_POST[$idx] == "on") ? 1 : 0;

                $idx = "allowresize$k";
                $link['allowresize'] = (isset($_POST[$idx])) && ($_POST[$idx] == "on") ? 1 : 0;

                $idx = "allowscroll$k";
                $link['allowscroll'] = (isset($_POST[$idx])) && ($_POST[$idx] == "on") ? 1 : 0;

                $idx = "showdirectorylinks$k";
                $link['showdirectorylinks'] = (isset($_POST[$idx])) && ($_POST[$idx] == "on") ? 1 : 0;

                $idx = "showlocationbar$k";
                $link['showlocationbar'] = (isset($_POST[$idx])) && ($_POST[$idx] == "on") ? 1 : 0;

                $idx = "showmenubar$k";
                $link['showmenubar'] = (isset($_POST[$idx])) && ($_POST[$idx] == "on") ? 1 : 0;

                $idx = "showtoolbar$k";
                $link['showtoolbar'] = (isset($_POST[$idx])) && ($_POST[$idx] == "on") ? 1 : 0;

                $idx = "showstatusbar$k";
                $link['showstatusbar'] = (isset($_POST[$idx])) && ($_POST[$idx] == "on") ? 1 : 0;

                // defaultwidth + defaultheight
                $link['defaultwidth'] = !empty($_POST['defaultwidth'][$k]) ? $_POST['defaultwidth'][$k] : 0;
                $link['defaultheight'] = !empty($_POST['defaultheight'][$k]) ? $_POST['defaultheight'][$k] : 0;

                $data->links[] = $link;
            }
        }
        return parent::instance_config_save($data, $nolongerused);
    }

    /*
     *  Moodle overridden function.
     */
    function has_config() {
        return true;
    }

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
