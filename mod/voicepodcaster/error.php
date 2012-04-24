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
 * Author: thomas Rollinger                                                   *
 *                                                                            *  
 * Date: March 2007                                                           *                                                                        *
 *                                                                            *
 ******************************************************************************/

/* $Id: WimbaVoicetoolsAPI.php 45764 2007-02-28 22:04:25Z thomasr $ */
?>
<!DOCTYPE html PUBLIC -//W3C//DTD XHTML 1.0 Transitional//EN http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>Blackboard Collaborate</title>

</head>
<script language="javascript" src="js/pngfix.js"></script>
    <link rel="STYLESHEET" href="css/StyleSheet.css" type="text/css" />   
 <?php require_once("../../config.php");   ?>
<body>
    <form id="form1">
        <table width='100%' height='32px' border='0' align='center' cellpadding='0' cellspacing='0' style="border:solid 1px #808080;" >
            <tr>
                <td>
                    <div id="headerBar" class="headerBar">
                            <table cellpadding="0" cellspacing="0" id="TABLE1" style="padding-left:15px">
                                <tbody>
                                    <tr>
                                       <td align="left" valign="middle">
                                                <img height='24px' width='24px' src="pictures/items/headerbar-voice_recorder_icon.png">
                                                <span id='oldTitle' class="titleVoiceRecorder">Voice recorder</span>
                                          </td>
                                      
                                    </tr>
                                </tbody>
                            </table>
                        </div>
            </tr>
            <tr>
                <td>
                    <div id='contextBar' class='contextBar'>
                        <table cellspacing='0' cellpadding='0'>
                            <tr>
                                <td align="left">
                                    <label class='roomNameForSettings'>
                                        Error</label></td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <div id='error_frame_vr' align='center'>
                        <table width='100%' border='0' cellpadding='0' height='100px'>
                            <tr>
                                <td width='10%'>
                                </td>
                                <td valign='middle'>
                                    <img src='pictures/items/warning.png' alt='' width='60' height='60' border='0'></td>
                                <td width='20'>
                                </td>
                                <td valign='middle'>
                                    <div id='error_title'>
                                        Error
                                        <div id='error_body'>
                                          <p><?php echo get_string($_GET["error"]."_recorder","voicetools") ?></p>
                                        </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
    </form>
</body>
<script>
  if(navigator.appName.indexOf("Explorer") > -1 && parseFloat(navigator.appVersion)<5.5)
         correctPNG();
</script>
</html>
