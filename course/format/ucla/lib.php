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

define('UCLA_FORMAT_DISPLAY_ALL', -1);
define('UCLA_FORMAT_DISPLAY_PREVIOUS', -2);
define('UCLA_FORMAT_DISPLAY_LANDING', -3);

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

    $supernode =& find_course_link_helper($navigation, $ref_url);

    if ($supernode !== false) {
        $supernode->action->params(array(
            'topic' => UCLA_FORMAT_DISPLAY_LANDING
        ));
    }
    
    // Add the course preferences
    $course_pref = new moodle_url($CFG->wwwroot 
        . '/course/format/ucla/edit.php',
        array('courseid' => $course->id));

    $supernode->add(get_string('course_pref', 'format_ucla'), $course_pref);

    return $navigation->load_generic_course_sections($course, $coursenode, 
        'ucla');
}

/**
 *  It's a dfs, but a bfs is better.
 **/
function find_course_link_helper(&$navigation, $reference) {
    if (!is_object($navigation)) {
        return false;
    }

    if (isset($navigation->action) 
      && get_class($navigation->action) == 'moodle_url') {
        if ($navigation->action->compare($reference)) {
            return $navigation;
        }
    }

    foreach ($navigation->children as &$child) {
        $res = find_course_link_helper($child, $reference);

        if ($res !== false) {
            return $res;
        }
    }

    return false;
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
    } else {
        if (empty($course->idnumber)) {
            return false;
        }
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

    /**
     *  Landing page and determining which section to display
     **/
    $topic = optional_param(callback_ucla_request_key(), 
        UCLA_FORMAT_DISPLAY_PREVIOUS, PARAM_INT);

    /**
     *  New landing page and topic view control.
     *  We want to make sure that if a user is coming from a different course
     *  that they goto the landing page.
     *
     *  This code uses the fact that the $USER global is cached and carried 
     *  through
     *  the session. 
     *  Also uses the fact that course_get_display() will clear $USER->display 
     *  whenever we traverse to a new course.
     **/

    $displaysection = null;
    $to_topic = null;

    if ($topic >= UCLA_FORMAT_DISPLAY_ALL) {
        // This means that a topic was explicitly declared
        $to_topic = $topic;
    } else {
        if ($topic == UCLA_FORMAT_DISPLAY_LANDING 
                || !isset($USER->display['course'])) {
            debugging('UCLA Format: Landing page');

            if ($landing_page === false) {
                $to_topic = $course->marker;
            } else {
                $to_topic = $landing_page;
            }
        } else {
            debugging('UCLA Format: Previously viewed page');

            // This should show the previously viewed page
            // This defaults to '0'
            $displaysection = course_get_display($course->id);
        }
    }

    return array($to_topic, $displaysection);
}
