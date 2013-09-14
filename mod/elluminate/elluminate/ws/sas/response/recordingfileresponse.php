<?php
class Elluminate_WS_SAS_Response_RecordingFileResponse implements Elluminate_WS_APIResponseHandler{
   
   private $logger;
    
   private $recordingFactory;
    
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_SAS_Response_RecordingFileResponse");
   }

   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   public function processResponse($apiResult){
      $recordingFiles = null;
      try{
         //We only continue processing if the root element is present
         if (isset($apiResult->RecordingFileResponse)){
            $recordingFiles = $this->loadRecordingFileResponseArray($apiResult);
         }
      }catch(Exception $e){
         throw new Elluminate_Exception("processResponse: SAS Response Processing Error",0,'user_error_soaperror');
      }

      $this->logger->debug("processResponse, returning [" . sizeof($recordingFiles) . "] recordings");
      return $recordingFiles;
   }
   
   private function loadRecordingFileResponseArray($apiResult){
      $recordingFiles = array();
      if ($apiResult->RecordingFileResponse instanceof stdclass){
         array_push($recordingFiles, $this->getRecordingFileFromAPI($apiResult->RecordingFileResponse));
      } else {
         foreach ($apiResult->RecordingFileResponse as $recordingFileResponseValues) {
            array_push($recordingFiles, $this->getRecordingFileFromAPI($recordingFileResponseValues));
         }
      }
      return $recordingFiles;
   }
   
   private function getRecordingFileFromAPI($apiResponse){
      $recordingFile = $this->recordingFactory->getRecordingFile();
   
      $recordingFile->recordingid = $apiResponse->recordingId;
       
      $recordingFile->format = $apiResponse->format;
      $recordingStatus = $apiResponse->status;
      $recordingFile->status = $recordingStatus->recordingStatus;
      if (isset($recordingStatus->errorCode)){
         $recordingFile->errorcode = $recordingStatus->errorCode;
         $recordingFile->errortext = $recordingStatus->errorText;
      }
       
      if (isset($apiResponse->url)){
         $recordingFile->url = $apiResponse->url;
      }
      return $recordingFile;
   }
}