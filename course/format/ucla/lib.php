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
    global $CFG;

    // Sort of a dirty hack, but this so far is the best way to manipulate the
    // navbar since these callbacks are called before the format is included
    $path = $CFG->wwwroot . '/course/view.php';
    $ref_url = new moodle_url($path, array('id' => $course->id));

    $coursenode->action->params(array(
        'topic' => UCLA_FORMAT_DISPLAY_LANDING
    ));
  
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
        return false;
    }

    return true;
}

/**
 *  Figures out the topic to display. Specific only to the UCLA course format.
 *  Uses a $_GET or $_POST param to figure out what's going on.
 *
 *  @return Array(
 **/
function ucla_format_figure_section($course, $course_prefs = null) {
    global $USER;

    if ($course_prefs == null || !is_object($course_prefs)) {
        $course_prefs = new ucla_course_prefs($course_prefs);
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
        debugging('explicit landing page');
        $to_topic = $landing_page;
    } else {
        $to_topic = course_get_display($cid);

        // No previous history
        if ($to_topic == 0) {
            debugging('implicit landing page');
            $to_topic = $landing_page;
        }
    }

    $displaysection = $to_topic - 1;

    return array($to_topic, $displaysection);
}

