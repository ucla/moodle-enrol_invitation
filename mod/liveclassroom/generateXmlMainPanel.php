<?PHP 

/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2007 Horizon Wimba, All Rights Reserved.                *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Horizon Wimba.                       *
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
 *      along with the Horizon Wimba Moodle Integration;                      *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Hazan Samy                                                         *
 *                                                                            *
 * Date: October 2006                                                         *
 *                                                                            *
 ******************************************************************************/

/* $Id: generateXmlMainPanel.php 76082 2009-09-01 20:58:33Z trollinger $ */

/// This page is to generate the list of rooms and archives
header( 'Content-type: application/xml' );
require_once("../../config.php");
require_once("lib.php");
require_once("lib/php/common/WimbaLib.php");
require_once("lib/php/lc/LCAction.php");

require_once("lib/php/common/WimbaCommons.php");
require_once("lib/php/common/WimbaUI.php");    
require_once("lib/php/common/XmlArchive.php");
require_once("lib/php/common/XmlOrphanedArchive.php");
require_once("lib/php/common/XmlRoom.php");
require_once("lib/php/common/WimbaXml.php");

if (version_compare(PHP_VERSION,'5','>=') && file_exists($CFG->dirroot . '/auth/cas/CAS/domxml-php4-php5.php')) {
   require_once($CFG->dirroot . '/auth/cas/CAS/domxml-php4-php5.php');		
} else if (version_compare(PHP_VERSION,'5','>=')){
   require_once('lib/php/common/domxml-php4-php5.php');		
} 

foreach(getKeysOfGeneralParameters() as $param)
{
	$value=optional_param( $param["value"], $param["default_value"], $param["type"] );
	if( $value != null )
		$params[$param["value"]] = $value;
}

require_login( $params["enc_course_id"] );

if( isset($params["error"]) )//error from other pages
{
    $display=$uiManager->setError(get_string ($params["error"], 'liveclassroom'));
}
else
{

    $session=new WimbaMoodleSession( $params );     
    $message="";
    
    if(isset($params["messageProduct"]) && isset($params["messageAction"])) 
    {
    	$message = get_string("message_".$params["messageProduct"] ."_start", "liveclassroom")." ".
    	          
    	           get_string("message_".$params["messageAction"]."_end", "liveclassroom");
    }
    $api = new LCAction($session,$CFG->liveclassroom_servername, 
                        $CFG->liveclassroom_adminusername, 
                        $CFG->liveclassroom_adminpassword,$CFG->dataroot);
    
    $uiManager = new WimbaUI( $params, $api );  
    
    if( $uiManager->getSessionError() === false && $api->errormsg === "")//good
    { 
        $uiManager->getLCPrincipalView( $message );	   
    }
    else 
    {
        $uiManager->setError( $api->errormsg );
    }
}

echo $uiManager->getXmlString();	 
?>
