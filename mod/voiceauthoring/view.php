<?PHP
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2012  Blackboard Collaborate, All Rights Reserved.      *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Blackboard Collaborate.              *
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
 *      along with the Blackboard Collaborate Moodle Integration;             *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Hugues Pisapia                                                     *
 *                                                                            *
 * Date: April 2006                                                           *
 *                                                                            *
 ******************************************************************************/

/* $Id: view.php 64437 2008-06-16 15:03:21Z thomasr $ */
error_reporting(E_ERROR);
require_once('../../config.php');
require_once('lib.php');     
//Wimba Library
require_once ("lib/php/common/WimbaLib.php");
  
$id = optional_param('id', 0, PARAM_INT);   // Course Module ID, or
$action = optional_param('launchCal',"", PARAM_TEXT);

if ( empty($action) ) 
{
        if (! $cm = $DB->get_record("course_modules", array("id" => $id)))
        {
            print_error("Course Module ID was incorrect");
        }
        
        if (! $course = $DB->get_record("course", array("id" => $cm->course)))
        {
            print_error("Course is misconfigured");
        }
        
        if (! $voicetool = $DB->get_record("voiceauthoring", array("id" => $cm->instance)))
        
        {
            print_error("Course module is incorrect");
        }
} 
else 
{
        if (! $voicetool = $DB->get_record("voiceauthoring", array("id" => $id)))
        {
            print_error("Course module is incorrect");
        }
        if (! $course = $DB->get_record("course", array("id" => $voicetool->course)))
        {
            print_error("Course is misconfigured");
        }
        if (! $cm = get_coursemodule_from_instance("voiceauthoring", $voicetool->id, $course->id)) 
        {
            print_error("Course Module ID was incorrect");
        }
}


//require_login($voicetool->course);
$PAGE->set_url('/mod/voiceauthoring/view.php');
$cm = get_coursemodule_from_instance("voiceemail", $voicetool->id, $course->id);
require_login($course->id, false, $cm);
$PAGE->set_pagelayout('base');

//redirection to the course page
//redirection("$CFG->wwwroot/course/view.php?id=$course->id#$voicetool->section");
$url = "displayPlayer.php?rid=".$voicetool->rid."&mid=".$voicetool->mid."&title=".urlencode($voicetool->activityname);
if ($voicetool->isfirst == 0)
{
    $voicetool->isfirst = 1;
    /*$voicetool->name = addslashes($voicetool->name); addslashes not used after Moodle 2.0 */
    $DB->update_record("voiceauthoring",$voicetool); 
    redirection("$CFG->wwwroot/course/view.php?id=$course->id#$voicetool->section");
}
else if( !empty($action) &&  format_string ("<iframe>test</iframe>") != "test" )//iframe allowed
{
     redirection("$CFG->wwwroot/course/view.php?id=$course->id&launchCal=".$action);
}
else
{

$servername = $CFG->voicetools_servername;
$strvoicetools = get_string("modulenameplural", "voiceauthoring");
$strvoicetool  = get_string("modulename", "voiceauthoring");

$sentence1 = get_string ('vtpopupshouldappear.1', 'voiceauthoring');
$sentence2 = "<a href='javascript:openVA()';>".get_string ('vtpopupshouldappear.2', 'voiceauthoring')."</a>";
$sentence3 = get_string ('vtpopupshouldappear.3', 'voiceauthoring');
$strLaunchComment = $sentence1.$sentence2.$sentence3;  

$PAGE->set_title($course->shortname. ': '. format_string($voicetool->activityname));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strvoicetools, new moodle_url('/mod/voiceauthoring/index.php', array('id'=>$course->id)));
echo $OUTPUT->header();

?>
<link rel="STYLESHEET" href="css/StyleSheet.css" type="text/css" />
<script>
function openVA(){
    pop = window.open ("<?php echo $url?>", "va_popup", "width=310,height=155,location=0;scrollbars=0,toolbar=0,menubar=0,resizable=yes")   

    if(pop == null) return false;
    if(pop.closed != false) return false;

}

  
if(openVA() != false)
    top.location="<?php echo $CFG->wwwroot?>/course/view.php?id=<?php echo $course->id?>#<?php echo $voicetool->section?>";
    
</script>
<?php }?>

<div style="border:solid 1px #808080;width:700px;height:400px;background-color:white;margin: auto;"  class="general_font">
    <div class="headerBar">
        <div class="headerBarLeft" >
            <span>Blackboard Collaborate</span>
        </div>
    </div>
     <div style="height:340px;width:700px;">
        <span style="display:block;padding-top:150px;padding-left:200px">
            <?php echo $strLaunchComment;?>
        </span>
    </div>
     <div style="border-top:1px solid; background-color:#F0F0F0;width:700px;height:25px">
        <a href="<?php echo $CFG->wwwroot;?>/course/view.php?id=<?php p($course->id)?>"   style="padding-left: 550px; margin-top: 2px;" class="regular_btn">
            <span style="width:110px"><?php echo get_string ('close', 'voiceauthoring'); ?></span>
        </a>                                               
    </div>
</div>
<?php
    /// Finish the page
    echo $OUTPUT->footer();
?>
