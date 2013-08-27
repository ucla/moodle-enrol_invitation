<?php
include ('../includes/moodle.required.php');

require_login();

$systemcontext = context_system::instance();
require_capability('mod/elluminate:manage',$systemcontext);

/****************** ELM V1 -> V3 MIGRATION **********************************/
//All existing sessions for an ELM integration need to be updated via the V3
//API to cause them to be updated.  Once updated, the recordings associated
//to all sessions will be migrated and assigned new recording IDs.  Hence, a new

$schedManager = $ELLUMINATE_CONTAINER['schedulingManager'];
$sessionLoader = $ELLUMINATE_CONTAINER['sessionLoader'];
 
//Send a update (no changes) for each existing session
$sessions = $DB->get_records('elluminate');
echo "Sending Update Requests for Sessions<br>";
foreach($sessions as $session){
   if ($session->meetingid != null){
      $sessionObject = $sessionLoader->getSessionById($session->id);
      $schedManager->updateSession($sessionObject);
   }
}
 
//Clear away all old format recordings, they will be rebuilt after sessions
//are upgraded to v3
$recordings = $DB->get_records('elluminate_recordings');
echo "Removing Existing Recordings<br>";
foreach($recordings as $recording) {
   $DB->delete_records('elluminate_recordings');
}

$moodleDAO = $ELLUMINATE_CONTAINER['moodleDAO'];
$moodleDAO->deleteConfigRecord('elluminate_last_cron_run');

echo "<br>Migration Complete.  <a href='../../forcecron.php'>Click Here to run cron to pull back recordings.</a>";
