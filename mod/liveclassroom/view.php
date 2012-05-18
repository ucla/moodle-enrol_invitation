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
 * Date: 15th April 2006                                                      *
 *                                                                            *
 ******************************************************************************/

/* $Id: view.php 80726 2010-11-22 18:57:21Z bdrust $ */

/// This page prints a particular instance of the live classroom links

require_once("../../config.php");
require_once("lib/php/lc/LCAction.php");
require_once("lib/php/common/WimbaCommons.php");
require_once("lib/php/common/WimbaLib.php");
require_once("lib.php");

$PAGE->set_url('/mod/liveclassroom/view.php');

$id = optional_param('id', 0, PARAM_INT);//instance id

if ((isset($_GET["action"]) && $_GET["action"]!="launchCalendar" && $id) || !isset($_GET["action"]) ) 
{
  if (! $cm = $DB->get_record("course_modules", array("id" => $id)))
  {
    print_error("Course Module ID was incorrect");
  }
  if (! $course = $DB->get_record("course", array("id" => $cm->course)))
  {
    print_error("Course is misconfigured");
  }
  if (! $liveclassroom = $DB->get_record("liveclassroom", array("id" => $cm->instance)))
  {
    print_error("This Blackboard Collaborate Classroom instance doesn't exist");
  }
} 
else 
{
  if (! $liveclassroom = $DB->get_record("liveclassroom", array("id" => $id)))
  {
    print_error("This Blackboard Collaborate Classroom instance doesn't exist");
  }
  if (! $course = $DB->get_record("course", array("id" => $liveclassroom->course)))
  {
    print_error("Course is misconfigured");
  }
  if (! $cm = get_coursemodule_from_instance("liveclassroom", $liveclassroom->id, $course->id)) 
  {
    print_error("Course Module ID was incorrect");
  }
}

$PAGE->set_url('/mod/liveclassroom/view.php');
$cm = get_coursemodule_from_instance("liveclassroom", $liveclassroom->id, $course->id);
require_login($course->id, false, $cm);
$PAGE->set_pagelayout('base');

require_login($course->id);    

if ($liveclassroom->isfirst == 0)
{
    $liveclassroom->isfirst = 1;
    /*$liveclassroom->name = addslashes($liveclassroom->name);  addslashes not used after Moodle 2.0 */
    $DB->update_record("liveclassroom",$liveclassroom);
    redirection("$CFG->wwwroot/course/view.php?id=$course->id");
}

$api = new LCAction(null, $CFG->liveclassroom_servername, $CFG->liveclassroom_adminusername, $CFG->liveclassroom_adminpassword, $CFG->dataroot,$liveclassroom->course);
if ($api->errormsg != "") {
    wimba_add_log(WIMBA_ERROR,WC, "Error instaniating LCAction: ".$api->errormsg);
    print_error($api->errormsg);
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
#if(getRoleForWimbaTools($course->id, $USER->id)=="Instructor")
if (liveclassroom_getRole($context) == "Instructor")
{
	$authToken = $api->getAuthokenNormal($course->id."_T",$USER->firstname,$USER->lastname);
}
else
{
	$authToken = $api->getAuthokenNormal($course->id."_S",$USER->firstname,$USER->lastname);

}

$classid = $liveclassroom->type;

//get the room
if(!$room=$api->getRoom($classid)) {
    if(isset($api->errormsg) && $api->errormsg == "-8") {
        print_error(get_string('configerror','liveclassroom').': '.get_string('httpsrequired','liveclassroom').' '.get_string('contactadmin','liveclassroom'));
    } else if(isset($api->errormsg) && $api->errormsg == "-9") {
        print_error(get_string('configerror','liveclassroom').': '.get_string('httpsnotenabled','liveclassroom').' '.get_string('contactadmin','liveclassroom'));
    }
}

$strliveclassrooms = get_string("modulenameplural", "liveclassroom");
$strliveclassroom  = get_string("modulename", "liveclassroom");

$PAGE->set_title($course->shortname. ': '. format_string($liveclassroom->name));
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strliveclassroom, new moodle_url('/mod/liveclassroom/index.php', array('id'=>$course->id)));
echo $OUTPUT->header();

?>
<script type="text/javascript" src='<?PHP p($CFG->liveclassroom_servername)?>/js/launch.js'></script>

<script type="text/javascript">
function startLiveClassroom() {
  startHorizon('<?php p($classid) ?>',null,null,null,null,'hzA=<?php p($authToken) ?>&<?php echo($api->api->get_bridge_header_string()) ?>'); 
}
</script>

<div style="border:solid 1px #808080;width:700px;height:400px;background-color:white;"  class="boxaligncenter general_font">
    <div class="headerBar">
        <div class="headerBarLeft" >
            <span>Blackboard Collaborate</span>
        </div>
    </div>
     <div style="height:340px;width:700px;">
        <span style="display:block;padding-top:150px;padding-left:200px">
                	<?php if($room->isPreview() == false || liveclassroom_getRole($context) == "Instructor"){?>
	                	<script>startLiveClassroom()</script>
	                   <?php echo get_string ('lcpopupshouldappear.1', 'liveclassroom');?>
					    <a href="javascript:startLiveClassroom ();">
						<?php echo get_string ('lcpopupshouldappear.2', 'liveclassroom');?>
						</a>
					   <?php echo get_string ('lcpopupshouldappear.3', 'liveclassroom');
				  
                
                	}else{
						 echo get_string ('activity_tools_not_available', 'liveclassroom');   
          			 }?>
				   </span>
    </div>
     <div style="border-top:1px solid; background-color:#F0F0F0;width:700px;height:25px">
        <a href="<?php echo $CFG->wwwroot;?>/course/view.php?id=<?php p($course->id)?>"   style="padding-left: 550px; margin-top: 2px;" class="regular_btn">
            <span style="width:110px"><?php echo get_string ('close', 'liveclassroom'); ?></span>
        </a>                                               
    </div>
</div>
<?php
	/// Finish the page
echo $OUTPUT->footer();
?>
