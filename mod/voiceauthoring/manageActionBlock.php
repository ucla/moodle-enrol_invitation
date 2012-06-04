<?php    
/******************************************************************************
*                                                                            *
* Copyright (c) 1999-2006 Horizon Wimba, All Rights Reserved.                *
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
* Author: Thomas Rollinger                                                   *
*                                                                            *
* Date: 3th March 2007                                                      *
*                                                                            *
******************************************************************************/

/* $Id: WimbaVoicetoolsAPI.php 45764 2007-02-28 22:04:25Z thomasr $ */

/* This page manage the action of the block */
require_once('../../config.php');
require_once('lib.php');


require_once("lib/php/common/WimbaCommons.php");     
require_once('lib/php/vt/WimbaVoicetools.php'); 
require_once('lib/php/vt/WimbaVoicetoolsAPI.php');
global $CFG;
$course_id=optional_param('course_id', '',PARAM_ALPHANUM);
$action=optional_param('action', '',PARAM_ALPHANUM);
$block_id=optional_param('block_id', '',PARAM_ALPHANUM);
$comment=optional_param('block_voiceauthoring_comment', '',PARAM_TEXT);
$title=optional_param('block_voiceauthoring_title', '',PARAM_TEXT);

if( $action == "updateConfig" )
{
    $blocks=new Object();
    $blocks->bid=$block_id;
   
    $blocks->title=$title;
    $blocks->comment=$comment;
   
    voiceauthoring_update_block_informations($blocks);
    //redirection to the course page
    parentRedirection("$CFG->wwwroot/course/view.php?id=$course_id");
}


?>      