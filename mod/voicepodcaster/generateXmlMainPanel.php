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
 * Author: Samy Hazan                                                         *
 *                                                                            *  
 * Date: October 2006                                                         *                                                                        *
 *                                                                            *
 ******************************************************************************/


/* $Id: generateXmlMainPanel.php 76089 2009-09-01 22:22:41Z trollinger $ */

/// This page generates the xml of the principal window

global $CFG;
header( 'Content-type: application/xml' );
require_once("../../config.php");
require_once("lib.php");
require_once("lib/php/common/WimbaXml.php");
require_once("lib/php/common/WimbaCommons.php"); 
require_once("lib/php/common/WimbaUI.php");        
require_once("lib/php/common/XmlResource.php");    
require_once('lib/php/vt/WimbaVoicetools.php'); 
require_once('lib/php/vt/WimbaVoicetoolsAPI.php');  
require_once('lib/php/common/WimbaLib.php');  

if (version_compare(PHP_VERSION,'5','>=') && file_exists($CFG->dirroot . '/auth/cas/CAS/domxml-php4-php5.php')) {
   require_once($CFG->dirroot . '/auth/cas/CAS/domxml-php4-php5.php');		
} else if (version_compare(PHP_VERSION,'5','>=') ){
   require_once('lib/php/common/domxml-php4-php5.php');		
}

set_error_handler("manage_error");

$messageProduct=optional_param("messageProduct","", PARAM_RAW);
$messageAction=optional_param("messageAction","", PARAM_RAW);

foreach(getKeysOfGeneralParameters() as $param){ 
	$value=optional_param($param["value"],$param["default_value"],$param["type"]);
	if($value!=null)
		$params[$param["value"]] = $value;
}

require_login($params["enc_course_id"]);
$uiManager=new WimbaUI($params); 

wimba_add_log(WIMBA_DEBUG,voicepodcaster_LOGS,"getXmlListPanel : parameters  \n" . print_r($params,true)); 

if(isset($params["error"]))
{
    wimba_add_log(WIMBA_ERROR,voicepodcaster_LOGS,"getXmlListPanel : ". get_string ($params["error"], 'voicepodcaster')); 
 	$uiManager->setError( get_string( $params["error"], 'voicepodcaster') );
}
else
{
  //Session Management 	
    if( $uiManager->getSessionError() === false )//good
    { 
        $message="";
        if( !empty($messageProduct) && !empty($messageAction) )
        {
        	$message = get_string( "message_".$messageProduct."_start", "voicepodcaster")."  ".
        	           get_string( "message_".$messageAction."_end", "voicepodcaster" );
        }
        $uiManager->getVTPrincipalView($message,"pc"); 
    }
    else
    { //bad session	
        wimba_add_log(WIMBA_ERROR,voicepodcaster_LOGS,"getXmlListPanel : ". get_string ('error_session', 'voicepodcaster')); 
        $uiManager->setError(get_string ('error_session', 'voicepodcaster'));
    }
}

wimba_add_log(WIMBA_DEBUG,voicepodcaster_LOGS,"getXmlListPanel : xml generated \n". $uiManager->getXmlString()); 

if(isset($error_wimba))//error fatal was detected
{
    $uiManager->setError(get_string ('error_display', 'voicepodcaster'));
}

echo $uiManager->getXmlString();

?>
