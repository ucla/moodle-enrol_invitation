<?php
/**
 * This include will load:
 *  $recording
 *  $pageSession
 *  $cm : Course Module for Session 
 * 
 */
$loader = $ELLUMINATE_CONTAINER['recordingLoader'];
//Load Recording Object and set Parent Session
try {
   $recording = $loader->getRecordingByRecordingId($recordingid);
   if ($recording != null){
      $meetingId = $recording->meetingid;
   }
} catch (Elluminate_Exception $e) {
   print_error(get_string('viewrecordingloaderror','elluminate',$e->getMessage()));
} catch (Exception $e) {
   print_error(get_string('user_error_processing','elluminate'));
}

if ($recording == null){
   print_error('Invalid Recording ID [' . $recordingid . ']');
}

//Load Session and Trap Errors
try {
   $sessionLoader = $ELLUMINATE_CONTAINER['sessionLoader'];
   $pageSession = $sessionLoader->getSessionByMeetingId($meetingId);
}catch(Elluminate_Exception $e){ 
   print_error(get_string('viewsessionloaderror','elluminate',$e->getMessage()));
}catch(Exception $e){
   print_error(get_string('viewsessiongenericerror','elluminate',$meetingId));
}

if ($pageSession->getSessionType() == Elluminate_Group_Session::GROUP_CHILD_SESSION_TYPE){
   $cm = get_coursemodule_from_instance('elluminate', $pageSession->groupparentid,$pageSession->course);
}else{
   $cm = get_coursemodule_from_instance('elluminate', $pageSession->id,$pageSession->course);
}

$context = context_module::instance($cm->id);