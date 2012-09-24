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
 * Library of interface functions and constants for UCLA syllabus
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the UCLA syllabus specific functions, needed to implement all the plugin
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    local
 * @subpackage ucla_syllabus
 * @copyright  2012 UC Regents
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function local_ucla_syllabus_user_outline($course, $user, $mod, $newmodule) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $newmodule the module instance record
 * @return void, is supposed to echp directly
 */
function local_ucla_syllabus_user_complete($course, $user, $mod, $newmodule) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in newmodule activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function local_ucla_syllabus_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link newmodule_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function local_ucla_syllabus_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see newmodule_get_recent_mod_activity()}

 * @return void
 */
function local_ucla_syllabus_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function local_ucla_syllabus_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function local_ucla_syllabus_get_extra_capabilities() {
    return array();
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Serves the files from the ucla_syllabus file areas.
 * 
 * Depending on the syllabus access type, do the following checks:
 *  - Public: allow download
 *  - Logged in: check to see if user is logged in
 *  - Private: check to see if user is associated with course
 *
 * @package local_uclas_syllabus
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the newmodule's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function local_ucla_syllabus_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    require_once(dirname(__FILE__).'/locallib.php');
    global $DB, $CFG;
    
    // first get syllabus file
    $syllabus = ucla_syllabus_manager::instance($args[0]);  // first argument should be ucla_syllabus id
    
    // do some sanity checks
    if (empty($syllabus) || !(isset($syllabus->stored_file))) {
        // no syllabus
        send_file_not_found();        
    } else if ($syllabus->courseid != $course->id || 
            $syllabus->stored_file->get_contextid() != $context->id) {
        // given file doesn't belong to given course
        print_error('err_syllabus_mismatch', 'local_ucla_syllabus');
    }
    
    // now check access type
    $allow_download = false;
    switch ($syllabus->access_type) {
        case UCLA_SYLLABUS_ACCESS_TYPE_PUBLIC:
            $allow_download = true;
            break;
        case UCLA_SYLLABUS_ACCESS_TYPE_LOGGEDIN:
            require_login($course, true, $cm);
            if (isloggedin()) {
                $allow_download = true;
            }
            break;
        case UCLA_SYLLABUS_ACCESS_TYPE_PRIVATE:
            // TODO later
            break;
        default:
            break;
    }
    
    if ($syllabus->can_view()) {
        // finally send the file
        send_stored_file($syllabus->stored_file, 86400, 0, $forcedownload);
    } else {
        print_error('err_syllabus_not_allowed', 'local_ucla_syllabus');
    }
}

//////////////////////////////////////////////////////////////////////////////////
//// Navigation API                                                             //
//////////////////////////////////////////////////////////////////////////////////
//
///**
// * Extends the global navigation tree by adding newmodule nodes if there is a relevant content
// *
// * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
// *
// * @param navigation_node $navref An object representing the navigation tree node of the newmodule module instance
// * @param stdClass $course
// * @param stdClass $module
// * @param cm_info $cm
// */
//function newmodule_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
//}
//
///**
// * Extends the settings navigation with the newmodule settings
// *
// * This function is called when the context for the page is a newmodule module. This is not called by AJAX
// * so it is safe to rely on the $PAGE.
// *
// * @param settings_navigation $settingsnav {@link settings_navigation}
// * @param navigation_node $newmodulenode {@link navigation_node}
// */
//function newmodule_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $newmodulenode=null) {
//}
