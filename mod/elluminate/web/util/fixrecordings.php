<?php
include ('../includes/moodle.required.php');

require_login();

$systemcontext = context_system::instance();
require_capability('mod/elluminate:manage',$systemcontext);

$recordingDAO = $ELLUMINATE_CONTAINER['recordingDAO'];

$allRecordings = $recordingDAO->getAllRecordings();
echo "============================<br>";
$recordingList = array();
foreach ($allRecordings as $recording){
   $rid = $recording->recordingid;
   echo "Check Recording ID:" . $rid . "<br>";
   
   $recordingFiles = $recordingDAO->getRecordingFiles($rid);
   if ($recordingFiles == null){
      echo "Recordings Files Missing, fixing.<br>";
      $recordingList[] = $rid;
   }
}
   
if (sizeof($recordingList) > 0){
   $statusupdater = $ELLUMINATE_CONTAINER['recordingStatusUpdater'];
   $statusupdater->doRecordingFileStatusUpdate($recordingList);
}

echo sizeof($recordingList) . " recordings fixed.<br>";
echo "============================<br>";