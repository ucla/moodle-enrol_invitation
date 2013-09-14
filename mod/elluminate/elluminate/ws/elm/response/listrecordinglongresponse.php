<?php
class Elluminate_WS_ELM_Response_ListRecordingLongResponse implements Elluminate_WS_APIResponseHandler{
    
   private $logger;
   
   private $recordingFactory;

   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_ELM_Response_ListRecordingLongResponse");
   }
   
   public function __set($property, $value) {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }

   public function processResponse($apiResponse){
      $this->logger->debug("processResponse start");
      $recordings = null;
      try{
         $recordings = $this->loadRecordingResponseArray($apiResponse);
      }catch(Exception $e){
         throw new Elluminate_Exception("processResponse: ELM Response Processing Error",0,'user_error_soaperror');
      }
      return $recordings;      
   }
   
   private function loadRecordingResponseArray($apiResponse){
      $recordings = array();
      //Response is a single record
      if ($apiResponse->ListRecordingLongResponse instanceof stdclass){
         array_push($recordings, $this->getRecordingFromAPI($apiResult->ListRecordingLongResponse));
      } else {
         //Response is an array of records
         foreach ($apiResponse->ListRecordingLongResponse as $recordingResponseValues) {
            array_push($recordings, $this->getRecordingFromAPI($recordingResponseValues));
         }
      }
      return $recordings;
   }
   
   private function getRecordingFromAPI($apiResponse){
      $recording = $this->recordingFactory->getRecording();
      $recording->meetingid = $apiResponse->RecordingLongResponse->sessionId;
      $recording->recordingid = $apiResponse->RecordingLongResponse->recordingId;
      $recording->recordingsize = $apiResponse->RecordingLongResponse->recordingSize;
   
      $recording->created = Elluminate_WS_Utils::convertSASDateToPHPDate($apiResponse->RecordingLongResponse->creationDate);
   
      $recording->startdate = Elluminate_WS_Utils::convertSASDateToPHPDate($apiResponse->RecordingLongResponse->roomStartDate);
      $recording->enddate = Elluminate_WS_Utils::convertSASDateToPHPDate($apiResponse->RecordingLongResponse->roomEndDate);
      $recording->roomname = $apiResponse->RecordingLongResponse->roomName;
      $recording->securesignon = $apiResponse->RecordingLongResponse->secureSignOn;
      $recording->url = $apiResponse->RecordingLongResponse->recordingURL;
   
      return $recording;
   }
}
