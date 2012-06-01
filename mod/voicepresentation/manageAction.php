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
 * Author: Samy Hazan                                                         *
 *                                                                            *
 * Date: Septe,mer 2006                                                         *
 *                                                                            *
 ******************************************************************************/

/* $Id: manageRoomAction.php 179 2007-01-11 15:25:54Z hugues $ */

/* This page manage the action create, update, delete for a room */
global $CFG, $DB;
require_once ('../../config.php');
require_once ('lib.php');
require_once ("lib/php/common/WimbaLib.php");
require_once ("lib/php/common/DatabaseManagement.php");
require_once ("lib/php/common/WimbaCommons.php");
require_once ('lib/php/vt/WimbaVoicetools.php');
require_once ('lib/php/vt/WimbaVoicetoolsAPI.php');
require_once ('lib/php/vt/VtAction.php');

$messageProduct=optional_param("messageProduct","", PARAM_RAW);
$messageAction=optional_param("messageAction","", PARAM_RAW);
$notool=optional_param("novoicetools","false", PARAM_RAW);

$keys=array_merge(getKeysOfGeneralParameters(),getKeyWimbaVoiceForm());
foreach($keys as $param){ 
	$value=optional_param($param["value"],$param["default_value"],$param["type"]);
	if($value!=null)
		$params[$param["value"]] = $value;
}  

wimba_add_log(WIMBA_DEBUG,voicepresentation_LOGS,"manageAction : parameters  \n" . print_r($params,true)); 
require_login($params["enc_course_id"]);

$session = new WimbaMoodleSession($params);

$redirectionUrl='welcome.php?id=' . $params["enc_course_id"] . '&' . 
                voicepresentation_get_url_params($params["enc_course_id"]) . '&time=' . $session->timeOfLoad;

$urlModuleForm=$CFG->wwwroot.'/course/mod.php?section=0&sesskey='.sesskey().
                '&id='.$session->getCourseId().'&add=voicepresentation&rid=';

$messageType = "";

if ( $session->error === false && $session != NULL ) 
    {
    $vtAction = new vtAction( $session->getEmail(), $params );
	if ($params['action'] == 'launch') 
	{
		$session->setCurrentVtUSer($params["type"]);
	
		if ($params["studentView"] == "true") 
		{
			$session->setVtUserRigths($params["type"], "student");
		}
		$resource = voicetools_api_get_resource($params["resource_id"]);
		$result = $vtAction->getVtSession($resource, $session->getVtUser(), $session->getVtUserRigths());
		if ($result != NULL) 
		{
		    wimba_add_log(WIMBA_DEBUG,voicepresentation_LOGS,"launch the ". $params["type"] . ", nid =" .$result->getNid()); 
		    if(!empty($params["filter_screen_name"])){
		      redirection($CFG->voicetools_servername . '/' . $params["type"] . '?action=display_popup&nid=' . $result->getNid()."&filter_screen_name=".$params["filter_screen_name"]);
		    }else{
		      redirection($CFG->voicetools_servername . '/' . $params["type"] . '?action=display_popup&nid=' . $result->getNid());
		    }
		}
		else 
		{
			redirection($redirectionUrl.'&error=problem_vt');
		}
	}
	elseif ($params['action'] == 'create' || $params['action'] == "createDefault")  
	{
		if ($params['type'] == "board") 
		{
			$result = $vtAction->createBoard(); //create the resource on the vt
			
           
			$messageAction = "created";            $messageProduct = "board";
		}
		elseif ($params['type'] == "presentation") 
		{
			$result = $vtAction->createPresentation();
			$messageAction = "created";            $messageProduct = "presentation";
		}
		elseif ($params['type'] == "pc")
		{
			$result = $vtAction->createPodcaster();
			$messageAction = "created";            $messageProduct = "pc";
		}
        
		if ($result != NULL && $result->error != "error") 
		{
		    
			$resource_id = storeResource($result->getRid(), $session->getCourseId(), $params);
                                                              			
			if (empty ($resource_id)) 
			{
				wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,"manageAction : Problem to add the resource into the database"); 
				redirection($redirectionUrl. '&error=problem_bd');
			}
			if($params['action'] == "createDefault")
			{
			    echo $result->getRid();
			    exit();   
			}
		}
		else 
		{
		   if($params['action'] == "createDefault")
            {
                echo "error";
                exit();   
            }
            wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,"manageAction :Problem to add the new resource on the vt server"); 
			redirection($redirectionUrl . '&error=problem_vt');
		}
	}
	elseif ($params['action'] == 'update') 
	{
		if ($params['type'] == "board") 
		{
			$result = $vtAction->modifyBoard($params["resource_id"]); //create the resource on the vt
			if ($result != NULL && $result->error != "error") 
    		{
    			if($params['grade'] == 'true'){         
    			   $params['gradeid']=voicepresentation_add_grade_column($result->getRid(),$params["enc_course_id"],$result->getTitle(),$params["points_possible"]);
    			}else{
    			  $resourceDb=$DB->get_record("voicepresentation_resources",array("rid" => $result->getRid()));
    			  if($resourceDb->gradeid != -1 ){
        			   voicepresentation_delete_grade_column($result->getRid(),$params["enc_course_id"]);
        			   $params['gradeid']=-1;//the resource will also be updated
    			  }
    			}
    		}
			$messageAction = "updated";            $messageProduct = "board";
		}
		elseif ($params['type'] == "presentation") 
		{
			$result = $vtAction->modifyPresentation($params["resource_id"]);
			$messageAction = "updated";            $messageProduct = "presentation";
		}
		elseif ($params['type'] == "pc") 
		{
			$result = $vtAction->modifyPodcaster($params["resource_id"]); 
			$messageAction = "updated";            $messageProduct = "pc";
		}

		if ($result != NULL) 
		{
			//create the object to store in the db
			$resource_id = updateResource($result->getRid(), $session->getCourseId(), $params);

			if (empty ($resource_id)) 
			{
				error_log(__FUNCTION__ . " : Problem to update the resource on the database", TRUE);
				redirection($redirectionUrl . '&error=problem_bd');
			}

			$messageType = $params['type'] . 'Updated';
		}
		else 
		{
			wimba_add_log(WIMBA_ERROR,voicepresentation_LOGS,"manageAction : Problem to add the resource into the database"); 
			redirection($redirectionUrl . '&error=problem_vt');
		}

	}
	elseif ($params['action'] == 'delete') 
	{
	    /* Bug 28439 - We need to check if the resource has a grade book associated with it
	       before we try and delete it */
	    $resourceDb=$DB->get_record("voicepresentation_resources", array("rid" => $params["resource_id"]));
            if($resourceDb->gradeid != -1 ){
	      voicepresentation_delete_grade_column($params["resource_id"],$params["enc_course_id"]);
	    }
		$result = $vtAction->deleteResource($params["resource_id"]);
		if ($result != NULL) { //if no error during the creation
			if (! voicepresentation_delete_all_instance_of_resource($params["resource_id"])) 
			{
	 			 notify("Could not delete the activities for the voicetools:".  $params["resource_id"]);
			}
			$messageType = 'delete' . $param["type"];
			$messageAction = "deleted";             $messageProduct = $params["type"];
		}

	}
	elseif ($params['action'] == 'submitGrade') 
	{
		voicepresentation_add_grades($params["resource_id"],$params["enc_course_id"], $params["grades"]);
	
	    $messageAction = "updated_grades"; 
        $messageProduct = "grades";

	}	
	redirection($redirectionUrl . '&messageAction=' . $messageAction. '&messageProduct=' .$messageProduct);
}
else //bad session
{
	redirection($redirectionUrl . '&error=session');
}
?>
