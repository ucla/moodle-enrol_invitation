<?PHP
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2012  Blackboard Collaborate, All Rights Reserved.      *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Blackboard Collaborate.              *
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
 *      along with the Blackboard Collaborate Moodle Integration;             *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Hugues Pisapia                                                     *
 *                                                                            *  
 * Date: 15th April 2006                                                      *                                                                        *
 *                                                                            *
 ******************************************************************************/


/* $Id: lib.php 80757 2010-12-08 21:48:45Z bdrust $ */
/// Library of functions and constants for module voicepresentation

if (!function_exists('getKeysOfGeneralParameters')) {
    require_once('lib/php/common/WimbaLib.php');
}
if(!function_exists('voicetools_api_create_resource')){
    require_once('lib/php/common/DatabaseManagement.php');
    require_once('lib/php/vt/WimbaVoicetoolsAPI.php');
    require_once('lib/php/vt/WimbaVoicetools.php');
    require_once('lib/php/vt/VtAction.php');
    
}
if (!function_exists('grade_update') && file_exists($CFG->libdir.'/gradelib.php')) { //workaround for buggy PHP versions
  require_once($CFG->libdir.'/gradelib.php');
}


require_once($CFG->libdir.'/datalib.php');
require_once($CFG->dirroot.'/course/lib.php');


define("voicepresentation_MODULE_VERSION", "3.3.0");
define("voicepresentation_LOGS", "voicepresentation");

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
function voicepresentation_supports($feature) {
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
* @param $config - the information from the form mod.html
*/
function voicepresentation_process_options ($config) {
   global $CFG;
  
  /*******
    we do the following verfication before submitting the configuration
  	-The parameters sent can not be empty
  	-The url of the server can not finish with a /
  	-The url must start with http:// or https://
  	-The api account has to valid
  ********/
   $config->module="voicetools";
    if(empty($config->servername))
    {
       wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,get_string('wrongconfigurationURLunavailable', 'voicepresentation'));
       print_error(get_string('wrongconfigurationURLunavailable', 'voicepresentation'), $_SERVER["HTTP_REFERER"]);
    }
    
    if(empty($config->adminusername))
    {
        wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,get_string('emptyAdminUsername', 'voicepresentation'));
        print_error(get_string('emptyAdminUsername', 'voicepresentation'), $_SERVER["HTTP_REFERER"]);
    }  
    
    if(empty($config->adminpassword))
    {
        wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,get_string('emptyAdminPassword', 'voicepresentation'));
        print_error(get_string('emptyAdminPassword', 'voicepresentation'), $_SERVER["HTTP_REFERER"]);
    } 
    
    if ($config->servername{strlen($config->servername)-1} == '/')
    {
        wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,get_String('trailingSlash', 'voicepresentation'));
        print_error(get_String('trailingSlash', 'voicepresentation'), $_SERVER["HTTP_REFERER"]);
    }
  
    if (!preg_match('/^http:\/\//', $config->servername) && !preg_match('/^https:\/\//', $config->servername)) 
    {
        wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,get_String('trailingHttp', 'voicepresentation'));    
        print_error(get_String('trailingHttp', 'voicepresentation'), $_SERVER["HTTP_REFERER"]);
    }  
    
    //check if the api account filled is correct and allowed
    $result = voicetools_api_check_documentbase ($config->servername, $config->adminusername,$config->adminpassword,$CFG->wwwroot);  
    
    if ($result != "ok") 
    {
        if(get_string($result, 'voicepresentation' ) == "[[]]")
        {//the error description is not in the bundle
            wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,$result);
            print_error($result, 'javascript:history.back();');
        }  
        else{
            wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,get_String($result, 'voicepresentation'));
            print_error(get_string($result, 'voicepresentation' ), 'javascript:history.back();');
        }
    }
    
    //to make sure that all the necessary module are installed
    wimba_add_log(WIMBA_INFO,voicepresentation_LOGS,"php info :\n" .print_r(get_loaded_extensions(),true)); 
    
    wimba_add_log(WIMBA_INFO,voicepresentation_LOGS,"The module is well configured");
}

function voicepresentation_add_instance($voicetool) {
  /// Given an object containing all the necessary data, 
  /// (defined by the form in mod.html) this function 
  /// will create a new instance and return the id number 
  /// of the new instance.
  global $USER, $DB;

  //get the resource information(type and id)               
  $voicetool->timemodified = time();  
  $voicetool->rid = $voicetool->resource;     
  $voicetool->name = $voicetool->name;  
 
   if (!$voicetool->id = $DB->insert_record('voicepresentation', $voicetool)) 
  {
      wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,"Problem to add a new instance");  
      return false;
  }
  
  if(isset($voicetool->calendar_event) &&  $voicetool->calendar_event==true) 
  { 
      voicepresentation_addCalendarEvent($voicetool,$voicetool->id); 
  }
  
  
  
  $resource = $DB->get_record('voicepresentation_resources',array("rid"=>$voicetool->resource));
  if ($resource->gradeid != -1) { //the grade settings is enabled for this resource
     $activity = $DB->get_record('voicepresentation',array("id"=>$resource->gradeid));
     if(empty($activity)){//the grade id is not a valid activity id( random number from delete process)
         //now a valid activity is linked to the resource, we have to update the grade.
         
         $students=getStudentsEnrolled($resource->course);
         $users_key = array_keys($students);
         
         $oldgrade = grade_get_grades($voicetool->course, "mod", "voicepresentation", $resource->gradeid,$users_key);
         if(isset($oldgrade->items[0]))
         {
           voicepresentation_delete_grade_column($resource->rid,$resource->course);
           $resource->gradeid = voicepresentation_add_grade_column($resource->rid,$resource->course,$oldgrade->items[0]->name,$oldgrade->items[0]->grademax,voicepresentation_build_gradeObject_From_ArrayOfGradeInfoObjects($oldgrade->items[0]->grades));
         
           $DB->update_record('voicepresentation_resources',$resource);
         }
     }
   
  }
  
 
  wimba_add_log(WIMBA_INFO,voicepresentation_LOGS,"Add Instance".$voicetool->id);  
  //for the debug
  wimba_add_log(WIMBA_DEBUG,voicepresentation_LOGS,print_r($voicetool, true )); 
  return $voicetool->id;  
}    

function voicepresentation_update_instance($voicetool) {
  /// Given an object containing all the necessary data, 
  /// (defined by the form in mod.html) this function 
  /// will update an existing instance with new data.
    global $USER, $DB;

    //get the course_module instance linked to the liveclassroom instance
    if (! $cm = get_coursemodule_from_instance("voicepresentation", $voicetool->instance, $voicetool->course)) 
    {
        wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,"Problem to update the instance : ".$voicetool->instance); 
        print_error("Course Module ID was incorrect");
    }      
    
    if($voicetool->section != $cm->section)//the scetion has changed
    {
        //Find the right section in the course_section
        if (!$section = $DB->get_record("course_sections", array("id" => $cm->section)))
        {
            return false;
        }
        // setup "newmod" object with proper fields to send to "add_mod_to_section"
		$course_section = $DB->get_record("course_sections", array("id" => $cm->section));
		$newmod->course = $cm->course; 
		$newmod->section = $course_section->section; 
		$newmod->id = $cm->id; 
		$newmod->coursemodule = $cm->id; 
		// Get the course_module.id in the current sequence that comes after ours.
		// We need this information so we preserve the order in the section after 
		// do a delete/insert
		$modarray = explode(",", $section->sequence);
		if ($key = array_keys ($modarray, $cm->id)) {
			if (count($modarray) <= $key[0]+1) {
    			$beforemod = NULL; //it is already at the end of the list
			} else {
				$beforemod->id = $modarray[$key[0]+1];
			}
		} else {
			wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,"Can not find course_module_id=".$cm->id." in current sequence : ".$section->sequence); 
			$beforemod=NULL;
		}
        //delete in the course section
        if (! delete_mod_from_section($cm->id, $cm->section)) 
        {
            return false;
        }
        
        //update the course module section
        if (! $sectionid = add_mod_to_section($newmod,$beforemod) ) 
        {
            wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,"Problem to update the instance : ".$voicetool->instance); 
            print_error("Could not add the new course module to that section");
        }
        //update the course modules  
        if (! $DB->set_field("course_modules", "section", $sectionid, array("id" => $cm->id)))
        {
            wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,"Problem to update the instance : ".$voicetool->instance); 
            print_error("Could not update the course module with the correct section");
        }
    }
  
    $voicetool->timemodified = time();  
    $voicetool->id = $voicetool->instance;
    $voicetool->rid = $voicetool->resource;     
    $voicetool->name = $voicetool->name;  
    
    if (!$voicetool->id = $DB->update_record('voicepresentation', $voicetool)) 
    {
      return false;
    }
    
    if(isset($voicetool->calendar_event) && $voicetool->calendar_event) 
    {//no problem
        voicepresentation_addCalendarEvent($voicetool,$voicetool->instance); 
    } 
    else 
    {
        voicepresentation_deleteCalendarEvent($voicetool->instance );          
    }
    
    wimba_add_log(WIMBA_INFO,voicepresentation_LOGS,"Update of the instance : ".$voicetool->id); 
    return $voicetool->id ;
}


function voicepresentation_delete_instance($id) {
    global $DB;
    /// Given an ID of an instance of this module, 
    /// this function will permanently delete the instance 
    /// and any data that depends on it.  
    $result = true;  
    if (! $voicetool = $DB->get_record("voicepresentation", array("id" => $id)))
    {
        return false;
    }
  
    # Delete any dependent records here #
    if (! $instanceNumber=$DB->delete_records("voicepresentation", array("id" => $voicetool->id)))
    {
        wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,"Problem to delete the instance : ".$voicetool->id); 
        $result = false;
    } 

    if("voicepresentation" == "voiceboard"){
      $resource = $DB->get_record("voicepresentation_resources", array("rid" => $voicetool->rid));
      if($resource->gradeid !=  -1){
         
        $students  = getStudentsEnrolled( $voicetool->course);
        $users_key = array_keys($students);
         
        $oldgrade = grade_get_grades($voicetool->course, "mod", "voicepresentation", $resource->gradeid,$users_key);
        if(isset($oldgrade->items[0])){
          //the activity linked to the grade is no longer available
          //If there is still an activity linked to the resource, we will use this one,
          //else we will use an random numer as instance number(the title of the column will not be a link)
          voicepresentation_delete_grade_column($resource->rid,$voicetool->course);
          $resource->gradeid = voicepresentation_add_grade_column($resource->rid,$voicetool->course,$oldgrade->items[0]->name,$oldgrade->items[0]->grademax,voicepresentation_build_gradeObject_From_ArrayOfGradeInfoObjects($oldgrade->items[0]->grades));
        }
        $DB->update_record('voicepresentation_resources', $resource);
      }
    }
    voicepresentation_deleteCalendarEvent($voicetool->id);
    // delete the related calendar event
       
    return $result;
}
  

function voicepresentation_addCalendarEvent($activity_informations,$instanceNumber){
    global $CFG, $DB;

    //get some complementary of the resource       
    $resource=$DB->get_record('voicepresentation_resources',array('rid'=>$activity_informations->rid));

    $event = new Object();
    $event->name         = $activity_informations->name;
    $event->description  = $activity_informations->description."<br><a href=".$CFG->wwwroot."/mod/voicepresentation/view.php?id=".$activity_informations->id."&action=launchCalendar target=_self >".get_string("launch_calendar","voicepresentation").get_string("presentation","voicepresentation")." ...</a>";   
    $event->format       = 1;           
    $event->userid       = 0;    
    $event->courseid     = $activity_informations->course;  //course event
    $event->groupid      = 0;
    $event->modulename   = 'voicepresentation';
    $event->instance     = $instanceNumber;
    $event->eventtype    = '';
    $event->visible      = 1;
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
    
    $duration = $activity_informations->duration_hrs * 3600 + $activity_informations->duration_min * 60;
    if ($duration < 0)
    {
        $event->timeduration = 0;
    }
    else 
    {
        $event->timeduration = $duration;
    }  
    
    wimba_add_log(WIMBA_DEBUG,voicepresentation_LOGS,"Add calendar event\n".print_r($event, true ));  
    
    $oldEvent=$DB->get_record('event',array('instance' => $instanceNumber, 'modulename' => "voicepresentation"));
    if(!empty($oldEvent) &&  $oldEvent!=false) //old event exsit    
    {  
        $event->id =  $oldEvent->id  ;
        $result=$DB->update_record('event', $event);
    }
    else
    {
        $result = $DB->insert_record('event', $event);
    }

    return $result;
}


function voicepresentation_deleteCalendarEvent($instanceNumber){
  /// Basic event record for the database.
    global $CFG, $DB;

    if(!($event=$DB->get_record('event',array('instance'=>$instanceNumber,'modulename'=>"voicepresentation"))))
    {
        wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,"Problem to delete calendar event : ".$instanceNumber); 
        return false;
    }
    $result=$DB->delete_records("event", array("id" => $event->id));
}


function voicepresentation_delete_resource($rid) {
    global $DB;
    /// Given an ID of an instance of this module, 
    /// this function will permanently delete the instance 
    /// and any data that depends on it.  

    if (! ($voicetool = $DB->get_record("voicepresentation_resources", array("rid" => $rid)))) {
        wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,"Problem to delete a resource : ".$rid); 
        return false;
    }
    # Delete any dependent records here #
    if (! $DB->delete_records("voicepresentation_resources", array("id" => "$voicetool->id"))) {
        wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,"Problem to delete a resource : ".$rid); 
        return false;
    } 
       
    return true;
}

function voicepresentation_user_outline($course, $user, $mod, $voicetool) {
  /// Return a small object with summary information about what a 
  /// user has done with a given particular instance of this module
  /// Used for user activity reports.
  /// $return->time = the time they did it
  /// $return->info = a short text description
    return $return;
}

function voicepresentation_user_complete($course, $user, $mod, $voicetool) {
  /// Print a detailed representation of what a  user has done with 
  /// a given particular instance of this module, for user activity reports.

  return true;
}

function voicepresentation_print_recent_activity($course, $isteacher, $timestart) {
  /// Given a course and a time, this module should find recent activity 
  /// that has occurred in voicetool activities and print it out. 
  /// Return true if there was output, or false is there was none.

  global $CFG;

  return false;  //  True if anything was printed, otherwise false 
}

function voicepresentation_cron () {
  /// Function to be run periodically according to the moodle cron
  /// This function searches for things that need to be done, such 
  /// as sending out mail, toggling flags etc ... 

  global $CFG;

  return true;
}

function voicepresentation_grades($voicetoolid) {
  /// Must return an array of grades for a given instance of this module, 
  /// indexed by user.  It also returns a maximum allowed grade.
  ///
  ///    $return->grades = array of grades;
  ///    $return->maxgrade = maximum allowed grade;
  ///
  ///    return $return;

  return NULL;
}

function voicepresentation_get_participants($voicetoolid) {
  //Must return an array of user records (all data) who are participants
  //for a given instance of voicetool. Must include every user involved
  //in the instance, independient of his role (student, teacher, admin...)
  //See other modules as example.

  return false;
}

function voicepresentation_scale_used ($voicetoolid,$scaleid) {
  //This function returns if a scale is being used by one voicetool
  //it it has support for grading and scales. Commented code should be
  //modified if necessary. See forum, glossary or journal modules
  //as reference.

  $return = false;

  //$rec = get_record("voicetool","id","$voicetoolid","scale","-$scaleid");
  //
  //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}

    return $return;
}


function voicepresentation_store_new_element($voicetool) {
    global $DB;
    $id = $DB->insert_record("voicepresentation_resources", $voicetool);

    return $id;
}

function voicepresentation_update_element($voicetool) {
    global $DB;
    $oldId = $DB->get_record('voicepresentation_resources',array('rid' => $voicetool->rid));

    $voicetool->id = $oldId->id;
    $id = $DB->update_record("voicepresentation_resources", $voicetool);

    return $id;
}


/*
* Delete all the activities on Moodle database for a vt given
* @praram $roomid : the id of the room associated to the activities
*  return a boolean true if all is well done
*/
function voicepresentation_delete_all_instance_of_resource($id) {
    global $DB;
/// Given an ID of an instance of this module, 
/// this function will permanently delete the instance 
/// and any data that depends on it.  

    //delete the resource of the vt list
	
    $result = true;
   	wimba_add_log(WIMBA_INFO,voicepresentation_LOGS,"Delete the resouce ". $id);
    if ($voicepresentation = $DB->get_records("voicepresentation", array("rid" => $id)))
    {
      # Delete any dependent records here #
	    foreach($voicepresentation as $voicetool){
		    //get the course_module instance linked to the liveclassroom instance
		    $cm=get_coursemodule_from_instance("voicepresentation", $voicetool->id, $voicetool->course);
            
		    if(!empty($cm)) //old event exsit
		    {
    		    if (! delete_course_module($cm->id)) 
    		    {
    		         wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,"Problem to delete the course module : ".$cm->id);  
    		         $result = false;
    		         //Delete a course module and any associated data at the course level (events)
    		    } 
    		    //delete the instance
    		    if (! $DB->delete_records("voicepresentation", array("id" => $voicetool->id)))
    		    {
                    wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,"Problem to delete all the activities associated to the voice tools");  
                    $result = false;
    		    } 
    			//delete in the course section too
    			if (! delete_mod_from_section($cm->id, $cm->section)) 
    			{
    				wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,"Could not delete the ".$cm->id." from that section : ".$cm->section);
                    $result = false;
    			}
    			voicepresentation_deleteCalendarEvent($voicetool->id);
		    }
	    }
  	}
    voicepresentation_delete_resource($id);
    return $result;
}



/**
* List all the resource for the course given
* @param $courseId - the current course id
* @return the  of the boards rid 
*         false if there is no resources
*         null if problem    
*/
function voicepresentation_get_voicetools_list($courseId) {
    global $DB;
    $tools_list = $DB->get_records('voicepresentation_resources',array('course'=>$courseId));
    $result= array();
    $result["rid"]= array();
    $result["info"]= array();

    if(empty($tools_list))
    {
        wimba_add_log(WIMBA_INFO,voicepresentation_LOGS,"No resources have been created yet");
        return $result;
    }
    else if($tools_list === false)
    {
        wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,"Problem to get the list of resources");
        print_error( "Response get_board_list : query to database failed");
        return false;
    }
    $result = array();
    foreach($tools_list as $tool) {
        //if($tool->type!="recorder")
        //{
            $result["rid"][]= $tool->rid;
            $result["info"][$tool->rid]= $tool;
       // }
    }
    wimba_add_log(WIMBA_DEBUG,voicepresentation_LOGS,"list of resource :\n".print_r($result, true ));

    return $result;
}

/**
* List all the resource for the course given
* @param $courseId - the current course id
* @return the  of the boards rid 
*/
function voicepresentation_get_voicetool_informations($rid) {
    global $DB;

    $tool = $DB->get_record('voicepresentation_resources', array('rid' => $rid));
    
    if(empty($tool))
    {
        wimba_add_log(WIMBA_INFO,voicepresentation_LOGS,"No resources have been created yet"); 
        return null;
    }
    else if($tool === false) 
    {
        wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,"Problem to get the list of resources");      
        print_error( "Response get_board_list : query to database failed");
        return "error_moodleDatabase";
    }   
    wimba_add_log(WIMBA_DEBUG,voicepresentation_LOGS,"list of resource :\n".print_r($tool, true ));      
           
    return $tool;
} 



/**
* List all the informnations(availability,start_date ..)of the board for the rid given
* @param $rid - the current course rid  of the board
* @return the list of informations 
*/
function voicepresentation_get_wimbaVoice_Informations($rid) {
    global $DB;

    if(!($board_informations = $DB->get_record('voicepresentation_resources', array('rid' => $rid))))
    {
        print_error( "Response get_board_list : query to database failed");
        return "error_moodleDatabase";
    }

  return $board_informations;
}




/**
* get the calendar event which matches the id
* @param $id - the voicetool instance 
* @return the calendar event or false 
*/
function voicepresentation_get_event_calendar($id) {
    global $DB;

  $event = $DB->get_record('event', array('instance' => $id, 'modulename' => 'voicepresentation'));
  if($event === false || empty($event)) {
  
    return false;
  }
  return $event;
}



function voicepresentation_get_version() {
    $answer = voicetools_api_get_version();
    
    if (!strcmp($answer, "error")) 
    {
        return get_string('voicepresentation', "error");
    }
    elseif (!strcmp($answer, 'unknown')) 
    {
        return get_string('voicepresentation', 'unknown');
    }
    
    return $answer;
}

function voicepresentation_getRole($context)
{
    global $CFG;
    global $USER;
    $role = "";

    if (has_capability('mod/voicepresentation:presenter', $context)) {
      $role = 'Instructor';
    } else {
      $role = 'Student';
    }

    return $role;
}

function voicepresentation_get_url_params($courseid)
{
    global $USER;
    global $CFG;

    $context = get_context_instance(CONTEXT_COURSE, $courseid) ;

    $role = voicepresentation_getRole($context);
    $signature = md5($courseid . $USER->email . $USER->firstname . $USER->lastname . $role);
    
    $url_params = "enc_course_id=" . wimbaEncode($courseid) . 
                  "&enc_email=" . wimbaEncode($USER->email) . 
                  "&enc_firstname=" . wimbaEncode($USER->firstname) . 
                  "&enc_lastname=" . wimbaEncode($USER->lastname) . 
                  "&enc_role=" . wimbaEncode($role) . 
                  "&signature=" . wimbaEncode($signature);
    return $url_params;
}


/**
* Implementation of the function for printing the form elements that control
* whether the course reset functionality affects the chat.
* @param $mform form passed by reference
*/
 function voicepresentation_reset_course_form_definition(&$mform) {
     $currentProduct="voicepresentation";
     $mform->addElement('header', 'classroomheader', get_string('modulenameplural', 'voicepresentation'));   
  
    
    if($currentProduct != "voicepodcaster"){
          $mform->addElement('checkbox',
                             'reset_content_voicepresentation_replies',
                              get_string("voicepresentation_reset_only_replies", 'voicepresentation'));
        
    }
    $mform->addElement('checkbox', 
                        'reset_content_voicepresentation', 
                        get_string("voicepresentation_reset_all", 'voicepresentation'));
    
    $mform->disabledIf('reset_content_voicepresentation', 'reset_content_voicepresentation_replies', 'checked');
    $mform->disabledIf('reset_content_voicepresentation_replies','reset_content_voicepresentation',  'checked');
                      
}

/**
* Implementation of the function for printing the form elements that control
* whether the course reset functionality affects the chat.
* @param $mform form passed by reference
*/
 function voicepresentation_reset_course_form($course) {
     global $DB;
     $currentProduct="voicepresentation";

    $activities = $DB->get_record("voicepresentation", array("course" => $course->id));
  
    if($activities){
        if($currentProduct != "voicepodcaster"){
           
             print_checkbox('reset_content_voicepresentation_replies', 1, false, get_string("voicepresentation_reset_only_replies", 'voicepresentation'), '', "if (this.checked) {document.getElementsByName('reset_content_voicepresentation')[0].disabled = 'true'} else {document.getElementsByName('reset_content_voicepresentation')[0].disabled=''}");  echo '<br />';
        
        }
       
        print_checkbox('reset_content_voicepresentation',1, false, get_string("voicepresentation_reset_all", 'voicepresentation'), '', "if (this.checked ) {document.getElementsByName('reset_content_voicepresentation_replies')[0].disabled = 'true'} else {document.getElementsByName('reset_content_voicepresentation_replies')[0].disabled=''}");  echo '<br />';
    }
    else
    {
        echo "There is not Voice Presentation in this course";    
    }
}

/**
* Actual implementation of the rest coures functionality, delete all the
* chat messages for course $data->courseid.
* @param $data the data submitted from the reset course.
* @return array status array
*/
function voicepresentation_reset_userdata($data) {
   global $CFG, $DB;

   $componentstr = get_string('modulenameplural', 'voicepresentation');
   $status = array();

   if (!empty($data->reset_content_voicepresentation_replies)) {
        $resources = $DB->get_records("voicepresentation_resources",array("course"=>$data->id)) ;
        
        foreach (array_keys($resources) as $id) 
        {
            $rid=$resources[$id]->rid;
            grade_get_grades($resources[$id]->course, "mod", "voicepresentation", $resources[$id]->gradeid); 
            $activities=$DB->get_records("voicepresentation",array("rid" => $rid));
            $newResource = voicetools_api_copy_resource($rid,"",1);
            //delete the old one and update the stored record
            //voicetools_api_delete_resource($rid);
            $resources[$id]->rid=$newResource->getRid();
            $DB->update_record("voicepresentation_resources",$resources[$id]);
            //need to update the rid linked to the activity
            foreach (array_keys($activities) as $activity_id) 
            {
                $activities[$activity_id]->rid=$newResource->getRid();
                $DB->update_record("voicepresentation",$activities[$activity_id]);
            }
        }
        $typestr = get_string("voicepresentation_reset_only_replies", 'voicepresentation');
        $status[] = array('component'=>$componentstr, 'item'=>$typestr, 'error'=>false);
   }
   
   if (!empty($data->reset_content_voicepresentation)) {
        $resources = $DB->get_records("voicepresentation_resources", array("course" => $data->id)) ;
        
        foreach (array_keys($resources) as $id) 
        {
            $rid=$resources[$id]->rid;
            $activities = $DB->get_records("voicepresentation", array("rid" => $rid));
            $newResource = voicetools_api_copy_resource($rid,"",2);
            //delete the old one and update the stored record
         //   voicetools_api_delete_resource($rid);
            $resources[$id]->rid=$newResource->getRid();
            $DB->update_record("voicepresentation_resources",$resources[$id]);
            foreach (array_keys($activities) as $activity_id) 
            {
                $activities[$activity_id]->rid=$newResource->getRid();
                $DB->update_record("voicepresentation",$activities[$activity_id]);
            }
        }
        $typestr = get_string("voicepresentation_reset_all", 'voicepresentation');
        $status[] = array('component'=>$componentstr, 'item'=>$typestr, 'error'=>false);
   }
   
   if (!empty($data->reset_gradebook_items)) { 
      $resources = $DB->get_records("voicepresentation_resources", array("course"=>$data->id)) ;
        
      foreach (array_keys($resources) as $id) 
      {
        if($resources[$id]->gradeid != "-1"){
          $resources[$id]->gradeid = "-1";
        
          $DB->update_record("voicepresentation_resources",$resources[$id]);
          $resource = voicetools_api_get_resource($resources[$id]->rid);
          $options = $resource->getOptions();
          $options->setPointsPossible("");
          $options->setGrade(false);
          $resource->setOptions($options);
          voicetools_api_modify_resource($resource->getResource());
        }
      }
   }

   return $status;
}

/**
* Actual implementation of the rest coures functionality, delete all the
* chat messages for course $data->courseid.
* @param $data the data submitted from the reset course.
* @return array status array
*/
function voicepresentation_delete_userdata($data,$showfeedback=true) {
   global $CFG, $DB;

   $componentstr = get_string('modulenameplural', 'voicepresentation');

   if (!empty($data->reset_content_voicepresentation_replies)) {
        $resources = $DB->get_records("voicepresentation_resources", array("course" => $data->id));

        foreach (array_keys($resources) as $id) 
        {
            $rid=$resources[$id]->rid;
            $activities=$DB->get_records("voicepresentation",array("rid" => $rid));
            $newResource = voicetools_api_copy_resource($rid,"",1);
            //delete the old one and update the stored record
            //voicetools_api_delete_resource($rid);
            $resources[$id]->rid=$newResource->getRid();
            $DB->update_record("voicepresentation_resources",$resources[$id]);
            //need to update the rid linked to the activity
            foreach (array_keys($activities) as $activity_id) 
            {
                $activities[$activity_id]->rid=$newResource->getRid();
                $DB->update_record("voicepresentation",$activities[$activity_id]);
            }
        }
        $typestr = get_string("voicepresentation_reset_only_replies", 'voicepresentation');
        if($showfeedback)
        {
            $strreset = get_string('reset');
            notify($strreset.': '.$typestr, 'notifysuccess');
        }   
   }
   
   if (!empty($data->reset_content_voicepresentation)) {
        $resources = $DB->get_records("voicepresentation_resources", array("course" => $data->id)) ;
        
        foreach (array_keys($resources) as $id) 
        {
            $rid=$resources[$id]->rid;
            $activities=$DB->get_records("voicepresentation", array("rid" => $rid));
            $newResource = voicetools_api_copy_resource($rid,"",2);
            //delete the old one and update the stored record
            //voicetools_api_delete_resource($rid);
            $resources[$id]->rid=$newResource->getRid();
            $DB->update_record("voicepresentation_resources",$resources[$id]);
            foreach (array_keys($activities) as $activity_id) 
            {
                $activities[$activity_id]->rid=$newResource->getRid();
                $DB->update_record("voicepresentation",$activities[$activity_id]);
            }
        }
        $typestr = get_string("voicepresentation_reset_all", 'voicepresentation');    
        
        if($showfeedback)
        {
            $strreset = get_string('reset');
            notify($strreset.': '.$typestr, 'notifysuccess');
        }
   }
   //the gradebook is completely reset, we have to manage that on our side. The grade setting of the resource has to be updated
   if (!empty($data->reset_gradebook_items)) {
      $resources = $DB->get_records("voicepresentation_resources",array("course",$data->id));
        
      foreach (array_keys($resources) as $id)
      {
        if($resources[$id]->gradeid != "-1"){
          $resources[$id]->gradeid = "-1";
        
          $DB->update_record("voicepresentation_resources",$resources[$id]);
          $resource = voicetools_api_get_resource($resources[$id]->rid);
          $options = $resource->getOptions();
          $options->setPointsPossible("");
          $options->setGrade(false);
          $resource->setOptions($options);
          voicetools_api_modify_resource($resource->getResource());
        }
      }
     
   }  

   
}

function voicepresentation_createResourceFromResource($rid,$new_rid,$new_course,$options="0")
{
    global $DB;
    $voicetools = $DB->get_record("voicepresentation_resources",array("rid" => $rid));
    $voicetools->id = null;
    $voicetools->rid = $new_rid;
    $voicetools->course = $new_course;
    $voicetools->fromrid = $rid;
    $voicetools->copyOptions = $options;
     
    return voicepresentation_store_new_element($voicetools);
} 


/*
 * This function add a column to the gradebook
 * return the activity id which will be used to do the link between this column and the vb resource
 */
function voicepresentation_add_grade_column($rid,$course_id,$resource_name,$points_possible,$grades=null)
{        
    global $DB;
  //check if the resource is already linked to an activity
  $activities = $DB->get_records("voicepresentation", array("rid" => $rid));
  if(!empty($activities)){
    $keys=array_keys($activities);
    $activity_id = $keys[0];//take the first one
  }else{
    srand ((double) microtime( )*1000000);
    $activity_id = rand(1000000,9000000);//we generate a big random number 
  }

  if($grades == null)
  {//check if there are some grades associated to the column
    $students=getStudentsEnrolled($course_id);
    $users_key = array_keys($students);
    $gradesData = grade_get_grades($course_id, "mod", "voicepresentation", $activity_id,$users_key);
    if(isset($gradesData->items[0]))
    {
      $grades = voicepresentation_build_gradeObject_From_ArrayOfGradeInfoObjects($gradesData->items[0]->grades);
    }   
  } 
  $grade_params = array('itemname'=>$resource_name,'grademax'=>$points_possible);//we will use this column to get the grade item associated to the resource.
  
  grade_update("mod/voicepresentation", $course_id, "mod", "voicepresentation", $activity_id, 0, $grades, $grade_params); 
  return $activity_id;
}

/*
 * This function add a grades to a specific column of the gradebook
 */
function voicepresentation_add_grades($rid, $course_id, $grades){
    global $DB;
    //check if the resource is already linked to an activity
    $voicetools = $DB->get_record("voicepresentation_resources",array("rid" => $rid));
  
    grade_update("mod/voicepresentation", $course_id, "mod", "voicepresentation", $voicetools->gradeid, 0, voicepresentation_build_gradeObject($grades)); 
  
    
}

/*
 * This function build the object that we have to pass to the grade_update function.
 * The entry param is the data that we get from the form
 */
function voicepresentation_build_gradeObject($grades){

      $gradesArray = array();
	  foreach($grades as $k=>$g) {
	  	$gradeObj = new object();
	  	$gradeObj->rawgrade=$g;
	  	$gradeObj->userid=$k;
		$gradesArray[$k]=$gradeObj;
	  }	
	  return $gradesArray;
}

/*
 * This function build the object that we have to pass to the grade_update function.
 * The entry param is the result of the function grade_get_grades which is an array of Grade Info Objects 
 */

function voicepresentation_build_gradeObject_From_ArrayOfGradeInfoObjects($grades){

      $gradesArray = array();
	  foreach($grades as $k=>$g) {
	  	$gradeObj = new object();
	  	$gradeObj->rawgrade=$g->grade;
	  	$gradeObj->userid=$k;
		$gradesArray[$k]=$gradeObj;
	  }	
	  return $gradesArray;
}

/*
 * THis function delete the grade column associated to the resource.
 * We can pass directly the activity id if we know it(restore process for example)
 */
function voicepresentation_delete_grade_column($rid,$course_id,$activity_id=null){
    global $DB;
    $voicetools = $DB->get_record("voicepresentation_resources", array("rid" => $rid));
    if(!isset($activity_id)){
      $activity_id = $voicetools->gradeid;//store the activity id to be able to delete the grade column
    }
    $voicetools->gradeid = "-1";
    $DB->update_record("voicepresentation_resources", $voicetools);
    
    grade_update("mod/voicepresentation", $course_id, "mod", "voicepresentation", $activity_id, 0, null, array("deleted"=>"1")); 
    
}
