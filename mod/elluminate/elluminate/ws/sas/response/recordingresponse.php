<?php
/**
 * This handles the response processing for ListRecordings
 *
 * @author dwieser
 *
 */
class Elluminate_WS_SAS_Response_RecordingResponse implements Elluminate_WS_APIResponseHandler {
   private $logger;

   private $recordingFactory;

   public function __construct() {
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_SAS_Response_RecordingResponse");
   }

   public function __set($property, $value) {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }

   public function processResponse($apiResult) {
      $this->logger->debug("processResponse start");
      $recordings = null;
      try {
         //We only continue processing if the root element is present
         if (isset($apiResult->RecordingResponse)) {
            $recordings = $this->loadRecordingResponseArray($apiResult);
         }
      } catch (Exception $e) {
         throw new Elluminate_Exception("SAS Response Processing Error", 0, 'user_error_soaperror');
      }

      $this->logger->debug("processResponse, returning [" . sizeof($recordings) . "] recordings");
      return $recordings;
   }

   private function loadRecordingResponseArray($apiResult) {
      $recordings = array();
      //Response is a single record
      if ($apiResult->RecordingResponse instanceof stdclass) {
         array_push($recordings, $this->getRecordingFromAPI($apiResult->RecordingResponse));
      } else {
         //Response is an array of records
         foreach ($apiResult->RecordingResponse as $recordingResponseValues) {
            array_push($recordings, $this->getRecordingFromAPI($recordingResponseValues));
         }
      }
      return $recordings;
   }

   private function getRecordingFromAPI($apiResponse) {
      $recording = $this->recordingFactory->getRecording();
      $recording->meetingid = $apiResponse->sessionId;
      $recording->recordingid = $apiResponse->recordingId;
      $recording->recordingsize = $apiResponse->recordingSize;

      $recording->created = Elluminate_WS_Utils::convertSASDateToPHPDate($apiResponse->creationDate);

      $recording->startdate = Elluminate_WS_Utils::convertSASDateToPHPDate($apiResponse->roomStartDate);
      $recording->enddate = Elluminate_WS_Utils::convertSASDateToPHPDate($apiResponse->roomEndDate);

      $recording->roomname = $apiResponse->roomName;
      $recording->securesignon = $apiResponse->secureSignOn;

      $version = new Elluminate_Config_Version();
      $version->processWholeVersion($apiResponse->recordingVersion);
      $recording->versionmajor = $version->versionmajor;
      $recording->versionminor = $version->versionminor;
      $recording->versionpatch = $version->versionpatch;
      $recording->version = $version;

      return $recording;
   }
}