<?PHP
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
 * Date: October 2006                                                         *
 *                                                                            *
 ******************************************************************************/
 
/* $Id: generateXmlSettingsPanel.php 76298 2009-09-21 12:57:31Z trollinger $ */
header( 'Content-type: application/xml' );
require_once("../../config.php");
require_once("lib.php");
require_once("lib/php/lc/LCAction.php");  
require_once("lib/php/common/WimbaXml.php");
require_once("lib/php/common/WimbaCommons.php");  
require_once("lib/php/common/WimbaLib.php"); 
require_once("lib/php/common/WimbaUI.php");    

if (version_compare(PHP_VERSION,'5','>=') && file_exists($CFG->dirroot . '/auth/cas/CAS/domxml-php4-php5.php')) {
   require_once($CFG->dirroot . '/auth/cas/CAS/domxml-php4-php5.php');		
} else if (version_compare(PHP_VERSION,'5','>=')){
   require_once('lib/php/common/domxml-php4-php5.php');		
} 

global $error;

$params = array ();
foreach(getKeysOfGeneralParameters() as $param)
{	
	$value=optional_param($param["value"],$param["default_value"],$param["type"]);
	if($value!=null)
		$params[$param["value"]] = $value;
}
require_login( $params["enc_course_id"] );

set_error_handler("manage_error");

$action = optional_param( 'action', null, PARAM_ALPHA) ;
$currentIdtab = optional_param( 'idtab', null, PARAM_CLEAN) ;
$session = new WimbaMoodleSession($params);     

$api = new LCAction($session,$CFG->liveclassroom_servername, 
                    $CFG->liveclassroom_adminusername, 
                    $CFG->liveclassroom_adminpassword,
                    $CFG->dataroot);
                    
$uiManager = new WimbaUI( $params, $api, $currentIdtab ); 

if($uiManager->getSessionError() === false)//good
{  
	if($action == 'update') 
	{
		$roomId = required_param('resource_id', PARAM_SAFEDIR );
	
	    $room_info = $api->getRoom($roomId);  
	  
	 	if($room_info)
	 	{
	 	  
            $uiManager->setCurrentProduct("liveclassroom", $room_info);
		}	
		else 
		{
			//problem to get the lc resource
			  $uiManager->setError( get_string('error_connection_lc', 'liveclassroom') );
		}
	}
	else
	{
		$uiManager->setCurrentProduct("liveclassroom");
	}
	
  	$uiManager->getLCSettingsView($action); 

}
else
{
    $uiManager->setError( get_string ('error_'.$session->error, 'liveclassroom') );
}

if( !empty($error) )
{
    $uiManager->setError("error");
}

echo $uiManager->getXmlString();

  
?>
