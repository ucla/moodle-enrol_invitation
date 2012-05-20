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

/* $Id: voiceauthoring.php 45764 2007-02-28 22:04:25Z thomasr $ */
error_reporting(E_ERROR);
?>
<html>
<head>
  <title>Voice Authoring</title>

<script language="javascript" src="lib/web/js/lib/prototype/prototype.js"></script>
<script language="javascript" src="lib/web/js/constants.js"></script>
<script language="javascript" src="lib/web/js/wimba_ajax.js"></script>
<link rel="STYLESHEET" href="css/StyleSheet.css" type="text/css" />
</head>

<body>
<?php    
require_once("../../config.php");
require_once("lib.php");
require_once('lib/php/vt/VtAction.php');
require_once('lib/php/vt/WimbaVoicetools.php'); 
require_once('lib/php/vt/WimbaVoicetoolsAPI.php');
global $CFG,$USER;
$vtAction=new vtAction($USER->email);

$course_id=optional_param('course_id', 0, PARAM_INT) ;
$block_id=optional_param('block_id', 0, PARAM_INT) ;   

require_login($course_id);

if(!isset($CFG->voicetools_servername)) 
{       
        echo "<script language='javascript'>window.location.replace('error.php?error=problem_vt')</script>";
        exit();
}  
$vtUser = new VtUser(NULL);
$vtUserRigths = new VtRights(NULL);  

$context = get_context_instance(CONTEXT_COURSE, $course_id) ;
if ( voiceauthoring_getRole($context) == "Instructor") 
{
    $vtUserRigths->setProfile ('moodle.recorder.instructor'); 
    $type="record";    
}
else 
{  
    $vtUserRigths->setProfile ( 'moodle.recorder.student');
    $type="play"; 
}

$rid = voiceauthoring_get_resource_rid($course_id); 

if($rid === false)// the resource is not yet created
{
  $result = $vtAction->createRecorder("Voice Authoring associated to the course ".$course_id);//create the resource on the vt
  if($result!=NULL && $result->error != "error")
  {       
   
    if(!storeResource($result->getRid(),$course_id,"recorder",$course_id."_recorder"))
    {
         $rid = $result->getRid();
        //problem to insert the record in db
    }
  }
}

$resource = voicetools_api_get_resource($rid); 
if( $resource )
{
    //echo "<script language='javascript'>window.location.replace('error.php?error=problem_vt')</script>";
    //exit(); 

    $message=new vtMessage(null);
    $message->setMid($block_id);  
    
    $result=$vtAction->getVtSession($resource,$vtUser,$vtUserRigths,$message);
    if($result === false)
    {        
        $error = "There is a problem to display the voice authoring";
       // echo "<script language='javascript'>window.location.replace('error.php?error=problem_vt')</script>";
      //  exit();
    }           
    $recorderInformations = voiceauthoring_get_block_informations($block_id);

} else {
    $error = "There is a problem to display the voice authoring";
}
$comment = isset($recorderInformations->comment) ? $recorderInformations->comment : '';
$getnid = $result ? $result->getNid() : null;

?>



<div>
    <p>
        <span class="general_font"> <?php echo $comment; ?> </span>
    </p>
    <p style="margin-left:-5px">
       <?php if(isset($error))
             {
                 echo $error;
             }
             else
             {?>
             
            <SCRIPT type="text/javascript">
                this.focus();
            </SCRIPT>
            
            <SCRIPT type="text/javascript" SRC="<?php echo $CFG->voicetools_servername;?>/ve/<?php echo $type;?>.js"></SCRIPT>
            <SCRIPT type="text/javascript">
                var w_p = new Object();
                w_p.nid="<?php echo $result->getNid();?>";
                  
                  
                
                  w_p.width="200px";
                  w_p.bg="white";
                  w_p.border="0";
                  
                  if (window.w_ve_play_tag) w_ve_play_tag(w_p);
                  
                    
                 else if (window.w_ve_record_tag) w_ve_record_tag(w_p);
                 else document.write("Applet should be there, but the Voice Tools server is down");
            </SCRIPT>
            <?php } ?>           
    </p>
</div>
</html>





