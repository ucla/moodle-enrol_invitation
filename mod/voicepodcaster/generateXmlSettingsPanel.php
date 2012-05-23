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
 * Date: September 2006                                                       *                                                                        *
 *                                                                            *
 ******************************************************************************/

/* $Id: generateXmlSettingsPanel.php 76081 2009-09-01 20:56:36Z trollinger $ */

/// This page is to generate the list of VT

global $CFG;
header( 'Content-type: application/xml' );
require_once("../../config.php");
require_once("lib.php");
require_once("lib/php/vt/WimbaVoicetoolsAPI.php");
require_once("lib/php/vt/WimbaVoicetools.php");
require_once("lib/php/common/WimbaCommons.php");
require_once("lib/php/common/WimbaXml.php");
require_once("lib/php/common/WimbaUI.php");
require_once("lib/php/common/WimbaLib.php");
if (version_compare(PHP_VERSION,'5','>=') && file_exists($CFG->dirroot . '/auth/cas/CAS/domxml-php4-php5.php')) {
   require_once($CFG->dirroot . '/auth/cas/CAS/domxml-php4-php5.php');		
} else if (version_compare(PHP_VERSION,'5','>=') ){
   require_once('lib/php/common/domxml-php4-php5.php');		
}

set_error_handler("manage_error");

$createWorkflow = optional_param('createWorkflow', false, PARAM_BOOL); // course
foreach(getKeysOfGeneralParameters() as $param)
{
    $value=optional_param($param["value"],$param["default_value"],$param["type"]);
    if($value!=null)
    {
        $params[$param["value"]] = $value;
    }
}

wimba_add_log(WIMBA_DEBUG,voicepodcaster_LOGS,"getXmlListPanel : parameters  \n" . print_r($params,true)); 
require_login($params["enc_course_id"]);

$uiManager=new WimbaUI($params);
if($uiManager->getSessionError() === false)
{
    /*******************
     GET URL INFORMATIONS
     ********************/
    $action = optional_param('action', "", PARAM_RAW);   // Course Module ID, or
    $typeProduct = $params['type'];
    
    if($action == 'update')
    { //get the information of the resource
        $currentBoard = voicetools_api_get_resource($params["resource_id"]);
    
        if( !isset($currentBoard) || $currentBoard->error==true)
        {
            wimba_add_log(WIMBA_ERROR,voicepodcaster_LOGS,"getXmlNewPanel : ". get_string('problem_vt','voicepodcaster')); 
            $uiManager->setError(get_string('problem_vt','voicepodcaster'));
        }
        else
        {
            $currentBoardInformations=voicepodcaster_get_wimbaVoice_Informations($params["resource_id"]);
            $uiManager->setCurrentProduct($typeProduct,$currentBoard,$currentBoardInformations);
        }
    }
    else
    {
        $uiManager->setCurrentProduct($typeProduct);
    }
    
    $display= $uiManager->getVTSettingsView($action,$createWorkflow);

}
else
{
    wimba_add_log(WIMBA_ERROR,voicepodcaster_LOGS,"getXmlNewPanel : ". get_string ('error_session', 'voicepodcaster')); 
    $uiManager->setError(get_string ('error_session', 'voicepodcaster'));
}
wimba_add_log(WIMBA_DEBUG,voicepodcaster_LOGS,"getXmlListPanel : parameters  \n" .  $uiManager->getXmlString()); 

if(isset($error_wimba))//error fatal was detected
{
    $uiManager->setError(get_string ('error_display', 'voicepodcaster'));
}

echo $uiManager->getXmlString();

?>

