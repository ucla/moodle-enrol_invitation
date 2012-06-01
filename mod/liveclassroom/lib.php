<?php

/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2008  Wimba, All Rights Reserved.                       *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Wimba.                               *
 *      You can redistribute it and/or modify it under the terms of           *
 *      the GNU General Public License as published by the                    *
 *      Free Software Foundation.                                             *
 *                                                                            *
 * WARRANTIES:                                                                *
 *      This software is distributed in the hope that it will be useful,      *
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *      GNU General Public License for more details.                          *
 *                                                                            *
 *      You should have received a copy of the GNU General Public License     *
 *      along with the Wimba Moodle Integration;                              *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Hugues Pisapia                                                     *
 *                                                                            *
 * Date: 15th April 2006                                                      *
 *                                                                            *
 ******************************************************************************/
/* $Id: lib.php 80761 2010-12-09 16:01:05Z bdrust $ */
// / Library of functions and constants for module liveclassroom

if (!function_exists('getKeysOfGeneralParameters')) {
    require_once('lib/php/common/WimbaLib.php');
}
require_once($CFG->libdir . '/datalib.php');

require_once("lib/php/lc/lcapi.php");
require_once("lib/php/lc/LCAction.php");
require_once("lib/php/lc/PrefixUtil.php");
# Used by the Platform Bridge
define("INTEGRATION_MODULE_NAME", "moodle");
define("INTEGRATION_MODULE_VERSION", $CFG->release);
define("LIVECLASSROOM_MODULE_NAME", "classroom");
define("LIVECLASSROOM_MODULE_VERSION", "5.1.0");
if (!defined('WC'))
    define("WC", "liveclassroom");

/**
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function liveclassroom_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return false;
        case FEATURE_MOD_INTRO:               return false;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        default: return null;
    }
}


/**
 * Validate the data in passed in the configuration page
 * 
 * @param  $config - the information from the form mod.html
 * @return nothing , but returns an error if the configuration is wrong
 */
function liveclassroom_process_options (&$config)
{
    global $CFG, $USER, $COURSE;
    /*******
    we do the following verfication before submitting the configuration
    -The parameters sent can not be empty
    -The url of the server can not finish with a /
    -The url must start with http:// 
    -The api account has to valid
    ********/
    
    $config->servername    = trim($config->servername);
    $config->adminusername = trim($config->adminusername);
    $config->adminpassword = trim($config->adminpassword);

    if (! isadmin($USER->id)) 
    {
        wimba_add_log(WIMBA_ERROR,WC,get_string('wrongconfigurationURLunavailable', 'liveclassroom'));
        print_error(get_string('errormustbeadmin', 'liveclassroom'));
    } 

    if (empty($config->servername)) 
    {
        wimba_add_log(WIMBA_ERROR,WC,get_string('wrongconfigurationURLunavailable', 'liveclassroom'));
        print_error(get_string('wrongconfigurationURLunavailable', 'liveclassroom'), $_SERVER["HTTP_REFERER"]);
    } 
    else if (empty($config->adminusername)) 
    {
        wimba_add_log(WIMBA_ERROR,WC,get_string('emptyAdminUsername', 'liveclassroom'));
        print_error(get_string('emptyAdminUsername', 'liveclassroom'), $_SERVER["HTTP_REFERER"]);
    } 
    else if (empty($config->adminpassword)) 
    {
        wimba_add_log(WIMBA_ERROR,WC,get_string('emptyAdminPassword', 'liveclassroom'));
        print_error(get_string('emptyAdminPassword', 'liveclassroom'), $_SERVER["HTTP_REFERER"]);
    } 

    $length = strlen($config->servername);
    if ($config->servername {$length-1} == '/') 
    {
        wimba_add_log(WIMBA_ERROR,WC,get_String('trailingSlash', 'liveclassroom'));
        print_error(get_String('trailingSlash', 'liveclassroom'), $_SERVER["HTTP_REFERER"]);
    } 
    
    if (!preg_match('/^http:\/\//', $config->servername) && !preg_match('/^https:\/\//', $config->servername))
    {
        wimba_add_log(WIMBA_ERROR,WC,get_String('trailingHttp', 'liveclassroom'));    
        print_error(get_String('trailingHttp', 'liveclassroom'), $_SERVER["HTTP_REFERER"]);
    } 
    $prefixUtil = new PrefixUtil();
    $prefix = $prefixUtil->getPrefix($config->adminusername);
    $api = new LCApi($config->servername,
        $config->adminusername,
        $config->adminpassword, $prefix, $COURSE->id);

    $api->lcapi_invalidate_auth();
    if (! $api->lcapi_authenticate()) 
    {
        if($api->lcapi_get_error() == LCAPI_EADDR) {
            wimba_add_log(WIMBA_ERROR,WC,get_string('wrongconfigurationURLincorrect', 'liveclassroom'));
            print_error(get_string('wrongconfigurationURLincorrect', 'liveclassroom'), $_SERVER["HTTP_REFERER"]);
        } else if ($api->lcapi_get_error() == LCAPI_EAUTH) {
            wimba_add_log(WIMBA_ERROR,WC,get_string('wrongadminpass', 'liveclassroom'));
            print_error(get_string('wrongadminpass', 'liveclassroom'), $_SERVER["HTTP_REFERER"]);
        } else if($api->lcapi_get_error() == LCAPI_EHTTPS) {
            wimba_add_log(WIMBA_ERROR,WC,get_string('httpsnotenabled', 'liveclassroom'));
            print_error(get_string('configfailed','liveclassroom').': '.get_string('httpsnotenabled', 'liveclassroom'), $_SERVER["HTTP_REFERER"]);
        } else if($api->lcapi_get_error() == LCAPI_EHTTP) {
            wimba_add_log(WIMBA_ERROR,WC,get_string('httpsrequired', 'liveclassroom'));
            print_error(get_string('configfailed','liveclassroom').': '.get_string('httpsrequired', 'liveclassroom'), $_SERVER["HTTP_REFERER"]);
        } else if ($api->lcapi_get_error()) {
            wimba_add_log(WIMBA_ERROR,WC,'lcapi_get_error() returned '.$api->lcapi_get_error());
        }
    } 
    $domxml = false;
    $php_extension = get_loaded_extensions();
    for( $i = 0; $i< count($php_extension); $i++)
    {
        if($php_extension[$i] == "libxml" || $php_extension[$i] == "domxml")
        {
             $domxml=true;
        }
    }
    if($domxml === false)
    {
        wimba_add_log(WIMBA_ERROR,WC,get_string('domxml', 'liveclassroom'));
        print_error(get_string('domxml', 'liveclassroom'), $_SERVER["HTTP_REFERER"]);
    }
    return;
} 

/*
*  Create a new instance of liveclassroom
* @param $liveclassroom : object liveclassroom
*/
function liveclassroom_add_instance($liveclassroom)
{

    global $CFG, $DB;
    // / Given an object containing all the necessary data,
    // / (defined by the form in mod.html) this function
    // / will create a new instance and return the id number
    // / of the new instance.
    $liveclassroom->timemodified = time();
    $liveclassroom->type = $liveclassroom->resource;
    
    $api = new LCAction(null, 
                        $CFG->liveclassroom_servername, 
                        $CFG->liveclassroom_adminusername, 
                        $CFG->liveclassroom_adminpassword, 
                        $CFG->dataroot, $liveclassroom->course);
    // May have to add extra stuff in here #
    $roomname = $api->getRoomName($liveclassroom->type); 
        
    if (!$liveclassroom->id  = $DB->insert_record("liveclassroom", $liveclassroom)) {
        wimba_add_log(WIMBA_ERROR,WC,"Problem to add a new instance");
        return false;
    } 
    
    if (isset($liveclassroom->calendar_event) && $liveclassroom->calendar_event == true) 
    { // no problem
        liveclassroom_addCalendarEvent($liveclassroom, $liveclassroom->id, $roomname);
    } 
    
    wimba_add_log(WIMBA_INFO,WC,"Add Instance");  
    //for the debug
    wimba_add_log(WIMBA_DEBUG,WC,print_r($liveclassroom, true )); 
    return $liveclassroom->id;
} 

function liveclassroom_update_instance($liveclassroom)
{
	global $CFG, $DB;
    // / Given an object containing all the necessary data,
    // / (defined by the form in mod.html) this function
    // / will update an existing instance with new data.
    $liveclassroom->timemodified = time();
    $liveclassroom->type = $liveclassroom->resource;
    $liveclassroom->id = $liveclassroom->instance;
    $api = new LCAction(null, 
                        $CFG->liveclassroom_servername, 
                        $CFG->liveclassroom_adminusername, 
                        $CFG->liveclassroom_adminpassword, 
                        $CFG->dataroot, $liveclassroom->course);
    // May have to add extra stuff in here #
    $roomname = $api->getRoomName($liveclassroom->type); 
    // Need to update the section
    // get the course_module instance linked to the liveclassroom instance
    if (! $cm = get_coursemodule_from_instance("liveclassroom", $liveclassroom->id, $liveclassroom->course))
    {
        wimba_add_log(WIMBA_ERROR,WC,"Problem to update the instance : ".$liveclassroom->id); 
        print_error("Course Module ID was incorrect");
    }
    // setup "newmod" object with proper fields to send to "add_mod_to_section"
    $course_section = $DB->get_record("course_sections", array("id" => $cm->section));
    $newmod->course = $cm->course; 
    $newmod->section = $course_section->section; 
    $newmod->id = $cm->id; 
    $newmod->coursemodule = $cm->id;
    // Find the right section in the course_section
    $section = $DB->get_record("course_sections", array("id" => $cm->section));
    // Get the course_module.id in the current sequence that comes after ours.
    // We need this information so we preserve the order in the section after 
    // do a delete/insert
    $modarray = explode(",", $section->sequence);
    if ($key = array_keys ($modarray, $cm->id)) {
	if (count($modarray) <= $key[0]+1) {
	    $beforemod = NULL; //it is already at the end of the list
	}
	else {
            $beforemod->id = $modarray[$key[0]+1];
	}
    }
    else {
        wimba_add_log(WIMBA_ERROR,WC,"Can not find course_module_id=".$cm->id." in current sequence : ".$section->sequence); 
	$beforemod=NULL;
    }
    // delete in the course section
    if (! delete_mod_from_section($cm->id, $cm->section)) 
    {
        $result = false;
        print_error("Could not delete the $cm->modulename from that section");
    } 
    // update the course module section
    if (! $sectionid = add_mod_to_section($newmod,$beforemod)) 
    {
        wimba_add_log(WIMBA_ERROR,WC,"Problem to update the instance : ".$liveclassroom->id);   
        print_error("Could not add the new course module to that section");
    } 
    // update the course modules
    if (! $DB->set_field("course_modules", "section", $sectionid, array("id" => $cm->id)))
    {
        wimba_add_log(WIMBA_ERROR,WC,"Problem to update the instance : ".$liveclassroom->id);     
        print_error("Could not update the course module with the correct section");
    } 

    if (!isset($liveclassroom->section)) 
    {
        $liveclassroom->section = 0;
    } 

    $instanceNumber = $DB->update_record("liveclassroom", $liveclassroom);
    if ($instanceNumber != false && isset($liveclassroom->calendar_event) && $liveclassroom->calendar_event) { // no problem
        liveclassroom_addCalendarEvent($liveclassroom, $liveclassroom->instance, $roomname);
    } 
    else 
    {
        liveclassroom_deleteCalendarEvent($liveclassroom->instance);
    } 
    return $instanceNumber;
} 

function liveclassroom_delete_instance($id)
{
    global $CFG, $DB;
    // / Given an ID of an instance of this module,
    // / this function will permanently delete the instance
    // / and any data that depends on it.
    // May have to add extra stuff in here #

    if (! $liveclassroom = $DB->get_record("liveclassroom", array("id" => "$id")))
    {
        return false;
    }
    $api = new LCAction(null, 
                        $CFG->liveclassroom_servername, 
                        $CFG->liveclassroom_adminusername, 
                        $CFG->liveclassroom_adminpassword, 
                        $CFG->dataroot,$liveclassroom->course);

    $result = true; 
    // Delete any dependent records here #
    if (! $DB->delete_records("liveclassroom", array("id" => $liveclassroom->id)))
    {
        wimba_add_log(WIMBA_ERROR,WC,"Problem to delete the instance : ".$liveclassroom->id); 
        $result = false;
    } 
    
    liveclassroom_deleteCalendarEvent("$liveclassroom->id");
    return $result;
} 

function liveclassroom_user_outline($course, $user, $mod, $liveclassroom)
{
    // / Return a small object with summary information about what a
    // / user has done with a given particular instance of this module
    // / Used for user activity reports.
    // / $return->time = the time they did it
    // / $return->info = a short text description
    return false; //$return;
} 

function liveclassroom_user_complete($course, $user, $mod, $liveclassroom)
{
    // / Print a detailed representation of what a  user has done with
    // / a given particular instance of this module, for user activity reports.
    return true;
} 

function liveclassroom_print_recent_activity($course, $isteacher, $timestart)
{
    // / Given a course and a time, this module should find recent activity
    // / that has occurred in liveclassroom activities and print it out.
    // / Return true if there was output, or false is there was none.
    global $CFG;

    return false; //  True if anything was printed, otherwise false 
} 

function liveclassroom_cron ()
{
    // / Function to be run periodically according to the moodle cron
    // / This function searches for things that need to be done, such
    // / as sending out mail, toggling flags etc ...
    global $CFG;

    return true;
} 

function liveclassroom_grades($liveclassroomid)
{
    // / Must return an array of grades for a given instance of this module,
    // / indexed by user.  It also returns a maximum allowed grade.
    // /
    // /    $return->grades = array of grades;
    // /    $return->maxgrade = maximum allowed grade;
    // /
    // /    return $return;
    return null;
} 

function liveclassroom_get_participants($liveclassroomid)
{
    // Must return an array of user records (all data) who are participants
    // for a given instance of liveclassroom. Must include every user involved
    // in the instance, independient of his role (student, teacher, admin...)
    // See other modules as example.
    return false;
} 

function liveclassroom_scale_used ($liveclassroomid, $scaleid)
{
    // This function returns if a scale is being used by one liveclassroom
    // it it has support for grading and scales. Commented code should be
    // modified if necessary. See forum, glossary or journal modules
    // as reference.
    $return = false; 

    return $return;
} 

/**
 * CALENDAR
 */
function liveclassroom_addCalendarEvent($activity_informations, $instanceNumber, $name)
{ 
    // / Basic event record for the database.
    global $CFG, $DB;

    $event = new Object();
    $event->name        = $activity_informations->name;
    $event->description = $activity_informations->description . "<br><a href=" . $CFG->wwwroot . "/mod/liveclassroom/view.php?id=" . $instanceNumber . "&action=launchCalendar target=_self >" . get_string("launch_calendar", "liveclassroom") ." ".$name. " ...</a>";
    $event->format      = 1;
    $event->userid      = 0;
    $event->courseid    = $activity_informations->course; //course event
    $event->groupid     = 0;
    $event->modulename  = 'liveclassroom';
    $event->instance    = $instanceNumber;
    $event->eventtype   = '';
    $event->visible     = 1;
    $event->timemodified = time();
   
    $start_hr = $activity_informations->calendar['h'];
    if ($activity_informations->calendar['A'] == 'PM')
        $start_hr += 12;
    if($activity_informations->course_format !="weeks" && $activity_informations->course_format !="weekscss")
    {//topics or social
        $event->timestart = mktime($start_hr, $activity_informations->calendar['i'], 0, $activity_informations->calendar['F'], $activity_informations->calendar['d'], $activity_informations->calendar['Y']);
    }
    else
    {
        $event->timestart = $activity_informations->calendar_start + ($activity_informations->calendar['l']+1) * 86400 + $start_hr * 3600 + $activity_informations->calendar['i'] * 60;
    }
    
    $duration = $activity_informations->duration_hrs*3600 + $activity_informations->duration_min*60;
    if ($duration < 0)
    {
        $event->timeduration = 0;
    }
    else 
    {
        $event->timeduration = $duration;
    }  
    
    wimba_add_log(WIMBA_DEBUG,WC,"Add calendar event\n".print_r($event, true ));  

    $oldEvent = $DB->get_record('event',array('instance' => $activity_informations->id, 'modulename' => 'liveclassroom'));
    if(!empty($oldEvent) &&  $oldEvent!=false) //old event exsit    exsit 
    {  
        $event->id =  $oldEvent->id  ;
        $result = $DB->update_record('event', $event);
    }
    else
    {
        $result = $DB->insert_record('event', $event);
    }       

    return $result;
} 

function liveclassroom_deleteCalendarEvent($instanceNumber)
{ 
    // / Basic event record for the database.
    global $CFG, $DB;
    $oldEvent = $DB->get_record('event', array('instance' => $instanceNumber, 'modulename' => 'liveclassroom'));

    if (!empty($oldEvent) && $oldEvent != false) {
        $result = $DB->delete_records("event", array("id" => $oldEvent->id));
    } else {
        return false;
    } 
    return $result;
} 
/**
 * get the calendar event which matches the id
 * 
 * @param  $id - the voicetool instance
 * @return the calendar event or false
 */
function liveclassroom_get_event_calendar($id)
{
    global $DB;
    $event = $DB->get_record('event', array('instance' => $id, 'modulename' => 'liveclassroom'));
    if ($event === false || empty($event)) {
        return false;
    } 
    return $event;
} 

/*
* Give the shortname for a courseid given
* @param $courseid : the id of the course
* Return a string : the shortname of the course
*/
function liveclassroom_get_course_shortname($courseid)
{
    global $DB;
    if (!($course = $DB->get_record('course', array('id' => $courseid))))
    {
        // error( "Response get room name: query to database failed");
        return false;
    } 
    // $name = $course->shortname;
    return $course->shortname;
} 

/*
* Delete all the activities on Moodle database for a room given
* @praram $roomid : the id of the room associated to the activities
*  return a boolean true if all is well done
*/
function liveclassroom_delete_all_instance_of_room($roomid,$prefix)
{
    global $CFG, $COURSE, $DB;
    // / Given an ID of an instance of this module,
    // / this function will permanently delete the instance
    // / and any data that depends on it.
    $api = new LCApi($CFG->liveclassroom_servername,
                     $CFG->liveclassroom_adminusername,
                     $CFG->liveclassroom_adminpassword,$prefix,$COURSE->id);

    $result = true;
    if ($liveclassrooms = $DB->get_records("liveclassroom", array("type" => $roomid)))
    {
        $roomname = $api->lcapi_get_room_name($liveclassroom->type); 
        // Delete any dependent records here #
        foreach($liveclassrooms as $liveclassroom) 
        {
            // get the course_module instance linked to the liveclassroom instance
            if (! $cm = get_coursemodule_from_instance("liveclassroom", $liveclassroom->id, $liveclassroom->course))
            {
                print_error("Course Module ID was incorrect");
            } 
            if (! delete_course_module($cm->id)) 
            {
                wimba_add_log(WIMBA_ERROR,WC,"Problem to delete the course module : ".$cm->id);  
                $result = false; 
                // Delete a course module and any associated data at the course level (events)
                // notify("Could not delete the $cm->id (coursemodule)");
            } 
            if (! $DB->delete_records("liveclassroom", array("id" => "$liveclassroom->id")))
            {
                wimba_add_log(WIMBA_ERROR,WC,"Problem to delete all the activities associated to the voice tools");  
                $result = false;
            } 
            // delete in the course section too
            if (! delete_mod_from_section($cm->id, "$cm->section")) 
            {
                wimba_add_log(WIMBA_ERROR,WC,"Could not delete the ".$cm->id." from that section : ".$cm->section);
                $result = false; 
                // notify("Could not delete the $mod->modulename from that section");
            } 
        } 
    } 
    
    return $result;
} 

function liveclassroom_getRole($context)
{
    global $CFG;
    global $USER;
    $role = "";

    if (has_capability('mod/liveclassroom:presenter', $context)) {
      $role = 'Instructor';
    } else {
      $role = 'Student';
    }

    return $role;
}

function liveclassroom_get_url_params($courseid)
{
    global $USER;
    global $CFG;

    $context = get_context_instance(CONTEXT_COURSE, $courseid) ;
    
    $firstname = $USER->firstname;
    $lastname = $USER->lastname;
    $email = $USER->email;

    $role = liveclassroom_getRole($context);
    wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": courseid=$courseid - email=$email - firstname=$firstname - lastname=$lastname - role=$role");
    $signature = md5($courseid . $email . $firstname . $lastname . $role);
    wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": ENCODED courseid=".wimbaEncode($courseid)." - email=".wimbaEncode($email)." - firstname=".wimbaEncode($firstname)." - lastname=".wimbaEncode($lastname)." - role=".wimbaEncode($role));
     wimba_add_log(WIMBA_DEBUG,WC,__FUNCTION__ . ": signature=$signature");
    $url_params = "enc_course_id=" . wimbaEncode($courseid) . "&enc_email=" . wimbaEncode($email) . "&enc_firstname=" . wimbaEncode($firstname) . "&enc_lastname=" . wimbaEncode($lastname) . "&enc_role=" . wimbaEncode($role) . "&signature=" . wimbaEncode($signature);
    return $url_params;
}


/* Management of the reset functionnality */


/**
* Implementation of the function for printing the form elements that control
* whether the course reset functionality affects the chat.
* @param $mform form passed by reference
*/
 function liveclassroom_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'classroomheader', get_string('modulenameplural', 'liveclassroom'));   
    $mform->addElement('checkbox', 'reset_content_liveclassroom_all', "Delete all the archives and content");
    $mform->addElement('checkbox', 'reset_content_liveclassroom_archives', "Delete only the archives");
  
    $mform->disabledIf('reset_content_liveclassroom_all', 'reset_content_liveclassroom_archives', 'checked');
    $mform->disabledIf('reset_content_liveclassroom_archives','reset_content_liveclassroom_all',  'checked');

}
/**
 * For version < 1.9
* Implementation of the function for printing the form elements that control
* whether the course reset functionality affects the chat.
* @param $mform form passed by reference
*/
 function liveclassroom_reset_course_form($course) {
     global $DB;
     $activities = $DB->get_record("liveclassroom", array("course" => $course->id));
  
    if($activities)
    {
        print_checkbox('reset_content_liveclassroom_all', 1, false, "Delete all the archives and content", '', "if (this.checked) {document.getElementsByName('reset_content_liveclassroom_archive')[0].disabled = 'true'} else {document.getElementsByName('reset_content_liveclassroom_archive')[0].disabled=''}");  echo '<br />';
        print_checkbox('reset_content_liveclassroom_archives',1, false, "Delete only the archives", '', "if (this.checked) {document.getElementsByName('reset_content_liveclassroom_all')[0].disabled = 'true'} else {document.getElementsByName('reset_content_liveclassroom_all')[0].disabled=''}");  echo '<br />';
    }
    else
    {
         echo "There is no Blackboard Collaborate Classroom in this course";  
    }
}
 
/**
* Actual implementation of the rest coures functionality, delete all the
* chat messages for course $data->courseid.
* @param $data the data submitted from the reset course.
* @return array status array
*/
function liveclassroom_delete_userdata($data, $showfeedback=true) {
   global $CFG, $COURSE, $DB;

   $componentstr = get_string('modulenameplural', 'liveclassroom');

   if (!empty($data->reset_content_liveclassroom_all)) 
   {
       $api = new LCAction(null,$CFG->liveclassroom_servername,
                        $CFG->liveclassroom_adminusername,
                        $CFG->liveclassroom_adminpassword,$CFG->dataroot,$data->id); 
       $rooms=$api->getRooms($data->id."_T");
      
       foreach ($rooms as $room)
       {
           if($room->isArchive() == 0)
           { 
                $isAdmin=$api->isStudentAdmin($room->getRoomId(), $data->id."_S");
                $api->cloneRoom($data->id,$room->getRoomId(),"0",$isAdmin,$room->isPreview());
                if($isAdmin == "true")
                {    
                    $api->removeRole($room->getRoomId(), $data->id."_S", "Student");
                    $api->removeRole($room->getRoomId(), $data->id."_T", "ClassAdmin");
                }
                else
                {
                    $api->removeRole($room->getRoomId(), $data->id."_S", "Instructor");
                    $api->removeRole($room->getRoomId(), $data->id."_T", "ClassAdmin");
                }
           }
           else
           {
                $api->deleteRoom($room->getRoomId());
           }
           $activities = $DB->get_records("liveclassroom", array("id" => $room->getRoomId()));
            foreach (array_keys($activities) as $id) 
            {
                $activities[$id]->rid=new_rid;
                $DB->update_record("liveclassroom",$activities[$id]);
                
            }
       }
       $typesstr = "Delete all the archives and content";    
        
   }
   else  if (!empty($data->reset_content_liveclassroom_archives)) 
   {
             $api = new LCAction(null,$CFG->liveclassroom_servername,
                        $CFG->liveclassroom_adminusername,
                        $CFG->liveclassroom_adminpassword,$CFG->dataroot,$data->id); 
       $rooms=$api->getRooms($data->id."_T");
      
       foreach ($rooms as $room)
       {
           if($room->isArchive() == 1)
           { 
                $api->deleteRoom($room->getRoomId());
           }
       }
       $typesstr = "Delete only the archives";
      
   }
  
   if($showfeedback)
   {
        $strreset = get_string('reset');
        notify($strreset.': '.$typestr, 'notifysuccess');
   }
}


/**
* Actual implementation of the rest coures functionality, delete all the
* chat messages for course $data->courseid.
* @param $data the data submitted from the reset course.
* @return array status array
*/
function liveclassroom_reset_userdata($data,$showfeedback=true) {
   global $CFG, $COURSE, $DB;

   $componentstr = get_string('modulenameplural', 'liveclassroom');
   $status = array();
   if (!empty($data->reset_content_liveclassroom_all)) 
   {
       $api = new LCAction(null,$CFG->liveclassroom_servername,
                        $CFG->liveclassroom_adminusername,
                        $CFG->liveclassroom_adminpassword,$CFG->dataroot,$data->id);
       $rooms=$api->getRooms($data->id."_T");
      
       foreach ($rooms as $room)
       {
           if($room->isArchive() == 0)
           { 
                $isAdmin=$api->isStudentAdmin($room->getRoomId(), $data->id."_S");
                $api->cloneRoom($data->id,$room->getRoomId(),"0",$isAdmin,$room->isPreview());
                if($isAdmin == "true")
                {    
                    $api->removeRole($room->getRoomId(), $data->id."_S", "Student");
                    $api->removeRole($room->getRoomId(), $data->id."_T", "ClassAdmin");
                }
                else
                {
                    $api->removeRole($room->getRoomId(), $data->id."_S", "Instructor");
                    $api->removeRole($room->getRoomId(), $data->id."_T", "ClassAdmin");
                }
      
           }
           else
           {
                $api->deleteRoom($room->getRoomId());
           }
           $activities = $DB->get_records("liveclassroom", array("id" => $room->getRoomId()));
            foreach (array_keys($activities) as $id) 
            {
                $activities[$id]->rid=new_rid;
                $DB->update_record("liveclassroom",$activities[$id]);
                
            }
       }
       $typesstr = "Delete all the archives and content";    
       $status[] = array('component'=>$componentstr, 'item'=>$typesstr, 'error'=>false);
       
   }
   else  if (!empty($data->reset_content_liveclassroom_archives)) 
   {
             $api = new LCAction(null,$CFG->liveclassroom_servername,
                        $CFG->liveclassroom_adminusername,
                        $CFG->liveclassroom_adminpassword,$CFG->dataroot,$data->id);
       $rooms=$api->getRooms($data->id."_T");
      
       foreach ($rooms as $room)
       {
           if($room->isArchive() == 1)
           { 
                $api->deleteRoom($room->getRoomId());
           }
       }
       $typesstr = "Delete only the archives";
       $status[] = array('component'=>$componentstr, 'item'=>$typesstr, 'error'=>false);
   }
   return $status;
  
}
?>
