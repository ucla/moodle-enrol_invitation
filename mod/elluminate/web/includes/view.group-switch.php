<?php

//Handle a session that has been forced from group mode to non-group mode
if ($switcher->sessionHasGroupModeOverride($pageSession,$cm->course, $cm->groupmode)){
   if ($pageSession->getSessionType() == Elluminate_Session::GROUP_SESSION_TYPE){
      echo $pageView->getChildSessionLinks($cm);
   }else{
      echo $pageView->getParentSessionLink($cm);
   }
}

