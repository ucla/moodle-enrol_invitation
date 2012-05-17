<?php

require_once('../../config.php');
require_once('lib.php');   

require_once('lib/php/vt/WimbaVoicetoolsAPI.php');
require_once('lib/php/vt/WimbaVoicetools.php'); 
require_once('lib/php/common/WimbaLib.php'); 
require_once('lib/php/vt/VtAction.php'); 


global $USER, $DB;

//Create the Voice E-mail linked to this actvity
$course_id=optional_param('course_id', '',PARAM_ALPHANUM);
$action=optional_param('action', '',PARAM_ALPHANUM);
$block_id=optional_param('block_id', '',PARAM_ALPHANUM);
$type=optional_param('type', '',PARAM_ALPHANUM);
$servername = $CFG->voicetools_servername;
$context = get_context_instance(CONTEXT_BLOCK, $block_id);
$users =  optional_param('users', '' ,PARAM_TEXT);

//for the configuration
$all=optional_param('block_send_vmail_all_users_enrolled', '0', PARAM_BOOL);
$student=optional_param('block_send_vmail_students', '0', PARAM_BOOL);
$instructor=optional_param('block_send_vmail_instructors', '0', PARAM_BOOL);
$selected=optional_param('block_send_vmail_selected', '0', PARAM_BOOL);

if( $action == "updateConfig" )
{
    $blocks=new Object();
    $blocks->block_id=$block_id;
    $blocks->course_id=$course_id;
     
    $blocks->all_users_enrolled=$all;
    $blocks->student=$student;
    $blocks->instructor=$instructor;
    $blocks->recipient=$selected;
    voiceemail_update_blocks_instance($blocks);
    //redirection to the course page
    parentRedirection("$CFG->wwwroot/course/view.php?id=$course_id");
}
else
{
    if ( $type == "other" ) 
    {
         $emailTo=implode(";",$users);
    }
    else
    {
        $emailTo = voiceemail_getEnrolledUsers ($course_id,$type);
    }
    
    
    $vtAction=new VtAction($USER->email);
    
    //get the voice email linked to this block
    $voiceemail = $DB->get_record("voiceemail_resources", array("block" => $block_id));
        
    if ( empty($voiceemail) )
    {
        $vmail=$vtAction->createVMmail( "Voice email for the course ".$course_id." and the block ".$block_id );
        storeVmailResource( $vmail->getRid(), $course_id, $block_id );
    }
    else
    {
         $vmail=$vtAction->getResource( $voiceemail->rid );
    }
    
    if( $vmail->error == "error" )
    {
        //have to display the error
        return false;
    }
    
    
    $currentUser=$vtAction->createUser($USER->firstname."_".$USER->lastname,$USER->email);
    $currentUserRights=$vtAction->createUserRights($vmail->getType(),voiceemail_getRole($context));
    
    $resourceOptions=&$vmail->options;
    $resourceOptions->setFrom($USER->email);
    $resourceOptions->setTo($emailTo);
    $vtSession=$vtAction->getVtSession($vmail,$currentUser,$currentUserRights)  ;      
    
    redirection($servername."/".$vmail->getType()."?action=display_popup&nid=".$vtSession->getNid());
}
?>
