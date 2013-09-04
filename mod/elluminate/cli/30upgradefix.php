<?php
/******************************************************************************
 *                                                                            
 * Copyright (c) 2013 Blackboard Inc., All Rights Reserved.                         *
 *                                                                            
 * COPYRIGHT:                                                                 
 *      This software is the property of Blackboard Inc.                           *
 *      It cannot be copied, used, or modified without obtaining an           
 *      authorization from the authors or a mandated member of Blackboard.    
 *      If such an authorization is provided, any modified version            
 *      or copy of the software has to contain this header.                   
 *                                                                            
 * WARRANTIES:                                                                
 *      This software is made available by the authors in the hope            
 *      that it will be useful, but without any warranty.                     
 *      Blackboard Inc. is not liable for any consequence related to the            
 *      use of the provided software.                                         
 *                                                                            
 * Class: ${NAME}                                                     
 *                                                                            
 * Author: dwieser                                               
 *                                                                            
 * Date:  7/5/13                                                         
 *                                                                            
 ******************************************************************************/

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions

require_once($CFG->dirroot . '/mod/elluminate/include/container.php');

echo "Re-attempting 3.0 upgrade";
$retryMode = true;
include_once $CFG->dirroot . '/mod/elluminate/db/elluminate_upgrade_30_rerun.php';