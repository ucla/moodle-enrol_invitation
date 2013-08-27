<?php 
/** MOODLE REQUIRED FILE **/
include ('web/includes/moodle.required.php');

$PAGE->requires->js('/mod/elluminate/web/js/elluminate-ajax.js');
$PAGE->requires->js('/mod/elluminate/web/js/recording.js');

//COURSE MODULE ID
$id = required_param('id', PARAM_INT);
$groupid = optional_param('group',null, PARAM_INT);

include('web/includes/view.load-session.php');
//Check if group mode switch is required
include('web/includes/session.group-switch.php');
//Load View Container
include('web/includes/session.loadview.php');
//Login and View Session Permissions
include('web/includes/session.permissions.php');
include('web/includes/session.permissioncheck.php');

$pageView->permissions = $permissions;

//Initialize Group Session if needed.  In this case we don't exit on error
include('web/includes/session.group-init.php');

//HTML output starts here
//Setup for page header output
if($permissions->doesUserHaveLoadPermissionsForSession()){
   $pageButton = $pageView->getSessionUpdateButtonHTML();
}
$pageUrl = $pageView::getPageUrl($cm->id);
$pageHeading = get_string('details','elluminate');
$pageTitle = format_string($pageSession->name);
$pageGroups = true;
include('web/includes/moodle.header.php');

include('web/includes/recording.permissions.php');
include('web/includes/view.recording-form.php');

if (isset($groupError)){
   echo $OUTPUT->notification($groupError);
}
//Main Session Info Table
echo $pageView->getSessionInfoTable();

//Recording List
echo $recordingListViewHelper->getRecordingTable($editrecordingdesc);

//Audit Record
Elluminate_Audit_Log::log(Elluminate_Audit_Constants::SESSION_VIEW, $pageView::getPageUrl($cm->id), $pageSession, $cm);

//check if group has been switched and display ui
include('web/includes/view.group-switch.php');

include('web/includes/moodle.footer.php');