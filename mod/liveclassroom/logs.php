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
 * Author: Hugues Pisapia                                                     *
 *                                                                            *
 *                                                                            *
 ******************************************************************************/
require_once ('../../config.php');
require_once ('lib/php/common/WimbaLib.php');
global $CFG;

//Gets the parameters
 $action = required_param("action", PARAM_ACTION);
 $log = optional_param("log", null, PARAM_RAW);
  
  //Set the log file name.
 if (isset($log)){
    $file = WIMBA_DIR."/".$log;
 }

 /**If action is list, lists all the logs in the level corresponding folder
  * If action is download, download the selected log
  */
    if($action == "list"){
        require_once("./loglist.php");
    }
    
    if ($action == "download"){  
      header("Content-type: application/octet-stream" ); 
      header("Content-Disposition: attachment; filename=".$log);
      readfile ($file);
    }
 
?>
