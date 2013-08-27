<?php
// **********   Standard Permission Checks Required to View Recording  ************ //

require_course_login($pageSession->course, true);

//View Session Access is required to see any recordings
if (!$permissions->doesUserHaveViewPermissionsForSession()){
   print_error(get_string($permissions->permissionFailureKey,'elluminate'));
}

$recordingPermissions->setCurrentRecording($recording);
if (!$recordingPermissions->doesUserHaveViewPermissionsForRecording()){
   print_error(get_string($recordingPermissions->permissionFailureKey,'elluminate'));
}