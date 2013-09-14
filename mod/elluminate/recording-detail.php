<?php
include ('web/includes/moodle.required.php');

//Page parameters
$recordingid = required_param('rid', PARAM_INT);

//Setup detail view helper
$detailViewHelper = $ELLUMINATE_CONTAINER['recordingDetailView'];

//Check if manual status update parameter is present
//must occur before load of recording
include ('web/includes/recording.manualupdate.php');

//include to retrieve session, recording, and course module 
include ('web/includes/recording.load.php');

//Permission Checks
include ('web/includes/session.permissions.php');
include ('web/includes/recording.permissions.php');
include ('web/includes/recording.permissioncheck.php');

if (! $recordingPermissions->doesUserHaveConvertPermissionsForRecording()){
   print_error(get_string($recordingPermissions->permissionFailureKey,'elluminate'));
}

//Setup page information
$pageTitle = format_string(get_string('recordingat','elluminate') . userdate($recording->created));
$pageHeading = $pageTitle;
$pageUrl = "/" . $detailViewHelper::getPageUrl($recordingid);

//PAGE OUTPUT STARTS
include ('web/includes/moodle.header.php');

echo $detailViewHelper->getRecordingDetailTable($pageHeading, $recording);

include ('web/includes/moodle.footer.php');