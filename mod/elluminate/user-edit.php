<?php 

include('web/includes/moodle.required.php');

$PAGE->requires->js('/mod/elluminate/web/js/add_remove_submit.js');

$id = required_param('id', PARAM_INT);
$type = required_param('type', PARAM_INT);

//Page Details
$pageUrl = "/mod/elluminate/user-edit.php?id=" . $id . "&type=" . $type;

$loadParent = false;
include('web/includes/session.load.php');
include('web/includes/session.loadview.php');
include('web/includes/session.permissions.php');
include('web/includes/session.permissioncheck.php');

//Initialize Group Session if needed
$exitOnError = true;
include('web/includes/session.group-init.php');

//Specific Permission Checks - user requires manage session permissions
if (!$permissions->doesUserHaveManageUserPermissionsForSession($type)) {
   print_error(get_string($permissions->permissionFailureKey,'elluminate'));
}

$myWebHelper = $ELLUMINATE_CONTAINER['userEditorView'];
$myWebHelper->mySession = $pageSession;
$myWebHelper->context = $context;
$myWebHelper->userEditType = $type;

//Handle submit of this form
$data = data_submitted(new moodle_url('/mod/elluminate/user-edit.php'));
if ($data && confirm_sesskey()) {
   $notice = $myWebHelper->handleSubmit($data);
   if ($type == Elluminate_HTML_UserEditor::PARTICIPANT_EDIT_MODE){
      Elluminate_Audit_Log::log(Elluminate_Audit_Constants::SESSION_PARTICIPANT_EDIT, $pageUrl,$pageSession, $cm);
   }else{
      Elluminate_Audit_Log::log(Elluminate_Audit_Constants::SESSION_MODERATOR_EDIT, $pageUrl,$pageSession, $cm);
   }
}

//Build list of moderators and users
$userLists = $myWebHelper->loadUserLists();

$pageTitle = $myWebHelper->getPageTitle();
$pageHeading = $pageTitle;

//Start HTML output
include('web/includes/moodle.header.php');

if (!empty($notice)) {
   echo $OUTPUT->notification($notice);
}

//Set up strings for the HTML fragment include
$strcreator =  $pageSession->getSessionCreator();
$sesskey = sesskey();

$strCurrentUsers = $myWebHelper->getCurrentUserString();
$strAvailableUsers = $myWebHelper->getAvailableUserString();
$strAddButton = $myWebHelper->getAddButtonString();
$strRemoveButton = $myWebHelper->getRemoveButtonString();

include('web/includes/user-edit-fragment.html');

include('web/includes/moodle.footer.php');
