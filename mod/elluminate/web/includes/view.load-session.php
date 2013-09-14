<?php
/**
 * This is a standard include file that will load a session from the moodle
 * course module ID (given a parameter of ID).
 */
$cm = get_coursemodule_from_id('elluminate', $id);

if ($cm == null){
   print_error('Incorrect Course Module ID (' . $id . ')');
}

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

if ($groupid === null){
   $groupid = groups_get_activity_group($cm);
}

//Make sure the requested group actually exists
$groupsAPI = $ELLUMINATE_CONTAINER['groupsAPI'];
if ($groupid != null && ! $groupsAPI->doesGroupExist($groupid)){
   print_error(get_string('groupdoesnotexist','elluminate',$groupid));
}

//Load Session and Trap Errors
try
{
   $loader = $ELLUMINATE_CONTAINER['sessionLoader'];
   if ($groupid != null && $groupid != 0) {
      $pageSession = $loader->getSessionByIdAndGroup($cm->instance, $groupid, $cm);
   } else {
      $pageSession = $loader->getSessionById($cm->instance);
   }
}catch(Elluminate_Exception $e){ 
   print_error(get_string('viewsessionloaderror','elluminate',$e->getMessage()));
}catch(Exception $e){
   print_error(get_string('viewsessiongenericerror','elluminate',$cm->instance));
}
