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
 * Author: Hugues Pisapia                                                     *
 *                                                                            *
 * Date: April 2006                                                           *
 *                                                                            *
 ******************************************************************************/

/* $Id: view.php 76014 2009-08-25 20:30:43Z trollinger $ */

error_reporting(E_ERROR);
require_once('../../config.php');
require_once('lib.php');
//Wimba Library
require_once ("lib/php/common/WimbaLib.php");
require_once ("lib/php/common/DatabaseManagement.php");      
require_once('lib/php/vt/WimbaVoicetools.php');   
require_once('lib/php/vt/WimbaVoicetoolsAPI.php');
require_once('lib/php/common/WimbaCommons.php');   
require_once('lib/php/vt/VtAction.php');   

$id = optional_param('id', 0, PARAM_INT);   // Course Module ID, or
$action = optional_param('action', "", PARAM_ACTION);

if(!empty($_SERVER['HTTP_REFERER']) && indexOf($_SERVER['HTTP_REFERER'],"/grader/") != -1){
  //we come from the gradebook
   
    if (! $cm = $DB->get_record("course_modules", array("id" => $id)))
    {
        print_error("Course Module ID was incorrect");
    }
    
    if (! $course = $DB->get_record("course", array("id" => $cm->course)))
    {
        print_error("Course is misconfigured");
    }
    
    if (! $voicetool = $DB->get_record("voiceboard", array("id" => $cm->instance)))
    
    {
        print_error("Course module is incorrect");
    }
    redirection("index.php?id=".$voicetool->course."&action=displayGrade&gradeId=".$voicetool->id."&resource_id=".$voicetool->rid);
    exit();
}
else if ((isset($action) && $action!="launchCalendar" ) && $id || !isset($action) ) 
{
        if (! $cm = $DB->get_record("course_modules", array("id" => $id)))
        {
            print_error("Course Module ID was incorrect");
        }
        
        if (! $course = $DB->get_record("course", array("id" => $cm->course)))
        {
            print_error("Course is misconfigured");
        }
        
        if (! $voicetool = $DB->get_record("voiceboard", array("id" => $cm->instance)))
        
        {
            print_error("Course module is incorrect");
        }
} 
else 
{
        if (! $voicetool = $DB->get_record("voiceboard", array("id" => $id)))
        {
            print_error("Course module is incorrect");
        }
        if (! $course = $DB->get_record("course", array("id" => $voicetool->course)))
        {
            print_error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("voiceboard", $voicetool->id, $course->id))
        {
            print_error("Course Module ID was incorrect");
        }
}

$PAGE->set_url('/mod/voiceboard/view.php', array('id'=>$id));

$cm = get_coursemodule_from_instance("voiceboard", $voicetool->id, $course->id);
require_login($course->id, false, $cm);
$PAGE->set_pagelayout('base');

if ($voicetool->isfirst == 0)
{
    $voicetool->isfirst = 1;
    /*$voicetool->name = addslashes($voicetool->name); addslashes not used after Moodle 2.0 */     
    $DB->update_record("voiceboard",$voicetool);
    redirection("$CFG->wwwroot/course/view.php?id=$course->id");
}

$servername = $CFG->voicetools_servername;
$strvoicetools = get_string("modulenameplural", "voiceboard");
$strvoicetool  = get_string("modulename", "voiceboard");

$sentence1=get_string ('vtpopupshouldappear.1', 'voiceboard');
$sentence2="<a href='javascript:startVoiceBoard ()';>".get_string ('vtpopupshouldappear.2', 'voiceboard')."</a>";
$sentence3=get_string ('vtpopupshouldappear.3', 'voiceboard');
$strLaunchComment=$sentence1.$sentence2.$sentence3;                           

$PAGE->set_title($course->shortname. ': '. format_string($voicetool->name));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

$roleSwitch=isSwitch();//the user have switched his role?
//determinate the role for the wimba tools 
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$role=voiceboard_getRole($context);  

//get the informations related to the Vt resource
$vtAction=new vtAction($USER->email); 
$resource=$vtAction->getResource($voicetool->rid) ;  
//check the availability of the resource   
$vtpreview=isVtAvailable($voicetool->rid);
    
if($resource->error==true)
{
    wimba_add_log(WIMBA_ERROR,voiceboard_LOGS,"view.php : problem to get the resource(rid : ".$voicetool->rid.") linked to this activity"); 
    print_error(get_string("problem_vt", "voiceboard"), "$CFG->wwwroot/course/view.php?id=$course->id");
}


$currentUser=$vtAction->createUser($USER->firstname."_".$USER->lastname,$USER->email);
$currentUserRights=$vtAction->createUserRights($resource->getType(),$role);

//get the vt session
$vtSession=$vtAction->getVtSession($resource,$currentUser,$currentUserRights)  ;      
  
?>
<link rel="STYLESHEET" href="css/StyleSheet.css" type="text/css" />
<script  type="text/javascript">  
var popup;  
function doOpenPopup (url, type)
{                 
  popup=window.open (url, type+"_popup", "scrollbars=no,toolbar=no,menubar=no,resizable=yes");    
}

function startVoiceBoard(){
  <?php if(isset($roleSwitch) && $roleSwitch==true)  { ?>
        result=window.confirm("<?php echo get_string ('launchstudent', 'voiceboard');?>");
        if(result==true) {     
     	  doOpenPopup("<?php echo $servername ?>/<?php echo $resource->getType();?>?action=display_popup&nid=<?php echo $vtSession->getNid() ?>","<?php echo $resource->getType()?>");
        } 
        else
        {
          location.href= document.referrer;                     
        }
 <?php }else{ ?>
        doOpenPopup("<?php echo $servername ?>/<?php echo $resource->getType();?>?action=display_popup&nid=<?php echo $vtSession->getNid() ?>","<?php echo $resource->getType()?>");      
 <?php } ?>
}
  
</script>

<div style="border:solid 1px #808080;width:700px;height:400px;background-color:white;" class="boxaligncenter general_font">
     <div class="headerBar">
            <div class="headerBarLeft" >
                <span>Blackboard Collaborate</span>
            </div>
    </div>
    <div style="height:340px;width:700px;">
        <span style="display:block;padding-top:150px;padding-left:200px">
            <?php if($vtpreview==false && $role != "Instructor")
                  {
            	        echo get_string ('activity_tools_not_available', 'voiceboard');
                  }
                  else
                  { 
                  	    echo $strLaunchComment ;  
                  	    ?>
                  	     <script>
                            startVoiceBoard();
                        </script>   
                  	    <?php
                  }
            ?>
           
        </span>
    </div>
     <div style="border-top:1px solid; background-color:#F0F0F0;width:700px;height:25px">
        <a href="<?php echo $CFG->wwwroot;?>/course/view.php?id=<?php p($course->id)?>"   style="padding-left: 550px; margin-top: 2px;" class="regular_btn">
            <span style="width:110px"><?php echo get_string ('close', 'voiceboard');?></span>
        </a>                                               
    </div>
</div> 
<?php
    /// Finish the page
    echo $OUTPUT->footer();
?>
