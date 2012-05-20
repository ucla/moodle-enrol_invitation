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
 * Date: November 2006                                                        *
 *                                                                            *
 ******************************************************************************/
 
/* $Id: reports.php 80709 2010-11-17 14:12:11Z bdrust $ */

/// This page is to displayed the reports of a room


require_once("../../config.php");
require_once("lib.php");
require_once("lib/php/lc/lcapi.php");
require_once("lib/php/lc/LCAction.php");

$roomId = required_param('id', PARAM_SAFEDIR);
$token = required_param('hzA', PARAM_RAW);
$courseId = required_param('courseId',PARAM_SAFEDIR); 

$api=new LCAction(null,$CFG->liveclassroom_servername, 
	           $CFG->liveclassroom_adminusername, 
	           $CFG->liveclassroom_adminpassword,$CFG->dataroot,$courseId);
$bridgeHeader = '&'.$api->api->get_bridge_header_string();
		
$room_info = $api->getRoom($roomId);  

?>
<html>
<head>
<link rel="STYLESHEET" href="css/StyleSheet.css" type="text/css" />
<script type="text/javascript" src='<?PHP echo $CFG->liveclassroom_servername ;?>/js/launch.js'></script>
</head>
<body>
    <table border="0" width="96%" cellpadding="0" cellspacing="0">
      <tr class="divider">
        <td colspan=2 class="page_title"><img src="pix/icon.gif" alt="Live Classroom" title="Live Classroom" border="0"/>View Reports</td>
      </tr>

      <tr>
        <td width="100%" height="5px" colspan=2>
        </td>
      </tr>

      <tr>
        <td colspan=2>

 		  <a style="text-decoration:underline;" href="<?php echo $CFG->liveclassroom_servername;?>/admin/api/class/results?class_id=<?php echo$api->getPrefix().$room_info->getRoomId(); ?>&hzA=<?php echo $token;?>&amp;no_sidebar=1<?php echo $bridgeHeader;?>" title="Poll Results">Poll Results</a>

          <div class="helptext">
            View the results from all advanced polls that have been conducted in this room or archive.
          </div>
        </td>
      </tr>

      <tr>
        <td colspan=2>
          <br />
           <a style="text-decoration:underline;" href="<?php p($CFG->liveclassroom_servername)?>/admin/api/server/tracking.pl?mode=detailed&amp;popup=1&amp;channel=<?php echo $api->getPrefix().$room_info->getRoomId(); ?>&amp;hzA=<?php echo $token;?>&amp;no_sidebar=1<?php echo $bridgeHeader;?>" title="Tracking">Tracking</a>


          <div class="helptext">
            View statistics on who has entered this room or archive, when they arrived, and the duration of their stay
          </div>
        </td>
      </tr>
     <?php if($room_info->isArchive()){?>
      <tr>
        <td colspan=2>
          <br />
           <a style="text-decoration:underline;" href="<?php p($CFG->liveclassroom_servername)?>/admin/api/chatlogs?class_id=<?php echo $api->getPrefix().$room_info->getRoomId(); ?>&amp;hzA=<?php echo $token; ?><?php echo $bridgeHeader;?>" title="Chat Logs">Chat Logs</a>

          <div class="helptext">
            View chat logs recorded during this archive
          </div>
        </td>
      </tr>
      <?php } ?> 
    </table>
  </body>
</html>
