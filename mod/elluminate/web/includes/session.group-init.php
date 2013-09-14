<?php
/**
 * This include will:
 *   1.) Check if the current page session is a group session
 *   2.) If yes, and the meeting id for the page session is null, send a request to SAS to initialize meeting 
 *   
 * Variables to be set before including:
 *   $pageSession
 *   
 */

if ($pageSession->isGroupSession()){
   //Add session to server, handle error
   try {
      $initializer = $ELLUMINATE_CONTAINER['groupSessionInitializer'];
      $initializer->initializerSession = $pageSession;
      $initializer->initSession($context);
   } catch (Exception $e) {      
      $a = new stdclass;
      $a->id = $pageSession->id;
      $a->group = $pageSession->groupid;
      $a->errormsg = $e->getMessage();
      $groupError = get_string('groupiniterror','elluminate',$a);
      if (isset($exitOnError)){
         print_error($groupError);
      }
   }
}