<?php
include ('web/includes/moodle.required.php');

//Parameters
$recordingid = required_param('rid', PARAM_INT);
$format = required_param('format',PARAM_ALPHANUM);
$ajaxmode = optional_param('ajax', 0, PARAM_INT);

//include to retrieve session, recording, and course module
include ('web/includes/recording.load.php');

$errorcode = '';
require_course_login($pageSession->course, true);

include('web/includes/session.permissions.php');
include('web/includes/recording.permissions.php');

//View Session Access is required to see any recordings
if (!$permissions->doesUserHaveViewPermissionsForSession()){
   $errorcode = get_string($permissions->permissionFailureKey,'elluminate');
}

$recordingPermissions->setCurrentRecording($recording);
if (!$recordingPermissions->doesUserHaveViewPermissionsForRecording()){
   $errorcode = get_string($recordingPermissions->permissionFailureKey,'elluminate');
}

if (! $recordingPermissions->doesUserHaveConvertPermissionsForRecording()){
   $errorcode =  get_string($recordingPermissions->permissionFailureKey,'elluminate');
}

$convertView = $ELLUMINATE_CONTAINER['convertView'];

if ($errorcode != ''){
   if ($ajaxmode > 0){   
      echo get_string('error','elluminate');
   }else{
      print_error($errorcode);
   }
}else{
   //Audit Log
   Elluminate_Audit_Log::log(Elluminate_Audit_Constants::RECORDING_CONVERT, $convertView::getPageUrl($recordingid, $format), $pageSession, $cm);

   if ($ajaxmode > 0){
      echo $convertView->requestConversion($recording, $format, $ajaxmode);
   }else{
      //If not ajax mode, redirect back to recording detail page
      $convertView->requestConversion($recording, $format);
      header('Location:' . $convertView->getRecordingDetailSessionUrl($recordingid));
   }
}