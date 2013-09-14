<?php 
include ('web/includes/moodle.required.php');
//Page Parameters
$recordingid = required_param('rid', PARAM_INT);
$format = required_param('format', PARAM_ALPHANUM);

//For non-vcr formats, always run a status check to make sure the recording is present.
if ($format != Elluminate_Recordings_Constants::VCR_FORMAT){
   $statusUpdateHelper = $ELLUMINATE_CONTAINER['recordingStatusUpdater'];
   $statusUpdateHelper->doSingleFileRecordingFileStatusUpdate($recordingid,$format);
}

//include to retrieve session, recording, and course module 
include ('web/includes/recording.load.php');

//Permission Checks
include ('web/includes/session.permissions.php');
include ('web/includes/recording.permissions.php');
include ('web/includes/recording.permissioncheck.php');

if ($format != Elluminate_Recordings_Constants::VCR_FORMAT && ! 
         $recordingPermissions->isMP4PlaybackEnabled()){
   print_error(get_string('recordingmp4notlicensed','elluminate'));
}

$playView = $ELLUMINATE_CONTAINER['recordingPlayView'];
$playView->permissions = $recordingPermissions;

//Audit Log
Elluminate_Audit_Log::log(Elluminate_Audit_Constants::RECORDING_VIEW,
   $playView::getPageUrl($recordingid, $format),$pageSession, $cm);

$launchURL = $playView->getLaunchUrl($recording,$format);

if ($launchURL){
   header('Location:' . $launchURL);
}else{
   //No URL, display error message
   //Setup page information
   $pageTitle = get_string('recordinglauncherror','elluminate');
   $pageHeading = format_string(get_string('recordingat','elluminate') . userdate($recording->created));
   $pageUrl = "/" . $playView::getPageUrl($recordingid,$format);

   //PAGE OUTPUT STARTS
   include ('web/includes/moodle.header.php');

   echo $playView->getRecordingLaunchErrorMessage($recording, $format);
   echo $playView->getViewSessionLink($cm->id);
   
   include ('web/includes/moodle.footer.php');
}
