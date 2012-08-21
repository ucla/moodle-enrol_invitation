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
 * This file contains general functions for the course format UCLA. Based off
 * the topic format.
 *
 * @copyright 2012 UC Regents
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/local/ucla/lib.php');
/**  Course Preferences API **/
require_once(dirname(__FILE__) . '/ucla_course_prefs.class.php');

define('UCLA_FORMAT_DISPLAY_ALL', -2);
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
 * Used to display the course structure for a course where format=topic
 *
 * This is called automatically by {@link load_course()} if the current course
 * format = weeks.
 *
 * @param array $path An array of keys to the course node in the navigation
 * @param stdClass $modinfo The mod info object for the current course
 * @return bool Returns true
 */
function callback_ucla_load_content(&$navigation, $course, $coursenode) {
    return $navigation->load_generic_course_sections($course, $coursenode, 'ucla');
}

/**
 * The string that is used to describe a section of the course
 * e.g. Topic, Week...
 *
 * @return string
 */
function callback_ucla_definition() {
    return get_string('week');
}

function callback_ucla_get_section_name($course, $section) {
    // We can't add a node without any text
    if ((string)$section->name !== '') {
        return format_string($section->name, true, array('context' => get_context_instance(CONTEXT_COURSE, $course->id)));
    } else if ($section->section == 0) {
        return get_string('section0name', 'format_ucla');
    } else {
        return get_string('week').' '.$section->section;
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
    $ajaxsupport->testedbrowsers = array('MSIE' => 6.0, 'Gecko' => 20061111, 'Safari' => 531, 'Chrome' => 6.0);
    return $ajaxsupport;
}

/**
 * Callback function to do some action after section move
 *
 * @param stdClass $course The course entry from DB
 * @return array This will be passed in ajax respose.
 */
function callback_ucla_ajax_section_move($course) {
    global $COURSE, $PAGE;

    $titles = array();
    rebuild_course_cache($course->id);
    $modinfo = get_fast_modinfo($COURSE);
    $renderer = $PAGE->get_renderer('format_ucla');
    if ($renderer && ($sections = $modinfo->get_section_info_all())) {
        foreach ($sections as $number => $section) {
            $titles[$number] = $renderer->section_title($section, $course);
        }
    }
    return array('sectiontitles' => $titles, 'action' => 'move');
}

/**
 * Sets up given section. Will auto create section. if we have numsections set 
 * < than the actual number of sections that exist.
 * 
 * NOTE: We are not using the Moodle API of get_course_section, because we want
 * to set the section name in the database.
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
        // Set the name of the section name if it is null
        if(!empty($section) && $thissection->name == null) {
            $thissection->name = get_string('sectionname', 'format_weeks') . ' ' . $section;
            $DB->update_record('course_sections', $thissection);
        }        
    } else {
        // Create a new section
        $thissection = new stdClass;
        $thissection->course  = $course->id;   
        $thissection->section = $section;
        // Assign the week number as default name
        $thissection->name = get_string('sectionname', 'format_weeks') . ' ' . $section;
        $thissection->summary = '';
        $thissection->summaryformat = FORMAT_HTML;
        $thissection->visible  = 1;
        $thissection->id = $DB->insert_record('course_sections', $thissection);
        rebuild_course_cache($course->id, true);
    }
    
    return $thissection;
}

/**
 * Gets and determines if the format should display instructors.
 * 
 * @param object $course
 * @return mixed            If course should display instructions, will query
 *                          database for instructor information, else returns
 *                          false.
 */
function ucla_format_display_instructors($course) {
    global $CFG, $DB;
    
    require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');

    // only display office hours for registrar sites or instructional or test
    // collaboration sites
    $site_type = siteindicator_site::load($course->id);    
    if (!empty($site_type) && $site_type->property->type != 'instruction' && 
            $site_type->property->type != 'test') {
        return false;
    }
    
    // Note that untagged collaboration websites will also show the office hours
    // block, but that is okay; they should be tagged anyways.

    // now get instructors
    $params = array();
    $params[] = $course->id;    
    $instructor_types = $CFG->instructor_levels_roles;
    
    // map-reduce-able
    $roles = array();
    foreach ($instructor_types as $instructor) {
        foreach ($instructor as $role) {
            $roles[$role] = $role;
        }
    }

    // Get the people with designated roles
    try {
        if (!isset($roles) || empty($roles)) {
            // Hardcoded defaults
            $roles = array(
                'editingteacher',
                'teacher'
            );
        }

        list($in_roles, $new_params) = $DB->get_in_or_equal($roles);
        $additional_sql = ' AND r.shortname ' . $in_roles;
        $params = array_merge($params, $new_params);
    } catch (coding_exception $e) {
        // Coding exception...
        $additional_sql = '';
    }    
    
    
    // Join on office hours info as well to get all information in one query
    $sql = "
        SELECT 
            CONCAT(u.id, '-', r.id) as recordset_id,
            u.id,
            u.firstname,
            u.lastname,
            u.email,
            u.maildisplay,
            u.url,
            r.shortname,
            oh.officelocation,
            oh.officehours,
            oh.email as officeemail,
            oh.phone
        FROM {course} c
        JOIN {context} ct
            ON (ct.instanceid = c.id)
        JOIN {role_assignments} ra
            ON (ra.contextid = ct.id)
        JOIN {role} r
            ON (ra.roleid = r.id)
        JOIN {user} u
            ON (u.id = ra.userid)
        LEFT JOIN {ucla_officehours} oh
            ON (u.id = oh.userid AND c.id = oh.courseid)
        WHERE 
            c.id = ?
            $additional_sql
        ORDER BY u.lastname, u.firstname";    
    
    $instructors = $DB->get_records_sql($sql, $params);

    return $instructors;
}

/**
 *  Figures out the section to display. Specific only to the UCLA course format.
 *  Uses a $_GET or $_POST param to figure out what's going on.
 *
 *  @return int       Returns section number that user is viewing
 */
function ucla_format_figure_section($course, $course_prefs = null) {

    // see if user is requesting a specific section
    $section = optional_param('section', null, PARAM_INT);
    if (!is_null($section)) {
        // This means that a section was explicitly declared
        return $section;
    }
    
    // no specific section was requested, so see if user was looking for 
    // "Show all" option
    if (optional_param('show_all', 0, PARAM_BOOL)) {
        return UCLA_FORMAT_DISPLAY_ALL;
    }
    
    // no specific section and no "Show all", so just go to landing page    
    if ($course_prefs == null || !is_object($course_prefs)) {
        $course_prefs = new ucla_course_prefs($course->id);
    }

    // Default to course marker (usually section 0 (site info)) if there are no 
    // landing page preference
    $landing_page = $course_prefs->get_preference('landing_page', false);
    if ($landing_page === false) {
        $landing_page = $course->marker;
    } 

    return $landing_page;
}
