<?php
// **********   Standard Permission Checks Required to View Recording  ************ //

require_course_login($pageSession->course, true);

$recordingPermissions = $viewContainer->getRecordingPermissions();
$sessionPermissions = $viewContainer->getSessionPermissions();

//View Session Access is required to see any recordings
if (!$sessionPermissions->doesUserHaveViewPermissionsForSession()){
   print_error(get_string($sessionPermissions->permissionFailureKey,'elluminate'));
}

$recordingPermissions->setCurrentRecording($recording);
if (!$recordingPermissions->doesUserHaveViewPermissionsForRecording()){
   print_error(get_string($recordingPermissions->permissionFailureKey,'elluminate'));
}