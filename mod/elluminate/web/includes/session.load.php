<?php
/**
 * This include will load a session by session id 
 * This is not course module id - see view.load-session for include to load by course module id
 * 
 * Required to be set:
 * 
 *   $id - session ID parameter
 *   $loadParent - Group Session Only: set to true to load a group parent as opposed to a specific group instance 
 * 
 * 
 * Returns:
 *   $pageSession - loaded session
 *   $cm - course module object
 *   $context - page context
 */
try {
   $loader = $ELLUMINATE_CONTAINER['sessionLoader'];
   $pageSession = $loader->getSessionById($id);
    
   //If this is a group session, we only allow grading on parent session
   //load parent is a flag set by the calling page that can override this
   if ($pageSession->isGroupSession() && $pageSession->groupparentid > 0 && $loadParent){
      $parentid = $pageSession->groupparentid;
      $pageSession = $loader->getSessionById($parentid);
   }
}catch(Elluminate_Exception $e){ 
   print_error(get_string('viewsessionloaderror','elluminate',$e->getMessage()));
}catch(Exception $e){
   print_error(get_string('viewsessiongenericerror','elluminate',$id));
}

//Load Context
if ($pageSession->getSessionType() == Elluminate_Group_Session::GROUP_CHILD_SESSION_TYPE) {
   $cm = get_coursemodule_from_instance('elluminate', $pageSession->groupparentid,$pageSession->course);
} else {
   $cm = get_coursemodule_from_instance('elluminate', $pageSession->id,$pageSession->course);
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);