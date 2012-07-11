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

/**
 * This file contains general functions for the course format ucla
 * Stolen from course format Topic
 *
 * @since 2.0
 * @package ucla 
 * @subpackage format
 * @copyright 2011 UCLA - Stolen from: 2009 Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

require_once($CFG->dirroot . '/local/ucla/lib.php');

/**  Course Preferences API **/
require_once(dirname(__FILE__) . '/ucla_course_prefs.class.php');

// None of these can be bigger than 0
define('UCLA_FORMAT_DISPLAY_ALL', -2);
define('UCLA_FORMAT_DISPLAY_PREVIOUS', -3);
define('UCLA_FORMAT_DISPLAY_LANDING', -4);

/**
 * Indicates this format uses sections.
 *
 * @return bool Returns true
 */
function callback_ucla_uses_sections() {
    return true;
}

/**
 * Used to display the course structure for a course where format
 *
 * This is called automatically by {@link load_course()} if the current course
 * format = weeks.
 *
 * @param array $path An array of keys to the course node in the navigation
 * @param stdClass $modinfo The mod info object for the current course
 * @return bool Returns true
 */
function callback_ucla_load_content(&$navigation, $course, $coursenode) {
    global $DB, $CFG;

    // Sort of a dirty hack, but this so far is the best way to manipulate the
    // navbar since these callbacks are called before the format is included

    // This is to specify that the breadcrumb link to the course will send you
    // to the landing page.
    $path = $CFG->wwwroot . '/course/view.php';
    $ref_url = new moodle_url($path, array('id' => $course->id));

    $coursenode->action->params(array(
        'topic' => UCLA_FORMAT_DISPLAY_LANDING
    ));

    // This is to prevent further diving and incorrect associations in the
    // navigation bar
    $logical_limitations = array('subjarea', 'division');

    $subjareanode = null;
    $divisionnode = null;

    $division = false;
    $subjarea = false;

    // Browse-by hooks for categories
    if (block_instance('ucla_browseby')) {
        // Term is needed for browseby
        $courseinfos = ucla_map_courseid_to_termsrses($course->id);
        if ($courseinfos) {
            $first = reset($courseinfos);
            $term = $first->term;

            $parentnode =& $coursenode->parent;

            // Find the nodes that represent the division and subject areas
            while ($parentnode->type == navigation_node::TYPE_CATEGORY) {
                if ($subjareanode == null) {
                    $subjarea = $DB->get_field('ucla_reg_subjectarea', 'subjarea', 
                        array('subj_area_full' => $parentnode->text));

                    if ($subjarea) {
                        $subjareanode =& $parentnode;
                    }
                } else if ($divisionnode == null) {
                    $division = $DB->get_field('ucla_reg_division', 'code',
                        array('fullname' => $parentnode->text));

                    if ($division) {
                        $divisionnode =& $parentnode;
                        break;
                    }
                }

                $parentnode =& $parentnode->parent;
            }


            // Replace the link in the navbar for subject areas and divisions
            // with respective browseby links
            if ($divisionnode != null) {
                $divisionnode->action = new moodle_url(
                        '/blocks/ucla_browseby/view.php',
                        array(
                            'type' => 'subjarea',
                            'division' => $division,
                            'term' => $term
                        )
                    );
            }
            
            if ($subjareanode != null) {
                $subjareaparams = array(
                        'type' => 'course',
                        'subjarea' => $subjarea,
                        'term' => $term
                    );

                if ($division) {
                    $subjareaparams['division'] = $division;
                }

                $subjareanode->action = new moodle_url(
                    '/blocks/ucla_browseby/view.php',
                    $subjareaparams
                );
            }
        }
    }

    return $navigation->load_generic_course_sections($course, $coursenode, 
        'ucla');
}

/**
 * The string that is used to describe a section of the course
 * e.g. Topic, Week...
 *
 * @return string
 */
function callback_ucla_definition() {
    return get_string('section');
}

/**
 * The GET argument variable that is used to identify the section being
 * viewed by the user (if there is one)
 *
 * @return string
 */
function callback_ucla_request_key() {
    return 'topic';
}

/**
 *  This will handle how default section names are handled.
 *
 *  @return string
 */
function callback_ucla_get_section_name($course, $section) {
    // We can't add a node without any text
    if (!empty($section->name)) {
        return $section->name;
    } else if ($section->section == 0) {
        return get_string('section0name', 'format_ucla');
    } else {
        $r = ''.get_string('week').' '.$section->section.'';

        return $r;
    }
}

/**
 * Returns a URL to arrive directly at a section
 *
 * @param int $courseid The id of the course to get the link for
 * @param int $sectionnum The section number to jump to
 * @return moodle_url
 */
function callback_ucla_get_section_url($courseid, $sectionnum) {
    return new moodle_url('/course/view.php', array('id' => $courseid, 'topic' => $sectionnum));
}

/**
 * Declares support for course AJAX features
 *
 * @see course_format_ajax_support()
 * @return stdClass
 */
function callback_ucla_ajax_support() {

    $ajaxsupport = new stdClass();
    $ajaxsupport->capable = true;
    $ajaxsupport->testedbrowsers = array(
            'MSIE' => 6.0, 
            'Gecko' => 20061111, 
            'Safari' => 531, 
            'Chrome' => 6.0
    );

    return $ajaxsupport;
}

/**
 *  Determines if the format should display instructors for this page.
 **/
function ucla_format_display_instructors($course) {
    if (function_exists('is_collab_site') && is_collab_site($course)) {
        global $CFG;
        require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');
        $collabsites = siteindicator_manager::get_sites($course);
        $sitetype = $collabsites[$course->id]->type;
        if ($sitetype == 'instruction' || $sitetype == 'test') {
            return true;
        }
        return false;
    }

    return true;
}

/**
 * Sets up given section. Will auto create section. if we have numsections set 
 * < than the actual number of sections that exist.
 * 
 * @global type $DB
 * @param int $section      Section id to get
 * @param array $sections   Sections for course
 * @param object $course 
 * 
 * @return object           Returns given section
 */
function setup_section($section, $sections, $course) {
    global $DB;
    
    if (!empty($sections[$section])) {
        $thissection = $sections[$section];
        
        // Save the name if the section name is NULL
        // This writes the value to the database
        if($section && NULL == $sections[$section]->name) {
            $sections[$section]->name = get_string('sectionname', "format_weeks") . " " . $section;
            $DB->update_record('course_sections', $sections[$section]);
        }
        
    } else {
        // Create a new section
        $thissection = new stdClass;
        $thissection->course  = $course->id;   
        $thissection->section = $section;
        // Assign the week number as default name
        $thissection->name = get_string('sectionname', "format_weeks") . " " . $section;
        $thissection->summary = '';
        $thissection->summaryformat = FORMAT_HTML;
        $thissection->visible  = 1;
        $thissection->id = $DB->insert_record('course_sections', $thissection);
    }
    
    return $thissection;
}
/**
 *  Figures out the topic to display. Specific only to the UCLA course format.
 *  Uses a $_GET or $_POST param to figure out what's going on.
 *
 *  @return array       Returns an array with two results with the index:
 *                      0 - $to_topic - the index of the section in 
 *                                      course_display table (internal usage)
 *                      1 - $displaysection - index of the section to use when 
 *                                            writing urls (external usage)
 **/
function ucla_format_figure_section($course, $course_prefs = null) {
    global $USER;

    if ($course_prefs == null || !is_object($course_prefs)) {
        $course_prefs = new ucla_course_prefs($course->id);
    }

    // Default to section 0 (course info) if there are no preferences
    $landing_page = $course_prefs->get_preference('landing_page', false);
    if ($landing_page === false) {
        $landing_page = $course->marker;
    } 

    // Shifting landing page section for storage purposes
    $landing_page++;

    /**
     *  Landing page and determining which section to display
     **/
    $topic = optional_param(callback_ucla_request_key(), 
        UCLA_FORMAT_DISPLAY_PREVIOUS, PARAM_INT);

    $topic++;

    $displaysection = null;
    $to_topic = null;
    $cid = $course->id;

    if ($topic == (UCLA_FORMAT_DISPLAY_ALL + 1) || $topic > 0) {
        // This means that a topic was explicitly declared
        $to_topic = $topic;
    } else if ($topic == (UCLA_FORMAT_DISPLAY_LANDING + 1)) {
        //debugging('explicit landing page');
        $to_topic = $landing_page;
    } else {
        $to_topic = course_get_display($cid);

        // No previous history
        if ($to_topic == 0) {
            //debugging('implicit landing page');
            $to_topic = $landing_page;
        }
    }

    // Fix if there was a change in number of sections
    if ($to_topic > $course->numsections + 1) {
        $to_topic = $landing_page;
    }

    $displaysection = $to_topic - 1;

    return array($to_topic, $displaysection);
}

