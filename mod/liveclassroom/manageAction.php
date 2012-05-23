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
 * Author: Hazan Samy                                                         *
 *                                                                            *
 * Date: September 2006                                                       *
 *                                                                            *
 ******************************************************************************/
 

/* $Id: manageAction.php 80709 2010-11-17 14:12:11Z bdrust $ */

/* This page manage the action create, update, delete for a room */
global $CFG;
require_once ("../../config.php");
require_once ("lib.php");
require_once ("lib/php/lc/LCAction.php");
require_once ("lib/php/common/WimbaCommons.php");
require_once ("lib/php/common/WimbaLib.php");
require_once ("lib/php/common/WimbaUI.php");
require_once ("lib/php/common/XmlRoom.php");
require_once ("lib/php/common/WimbaXml.php");

if (version_compare(PHP_VERSION,'5','>=') && file_exists($CFG->dirroot . '/auth/cas/CAS/domxml-php4-php5.php')) {
   require_once($CFG->dirroot . '/auth/cas/CAS/domxml-php4-php5.php');		
} else if (version_compare(PHP_VERSION,'5','>=')){
   require_once('lib/php/common/domxml-php4-php5.php');		
}  

$keys = array_merge(getKeysOfGeneralParameters(),getKeyWimbaClassroomForm());

$params=array();
foreach($keys as $param)
{	
	$value = optional_param($param["value"], $param["default_value"], $param["type"]);
    $params[$param["value"]] = $value;
}

require_login($params["enc_course_id"]);
$action = $params["action"];
$roomId = $params["resource_id"];
$rid_audio = $params["rid_audio"];
$session = new WimbaMoodleSession($params);
$xml = new WimbaXml();

if ( $session->error === false && $session !=  NULL ) {

    $api = new LCAction($session, 
                        $CFG->liveclassroom_servername, 
                        $CFG->liveclassroom_adminusername, 
                        $CFG->liveclassroom_adminpassword, 
                        $CFG->dataroot);
                        
	$prefix = $api->getPrefix();

	switch ( $action ) {

		case "launch" :
			$roomId = required_param( 'resource_id', PARAM_SAFEDIR );
			
			if ($params["studentView"] == "true") 
			{
				$authToken = $api->getAuthokenNormal($session->getCourseId()."_S",
                                    				 $session->getFirstname(),
                                    				 $session->getLastname());
			} 
			else 
			{
				$authToken = $api->getAuthoken();
			}
            redirection( $CFG->liveclassroom_servername.'/check_wizard.pl?'.
                                			'channel='.$api->getPrefix().$roomId.
                                			'&hzA='.$authToken.'&'.$api->api->get_bridge_header_string() );
			break;
			
		case "create" :
			$id = $api->createRoom($roomId, "false");
			$messageAction = "created";
			$messageProduct = "room";
			break;
			
		case "createDefault" :
            $id = $api->createSimpleRoom($params["longname"], "true", $params["enc_course_id"]);
            echo $prefix.$id;
            exit();
            break;	
            
		case "update" :
			$id = $api->createRoom($roomId, "true");
			$messageAction = "updated";
			$messageProduct = "room";
			break;
			
		case "delete" :
            
		    $id = $api->deleteRoom($roomId);
			//delte the activity linked to this room
			$prefix = $api->getPrefix();
			if ( !liveclassroom_delete_all_instance_of_room($roomId,$prefix) ) 
			{
				notify("Could not delete the activities for the room: $roomId");
			}
			$messageAction = "deleted";
			$messageProduct = "room";
			break;
			
		case "openContent" :
			$authToken = $api->getAuthoken();
			redirection( $CFG->liveclassroom_servername.'/admin/api/class/carousels.epl?'.
                                			'class_id='.$api->getPrefix().$roomId. 
                                			'&hzA='.$authToken. 
                                			'&no_sidebar=1&'.$api->api->get_bridge_header_string());
			break;
			
		case "openReport" :
			$authToken = $api->getAuthoken();
			redirection( 'reports.php?id='.$roomId.'&hzA='.$authToken.'&courseId='.$session->getCourseId() );
			exit ();
			break;
			
		case "openAdvancedMedia" :
			$authToken = $api->getAuthoken();
			redirection ( $CFG->liveclassroom_servername.'/admin/api/class/media.pl?'.
                                			'class_id='.$api->getPrefix().$roomId. 
                                			'&hzA='.$authToken. 
                                			'&no_sidebar=1&'.$api->api->get_bridge_header_string());
			exit ();
			break;
			
		case "openAdvancedRoom" :
			$authToken = $api->getAuthoken();
			redirection ( $CFG->liveclassroom_servername.'/admin/api/class/properties.pl?'.
                                			'class_id='.$api->getPrefix().$roomId. 
                                			'&hzA='.$authToken. 
                                			'&no_sidebar=1&'.$api->api->get_bridge_header_string());
			break;
			
		case "getDialInformation" :
		    header( 'Content-type: application/xml' );
			$select_room = $api->getRoom($roomId);

			if ( $params["studentView"] == "true" || $session->isInstructor() === false )
			{
				$xml->createPopupDialElement(get_string("popup_dial_title", "liveclassroom"), 
                            				 get_string("popup_dial_numbers", "liveclassroom"), 
                            				 get_string("popup_dial_pin", "liveclassroom"),
                            				 null, 
                            				 $select_room->getParticipantPin(), 
                            				 $api->getPhoneNumbers());
			}
			else
			{
				$xml->createPopupDialElement(get_string("popup_dial_title", "liveclassroom"), 
                            				 get_string("popup_dial_numbers", "liveclassroom"), 
                            				 get_string("popup_dial_pin", "liveclassroom"), 
                            				 $select_room->getPresenterPin(), 
                            				 $select_room->getParticipantPin(), 
                            				 $api->getPhoneNumbers());
			}
            echo $xml->getXml();
			break;
			
		case "saveSettings" :
			$id=$api->createRoom($roomId, "true");
			echo "good";
            exit ();
			break;
		case "getMp3Status" :
		    $audioFileStatus=$api->getMp3Status($rid_audio);
		    if($audioFileStatus === false || $audioFileStatus->getStatus() == "" )
		    {
		      echo "error_server";
		    }
		    else
		    {
		      echo $audioFileStatus->getStatus().";".$audioFileStatus->getUri().";";
		    }
		    exit();
			break;
		case "getMp4Status" :
		    $audioFileStatus=$api->getMp4Status($rid_audio);
		    if($audioFileStatus === false || $audioFileStatus->getStatus() == "")
		    {
		     echo "error_server";
		    }
		    else
		    {
		      echo $audioFileStatus->getStatus().";".$audioFileStatus->getUri().";";
		    }
			exit();
			break;
	}

	if ($action !=  "getDialInformation") 
	{

		redirection ('welcome.php?'.
                            'id=' . $session->getCourseId() . 
                            '&' . $session->url_params . 
                            '&time=' . $session->timeOfLoad .
                            '&messageAction=' . $messageAction . 
                            '&messageProduct=' . $messageProduct);
    }
}
else
{
	redirection ('welcome.php?'.
                    	'id=' . $params["enc_course_id"] . 
                    	'&' . liveclassroom_get_url_params($params["enc_course_id"]) .
                    	'&time=' . $session->timeOfLoad .
                    	'&error=session');
}
?>