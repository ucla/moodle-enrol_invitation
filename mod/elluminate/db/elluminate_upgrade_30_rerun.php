<?php
global $OUTPUT;
require_once($CFG->dirroot . '/mod/elluminate/include/container.php');
$logger = Elluminate_Logger_Factory::getLogger("Elluminate_30_Upgrade");

// Setup some details for error handling
$retryPage = $CFG->wwwroot . "/mod/elluminate/web/util/30_upgrade_retry.php";
$logPage = $CFG->wwwroot . "/mod/elluminate/web/util/logs.php";
$errorDetails = new stdClass;
$errorDetails->retry = $retryPage;
$errorDetails->logs = $logPage;

set_time_limit(0);
// ********* START SAS CONNECTIVITY CHECK ***********/

//Before we do anything else, confirm that API login group has V3 enabled.
//Url has to be changed from v1 to v3. If it's an SAS url, it should end with /v1 which will be updated to /v3.
//If it's not an SAS url, we assume it's ELM and we append /v3 to it.
$serverurl = $CFG->elluminate_server;

$version_index = strrpos($serverurl, "/default/v1");

//If the default value is still set then halt this portion of the upgrade.
//MOOD-573
if ($serverurl == "http://localhost:8080") {
   return false;
}

$isELMURL = 0;
if ($version_index !== false) {
   //This is an SAS url
   $serverurl = substr_replace($serverurl, "/default/v3", $version_index, strlen("/default/v1"));
} else if (strrpos($serverurl, "/v3") === false) {
   $isELMURL = 1;

   //This is an ELM url
   $end_slash = strrpos($serverurl, "/");

   if (($end_slash + 1) == strlen($serverurl)) {
      $serverurl = $serverurl . "v3";
   } else {
      $ending_substring = substr($serverurl, $end_slash);
      if (strpos($ending_substring, ".") !== false) {
         $serverurl = substr($serverurl, 0, $end_slash) . "/v3";
      } else {
         $serverurl = $serverurl . "/v3";
      }
   }
} else {
   $isELMURL = 1;
}

//Adding elluminate_log_level to config table during upgrade
//Log level should be 4, only set to 1 for debugging

//Set to debug during upgrade process only
set_config('elluminate_log_level', '1');

set_config('elluminate_server', $serverurl);
$username = $CFG->elluminate_auth_username;
$password = $CFG->elluminate_auth_password;

set_config('elluminate_server', $serverurl);
$username = $CFG->elluminate_auth_username;
$password = $CFG->elluminate_auth_password;

$schedulingManagerName = '';
try {
   $schedManager = Elluminate_WS_SchedulingManagerFactory::getSchedulingManagerWithSettings($serverurl, $username, $password);
   //Set mdl_config setting for SAS/ELM
   $schedulingManagerName = $schedManager->getSchedulingManagerName();
   set_config('elluminate_scheduler', $schedulingManagerName);

   $schedManager->testConnection();

} catch (Elluminate_Exception $e) {
   echo $OUTPUT->notification(get_string($e->getUserMessage(), 'elluminate'), $e->getDetails());
   if ($e->getMessage() == "You are not permitted to use the Api V3 Adapter") {
      echo $OUTPUT->notification(get_string('upgrade30error_api', 'elluminate', $e->getDetails()));
   }
   return false;
} catch (Exception $e) {
   echo $OUTPUT->notification(get_string('upgrade30error', 'elluminate'));
   return false;
}
// ********* END SAS CONNECTIVITY CHECK ***********/

// ************* START RECORDINGS UPDATE *****************/

//Get all recordings and retrieve full details for them.
if ($schedulingManagerName == 'SAS') {
   $recordings = $DB->get_records('elluminate_recordings');
   $recordingids_array = array();
   foreach ($recordings as $recording) {
      try {
         if (!empty($recording->meetingid)) {
            $logger->debug("Recording v3 Update: Upgrading Recording: " . $recording->recordingid);
            $full_recordings = $schedManager->getRecordingsForSession($recording->meetingid);
         }else{
            $logger->debug("Recording v3 Update: Skipping Invalid Recording: " . $recording->recordingid);
            continue;
         }
      } catch (Exception $e) {
         echo $OUTPUT->notification(get_string('upgrade30error', 'elluminate', $errorDetails));
         return false;
      }

      foreach ($full_recordings as $full_recording) {
         if ($full_recording->recordingid == $recording->recordingid) {
            $recordingids_array[] = $recording->recordingid;
            $recording->versionmajor = $full_recording->versionmajor;
            $recording->versionminor = $full_recording->versionminor;
            $recording->versionpatch = $full_recording->versionpatch;
            $DB->update_record('elluminate_recordings', $recording);
         }
      }
   }

   $recordingStatusUpdater = $ELLUMINATE_CONTAINER['recordingStatusUpdater'];
   $recordingStatusUpdater->doRecordingFileStatusUpdate($recordingids_array);
}

//For ELM, all we will do is create a fake VCR entry in the recording file table
//Caching will handle retrieval of URLs
if ($schedulingManagerName == 'ELM') {
   $recordings = $DB->get_records('elluminate_recordings');
   $recordingids_array = array();
   foreach ($recordings as $recording) {
      $recordingFile = $ELLUMINATE_CONTAINER['recordingFile'];
      $recordingFile->recordingid = $recording->recordingid;
      $recordingFile->format = Elluminate_Recordings_Constants::VCR_FORMAT;
      $recordingFile->status = Elluminate_Recordings_Constants::AVAILABLE_STATUS;
      $recordingFile->save();
   }
}
// ************* END RECORDINGS UPDATE *****************/

// ************* LICENSES UPDATE *****************/
//Do an initial call to the license check cron so that telephony and conversion are enabled right after the
//upgrade
if ($schedulingManagerName == 'SAS') {
   require_once($CFG->dirroot . '/mod/elluminate/include/cron-includes.php');
   try {
      $scheduler = Elluminate_WS_SchedulingManagerFactory::getSchedulingManagerWithSettings($serverurl, $username, $password);
      $licenseChecker = $ELLUMINATE_CONTAINER['cronLicenseCheckAction'];
      $licenseChecker->schedulingManager = $scheduler;
      $licenseChecker->executeFirstCronAction();
   } catch (Exception $e) {
      echo $OUTPUT->notification(get_string('upgrade30error', 'elluminate', $errorDetails));
      return false;
   }
}
// ************* END LICENSES UPDATE *****************/

//Set default telephony value
if ($schedulingManagerName == 'SAS') {
   set_config('elluminate_telephony', '-1');
}

//Set Log Level Back to Error
set_config('elluminate_log_level', '4');

if ($retryMode == true) {
   echo("<br>Upgrade Successful!");
}