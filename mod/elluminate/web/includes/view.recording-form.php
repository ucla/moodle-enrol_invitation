<?php
/**
 * The Recording List on the View Page has some dynamic form submit actions that need to be
 * handled and lead to the page being re-loaded. 
 * 
 */
$a                  = optional_param('a', 0, PARAM_INT);  // Blackboard Collaborate ID
$editrecordingdesc  = optional_param('editrecordingdesc', 0, PARAM_INT);
$delrecording       = optional_param('delrecording', 0, PARAM_INT);
$hiderecording      = optional_param('hiderecording', 0, PARAM_INT);
$showrecording      = optional_param('showrecording', 0, PARAM_INT);
$hidegrouprecording = optional_param('hidegrouprecording', 0, PARAM_INT);
$showgrouprecording = optional_param('showgrouprecording', 0, PARAM_INT);
$groupid			     = optional_param('group', 0, PARAM_INT);
$delconfirm         = optional_param('delconfirm', '', PARAM_ALPHANUM);

//Clean the recording description to prevent bad data from being stored in DB.
$recordingdesc      = optional_param('recordingdesc', '', PARAM_TEXT);

//Convert Recording Request
$convertrecording   = optional_param('convertRecording', '', PARAM_INT);
$convertformat      = optional_param('format', '', PARAM_TEXT);

//Load View Helper and display list of recordings
$recordingListViewHelper = $ELLUMINATE_CONTAINER['recordingListView'];
$recordingListViewHelper->permissions = $recordingPermissions;
$recordingListViewHelper->pageSession = $pageSession;
$recordingListViewHelper->courseModuleId = $cm->id;

// *** RECORDING DELETE ACTION ***
if (!empty($delrecording)){

   //Confirm permissions, error out if not correct
   if (!$recordingPermissions->doesUserHaveDeletePermissionsForRecording()){
      print_error(get_string($recordingPermissions->permissionFailureKey,'elluminate'));
   }

   //PART 1: only delrecording parameter passed - get confirmation
   if (empty($delconfirm)){
      echo $recordingListViewHelper->getDeleteRecordingConfirmationHTML($delrecording);
      include('web/includes/moodle.footer.php');
      exit;
   }

   //PART 2: delrecording and delconfirm passed - do the delete
   if ($delconfirm == sesskey()){
      $recordingListViewHelper->deleteRecordingAction($delrecording);
      $deleteUrl = $pageView::getPageUrl($cm->id) . "&delrecording=" . $delrecording;
      Elluminate_Audit_Log::log(Elluminate_Audit_Constants::RECORDING_DELETE,$deleteUrl,$pageSession, $cm);
   }
}

// *** RECORDING HIDE ACTION ***
if (!empty($hiderecording)) {
   if (!$recordingPermissions->doesUserHaveToggleVisibilityPermissionsForRecording()){
      print_error(get_string($recordingPermissions->permissionFailureKey,'elluminate'));
   }
   $recordingListViewHelper->hideRecordingAction($hiderecording);
}

// *** RECORDING SHOW ACTION ***
if (!empty($showrecording)) {
   if (!$recordingPermissions->doesUserHaveToggleVisibilityPermissionsForRecording()){
      print_error(get_string($recordingPermissions->permissionFailureKey,'elluminate'));
   }
   $recordingListViewHelper->showRecordingAction($showrecording);
}

// *** EDIT DESCRIPTION SUBMIT ACTION ***
if (($data = data_submitted($CFG->wwwroot . '/mod/elluminate/view.php'))
         && confirm_sesskey()
         && !empty($recordingdesc)) {

   //Fail if permissions check fails
   if (!$recordingPermissions->doesUserHaveEditDescriptionPermissionsForRecording()){
      print_error(get_string($recordingPermissions->permissionFailureKey,'elluminate'));
   }

   if (isset($data->descsave) && !empty($data->recordingid)){
      $recordingId = clean_param($data->recordingid, PARAM_INT);
      $recordingListViewHelper->editRecordingDescriptionAction($recordingId, $recordingdesc);
   }
}