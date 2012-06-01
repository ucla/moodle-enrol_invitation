<?php 
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2009  Wimba, All Rights Reserved.                       *
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
 * Author: Thomas Rollinger                                                   *
 *                                                                            *
 * Date: July,Mon 2009                                                       *
 *                                                                            *
 ******************************************************************************/

/* This page manage the grading of the resource */
global $CFG;
error_reporting(E_ERROR);
require_once ('../../config.php');
require_once ('lib.php');
require_once ("lib/php/common/WimbaLib.php");
require_once ("lib/php/common/DatabaseManagement.php");
require_once ("lib/php/common/WimbaCommons.php");
require_once ('lib/php/vt/WimbaVoicetools.php');
require_once ('lib/php/vt/WimbaVoicetoolsAPI.php');
require_once ('lib/php/vt/VtAction.php');

$keys=array_merge(getKeysOfGeneralParameters(),getKeyWimbaVoiceForm());
foreach($keys as $param){ 
	$value=optional_param($param["value"],$param["default_value"],$param["type"]);
	if($value!=null)
		$params[$param["value"]] = $value;
}  


require_login($params["enc_course_id"]);



$session = new WimbaMoodleSession($params);
$resource_id = $params["resource_id"];
$redirectionUrl='welcome.php?id=' . $params["enc_course_id"] . '&' . 
                voicepresentation_get_url_params($params["enc_course_id"]) . '&time=' . $session->timeOfLoad;
if ( $session->error === false && $session != NULL ) 
{

  $cancelUrl='index.php?id=' . $params["enc_course_id"] ;
  $context = get_context_instance(CONTEXT_COURSE, $params["enc_course_id"]);
  $adminUsers = get_users_by_capability($context,'mod/voicepresentation:presenter');
  $allusers = get_enrolled_users(get_context_instance(CONTEXT_COURSE, $params['enc_course_id']));

  //$users also contain the users which have this capabilities at the system level
  if(function_exists('array_diff_key')) { // PHP 5+
    $students=array_diff_key($allusers,$adminUsers);//we get the student by getting the diff of the two arrays
  } else { // PHP 4.x
    $students=array_diff_assoc($allusers,$adminUsers);//we get the student by getting the diff of the two arrays
  }
  $users_key = array_keys($students);
  
  $vtAction = new vtAction( $session->getEmail(), $params );
  $resource = $vtAction->getResource($resource_id);
  if($resource === false){//problem to get the grade
    redirection($redirectionUrl . '&error=problem_vt');
  }
  $options = $resource->getOptions();
  $isAverageMethodAvailable = true;
  $arrayAverageLength = $vtAction->getAverageMessageLengthPerUser($resource_id);
  if($arrayAverageLength == "not_implemented")
  {
    $isAverageMethodAvailable = false;
  }
  
  $arrayNbMessage = $vtAction->getNbMessagePerUser($resource_id);
  $urlParams = 'resource_id='.$resource_id.'&type=presentation&id=' . $params["enc_course_id"] . '&' . 
                voicepresentation_get_url_params($params["enc_course_id"]) . '&time=' . $session->timeOfLoad;
 
  $previousGrade=grade_get_grades($params["enc_course_id"], "mod", "voiceboard", $params["gradeId"], $users_key) ;       
   
  if(!empty($previousGrade) && isset($previousGrade->items[0])){
     //extract only the grade information from the object that we get 
     //we take the items 0 because we ask only for one grade item 
     $previousGrade=$previousGrade->items[0]->grades;         
  }


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" >
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <title>Voice Presentation grading page</title>
    <link rel="STYLESHEET" href="css/StyleSheet.css" type="text/css" />
    <!--[if lt IE 7]>
            <script type="text/javascript" src="lib/web/js/lib/iepngfix/iepngfix_tilebg.js"></script>
    <![endif]-->
    <script type="text/javascript" src="lib/web/js/lib/prototype/prototype.js"></script> 
    <script type="text/javascript" src="lib/web/js/wimba_commons.js"></script>
    <script type="text/javascript" src="lib/web/js/wimba_ajax.js"></script> 
    <script type="text/javascript" src="lib/web/js/verifForm.js"></script> 
    <script type="text/javascript" src="lib/web/js/constants.js"></script> 
<style>
*{
    margin:0; 
    padding:0;
}
</style>   

</head>
<body id="body">    
<div class="content general_font" id=content style="width:700px;height:400px;background-color:white;border: solid 1px #D9DEE5;" align="center">
    <form name="myform" id="myform" method="post">
        <input type="hidden" name="resource_id" id="resource_id" value='<?php echo $resource_id?>'/>
        
        <div class="headerBar">
            <div class="headerBarLeft" >
                <span>Blackboard Collaborate</span>
            </div>
        </div>
         <div class="contextBar" style="padding-top: 10px; padding-left:10px; height:35px">
            <div style="display: block; float: left;">
                <span style="float: left; width: 130px; font-size: 11px; text-align: right; padding-right: 5px;font-weight: bold;"><?php echo get_string('grade_vb_name', 'voicepresentation');?></span><span><?php echo $resource->getTitle();?></span> <br/> <span style="float: left; width: 130px; font-size: 11px; text-align: right; padding-right: 5px; font-weight: bold;"><?php echo get_string('points_possible', 'voicepresentation');?></span><span>  <?php echo $options->getPointsPossible();?></span>
            </div>
             <div style="float: right;padding-right:15px">
               <input type="button" onclick="javascript:LaunchVoiceTools('manageAction.php','<?php echo $urlParams;?>');" value="<?php echo get_string('grade_open_board', 'voicepresentation');?>"/>
            </div>
        </div>
         <div style="ooverflow: hidden; width: 700px; background-color: rgb(228, 233, 237); height: 30px;border-top:1px solid #718a9d;border-bottom:1px solid #718a9d;">
           <table width="685px"  cellspacing="0" cellpadding="1" border="0" style="float: left;">
        	<tr style="background-color:#E4E9ED;height:30px;" >
        		<th><span style="display:block;width:140px;text-align:left;display:block;font-size: 11px;"><?php echo get_string('grade_last_name', 'voicepresentation');?></span></th>
        		<th><span style="display:block;width:140px;text-align:left;display:block;font-size: 11px;"><?php echo get_string('grade_first_name', 'voicepresentation');?></span></th>
        		<th><span style="display:block;width:140px;text-align:left;display:block;font-size: 11px;"><?php echo get_string('grade_user_name', 'voicepresentation');?></span></th>
        		<th><span style="display:block;width:65px;font-size: 11px;"><?php echo get_string('grade_posts', 'voicepresentation');?></span></th>
        		<th><span style="display:block;width:100px;font-size: 11px;"><?php if($isAverageMethodAvailable){ echo get_string('grade_avg_length', 'voicepresentation'); }?></span></th>
        		<th><span style="display:block;width:100px;font-size: 11px;"><?php echo get_string('grade_points', 'voicepresentation');?></span></th>
        	</tr>
           </table>
          </div>
          <div style="height: 264px;overflow-y:scroll;overflow-x:hidden;clear:left;">
           <table cellspacing="0" cellpadding="1" border="0">
        	<?php 
        	for ($i = 0; $i < count($users_key); $i++) 
    		{		
    		  
    		?>
        	<tr class="board" ondblclick="javascript:LaunchVoiceTools('manageAction.php','<?php echo $urlParams;?>&filter_screen_name=<?php echo $students[$users_key[$i]]->firstname.'_'.$students[$users_key[$i]]->lastname;?>')">
            	<td style="width:140px;"><span style="text-align:left;padding-left:2px;overflow: hidden; width: 130px;display:block;" title="<?php echo $students[$users_key[$i]]->lastname;?>"><?php echo $students[$users_key[$i]]->lastname;?></span></td>
            	<td style="width:140px;"><span style="text-align:left;padding-left:2px;overflow: hidden; width: 130px;display:block;" title="<?php echo $students[$users_key[$i]]->firstname;?>"><?php echo $students[$users_key[$i]]->firstname;?></span></td>
            	<td style="width:140px;"><span style="text-align:left;padding-left:2px;overflow: hidden; width: 130px;display:block;" title="<?php echo $students[$users_key[$i]]->username;?>"><?php echo $students[$users_key[$i]]->username;?></span></td>
            	<td align="center" style="width:65px;">
            		<span>
            		<?php 
            		if(isset($arrayNbMessage[strtolower($students[$users_key[$i]]->firstname."_".$students[$users_key[$i]]->lastname)]))
            		{
            		  echo $arrayNbMessage[strtolower($students[$users_key[$i]]->firstname."_".$students[$users_key[$i]]->lastname)];
            		}else
            		{
            			echo "-";
            		}
            		?>
            		</span>
            	</td>
            	<td align="center" style="width:100px;">
            		
            		<span>
            		<?php
            		if($isAverageMethodAvailable){ 
                		if(isset($arrayAverageLength[strtolower($students[$users_key[$i]]->firstname."_".$students[$users_key[$i]]->lastname)]))
                		{
                          $str = "";
                          $avgLenght=$arrayAverageLength[strtolower($students[$users_key[$i]]->firstname."_".$students[$users_key[$i]]->lastname)];
                          $hours = intval(intval($avgLenght) / 3600);
                          if($hours > 0)
                          {
                              $str .= $hours.":";
                          }
    
                          $minutes = sprintf("%02X",fmod((intval($avgLenght) / 60),60));
                          if($hours > 0 || $minutes > 0)
                          {
                              $str .= $minutes.":";
                          }else{
                              $str .= "0:";
                          }
                      
                        
                          $seconds = fmod(intval($avgLenght),60);
                          if($seconds<10)
                          {  
                            $str .= "0".$seconds;
                          }
                          else
                          {
                            $str .= $seconds;
                          }
                          
                		  echo $str;
                		}else{
                			echo "-";
                		}
            		}
            		?>
            		</span>
            	</td>
            	<td style="width:100px;" align="center">
            		<input type="text" size="7" name="grades[<?php echo $users_key[$i];?>]" id="grades[<?php echo $users_key[$i];?>]" value="<?php echo (isset($previousGrade[$users_key[$i]]) && !empty($previousGrade[$users_key[$i]]->grade))?number_format($previousGrade[$users_key[$i]]->grade, 2, '.', ''):''?>" style="height: 18px; margin-top: 3px; margin-bottom: 3px;">
            	</td>
        	</tr>
        	<?php }?>
        	</table>
        
        </div>
        <div class="validationBar" id="validationBar">
        	<ul class="regular_btn_list" style="float: right; padding-top: 2px;">
	        	<li>
	        		<a class="regular_btn" href="#" onclick="javascript:top.location='<?php echo $cancelUrl?>';">
	        			<span style="width: 110px;"><?php echo get_string('validationElement_cancel','voicepresentation');?></span>
	        		</a>
	        	</li>
		        <li>
		        	<input type="submit" class="regular_btn-submit" onclick="javascript:submitGradeForm('manageAction.php','submitGrade','<?php echo $urlParams;?>')" value="<?php echo get_string('validationElement_saveAll','voicepresentation');?>"/>
		        </li>
        	</ul>
        </div>
        
        	</form>  
</div>     
<?php } else {  
	redirection($redirectionUrl . '&error=session');
}?>
     
