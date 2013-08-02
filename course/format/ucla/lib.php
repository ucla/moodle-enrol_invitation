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
global $CFG;
require_once($CFG->dirroot . '/local/ucla/lib.php');
/**  Course Preferences API **/
require_once(dirname(__FILE__) . '/ucla_course_prefs.class.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/tool/uclasiteindicator/lib.php');
require_once($CFG->dirroot. '/course/format/topics/lib.php');

define('UCLA_FORMAT_DISPLAY_SYLLABUS', 'syllabus');
define('UCLA_FORMAT_DISPLAY_ALL', -2);
define('UCLA_FORMAT_DISPLAY_LANDING', -4);


/**
 * Main class for the Topics course format
 *
 * @package    format_topics
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_ucla extends format_topics {
    /**
     *  Figures out the section to display. Specific only to the UCLA course format.
     *  Uses a $_GET or $_POST param to figure out what's going on.
     *
     *  @return int       Returns section number that user is viewing
     */
    function figure_section($course, $course_prefs = null) {

        // see if user is requesting a permalink section
        $sectionid = optional_param('sectionid', null, PARAM_INT);
        if (!is_null($sectionid)) {
            // NOTE: use section
            global $section;
            // This means that a sectionid was explicitly declared, so just use
            // $displaysection, because it has been converted to a section number
            return $section;
        }

        // see if user is requesting a specific section
        $section = optional_param('section', null, PARAM_INT);
        if (!is_null($section)) {
            // CCLE-3740 - section === -1 is an alias for section 0 (Site info)
            // This is set by uclatheme renderer so that we can handle this redirect correctly
            if($section === -1) {
                $section = 0;
            }
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
    
   /**
    * Gets and determines if the format should display instructors.
    * 
    * @param object $course
    * @return mixed            If course should display instructions, will query
    *                          database for instructor information, else returns
    *                          false.
    */
   function display_instructors() {
       global $CFG, $DB;

       require_once($CFG->dirroot . '/admin/tool/uclasiteindicator/lib.php');
       
       // only display office hours for registrar sites or instructional, tasite
       // or test collaboration sites
       $site_type = siteindicator_site::load($this->courseid);    
       if (!empty($site_type) && !in_array($site_type->property->type,
               array('instruction', 'tasite', 'test'))) {
           return false;
       }

       // Note that untagged collaboration websites will also show the office hours
       // block, but that is okay; they should be tagged anyways.

       // now get instructors
       $params = array();
       $params[] = $this->courseid;    
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
           SELECT DISTINCT
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
               ON (ct.instanceid = c.id AND ct.contextlevel= ".CONTEXT_COURSE.")
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
     * Returns the display name of the given section that the course prefers.
     *
     * Use section name is specified by user. Otherwise use default ("Topic #")
     *
     * @param int|stdClass $section Section object from database or just field section.section
     * @return string Display name that the course format prefers, e.g. "Topic 2"
     */
    public function get_section_name($section) {
        $section = $this->get_section($section);
        if ((string)$section->name !== '') {
            return format_string($section->name, true,
                    array('context' => context_course::instance($this->courseid)));
        } else if ($section->section == 0) {
            return get_string('section0name', 'format_ucla');
        } else {
            return get_string('week').' '.$section->section;
        }
    }
    
    public function course_footer() {
        global $PAGE;
        
        // Ensure that ajax should be included
        if (!course_ajax_enabled($this->course)) {
            return false;
        }
        
        $strishidden      = '(' . get_string('hidden', 'calendar') . ')';
        $strmovealt         = get_string('movealt', 'format_ucla');
        $pp_make_private = get_string('publicprivatemakeprivate','local_publicprivate');
        $pp_make_public = get_string('publicprivatemakepublic','local_publicprivate');
        $pp_private_material = get_string('publicprivategroupingname','local_publicprivate');
        
        $noeditingicons = get_user_preferences('noeditingicons', 1);

        $noeditingicons = empty($noeditingicons) ? false : true;

        $PAGE->requires->yui_module('moodle-course-dragdrop-ucla', 'M.format_ucla.init_resource_toolbox',
                array(array(
                    'noeditingicon' => $noeditingicons,
                    'makeprivate' => $pp_make_private,
                    'makepublic' => $pp_make_public,
                    'privatematerial' => $pp_private_material,
                )), null, true);
        
        $PAGE->requires->yui_module('moodle-course-dragdrop-ucla', 'M.format_ucla.init_toolbox',
                array(array(
                    'noeditingicon' => $noeditingicons,
                )), null, true);

        $PAGE->requires->yui_module('moodle-course-dragdrop-ucla', 'M.format_ucla.init',
                array(array(
                    'noeditingicon' => $noeditingicons,
                    'hidden' => $strishidden,
                    'movealt' => $strmovealt,
                )), null, true);

    
    }
}

/**
 * Temporary hack to maintain some backwards compatibility
 */

function ucla_format_figure_section($course, $course_prefs = null) {
    return course_get_format($course)->figure_section($course, $course_prefs);
}
