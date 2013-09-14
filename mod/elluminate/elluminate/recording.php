<?php

class Elluminate_Recording{   
   //Minimum version required for MP4 conversion
   const MIN_MP4_VERSION = "11.0.0";
   
   const NO_RECORDING_FILE = null;
  
   private $id;
   private $meetingid;
   private $recordingid;
   private $recordingsize;
   private $description;
   private $visible = 1;
   private $groupvisible = 1;
   private $created;
   private $startdate;
   private $enddate;
   private $roomname;
   private $versionmajor;
   private $versionminor;
   private $versionpatch;
   private $securesignon;
   
   //Only used for ELM
   private $url;
   
   //Version Object
   private $version;
   
   private $recordingFiles;
   
   private $serverRecordingManager;
   private $recordingDAO;
   
   private $logger;

   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Recording");
      $this->recordingFiles = array();
   }
   
   public function setServerRecordingManager($serverManager){
      $this->serverRecordingManager = $serverManager;
   }
   
   public function setRecordingDao($recordingDAO){
      $this->recordingDAO = $recordingDAO;
   }
   
   // ** GET/SET Magic Methods **
   public function __get($property)
   {
      if (property_exists($this, $property)) {
         return $this->$property;
      }
   }
    
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   // ** GET/SET Magic Methods **  
   
   /**
    * Hide Recording = visible = 0;
    */
   public function hideRecording()
   {
      $this->visible = 0;
   }
   
   /**
    * Show Recording = visible = 1;
    */
   public function showRecording()
   {
      $this->visible = 1;
   }
   
   /**
    * Helper function to return a StdClass version of this object
    * to allow public access on all member variables.
    *
    * This is required by the $DB moodle object to do DB operations
    * with the object
    *
    * @return StdClass
    */
   public function getDBInsertObject()
   {
      return get_object_vars($this);
   }
   
   /**
    * Delete the recording from the server and the DB
    * 
    * @throws Elluminate_Exception
    */
   public function delete()
   {
      $this->deleteFromServer();
      $this->deleteDBRecording();
   }
   
   /**
    * 
    * @throws Elluminate_Exception
    */
   private function deleteFromServer(){
      $this->serverRecordingManager->deleteRecording($this->recordingid);
      $this->logger->debug("Delete Recording: " . $this->id . " from server: success");          
   }
   
   private function deleteDBRecording(){
      $this->recordingDAO->deleteRecording($this->id, $this->recordingid);
      $this->logger->debug("Delete Recording: " . $this->id . " from DB: success");
   }
   
   /**
    * Update the current recording in the database;
    */
   public function updateRecording(){
      $updateSuccess = false;
      $this->recordingDAO->updateRecording($this);
      $updateSuccess = true;
      return $updateSuccess;
   }
   
   /**
    * Convert the current recording
    * 
    * @param String $format
    * Returns the record for the updated recordingFile 
    */
   public function convertRecording($format){
      $recordingFileToConvert = $this->getRecordingFileByFormat($format);
      
      //If recording file is invalid, return immediately.
      if (! $this->checkFileForConversionEligibility($recordingFileToConvert)){
         return $recordingFileToConvert;
      }
      
      $this->logger->debug("Conversion Request for Recording [" . $this->id .
               "], format [" . $format . "]" .
               ", file id [" . $recordingFileToConvert->id . "]");
      
      $updatedRecordingFile = $this->executeConvertWebServiceCall($format);
      
      if ($updatedRecordingFile != null){
         $recordingFileToConvert->status = $updatedRecordingFile->status;
         $recordingFileToConvert->errorcode = $updatedRecordingFile->errorcode;
         $recordingFileToConvert->errortext = $updatedRecordingFile->errortext;
      }else{
         $recordingFileToConvert->errortext = get_string('user_error_soaperror','elluminate');
      }
               
      $recordingFileToConvert->save();
      return $recordingFileToConvert;
   }
   
   private function checkFileForConversionEligibility($recordingFile){
      //Make sure recording file is valid for conversion request
      if ($recordingFile == self::NO_RECORDING_FILE){
         $this->logger->error("convertRecording request made for invalid file format for recording [" .
                  $this->id . "]");
         return false;
         
      }
      
      //If recording file is in any other status than NOT_AVAILABLE, don't continue with recording
      if ($recordingFile->status != Elluminate_Recordings_Constants::NOT_AVAILABLE_STATUS){
         $this->logger->error("Incorrect status for convertRecording request. ID [" . $this->id . "] " .
                  "format [" . $recordingFile->format . "] " .
                  "status [" . $recordingFile->status . "]");
         return false;
      }
      
      return true;
   }
   
   /**
    * USED BY COLLABORATE BLOCK
    * 
    * Build a StdClass object with recording details
    * 
    * @param unknown_type $meeting
    * @param unknown_type $recording
    * @return stdClass
    */
   public function createRecordingEntry($meeting, $recording) {
	   $entry = new stdClass;
	   $entry->meetingid = $meeting->meetingid;
	   $entry->name = $meeting->name;
	   $entry->recordingid = $recording->id;
	   $entry->created = $recording->created;
	   return $entry;
   }
   
   public function getRecordingDurationMinutes(){
      $durationSeconds = $this->enddate - $this->startdate;
      
      return gmdate("H:i:s", $durationSeconds);
   }
   
   public function isEligibleForConversion(){
      $minVersion = new Elluminate_Config_Version();
      $minVersion->processWholeVersion(self::MIN_MP4_VERSION);
      return $this->version->isGreaterThanOrEqualTo($minVersion);
   }
   
   /**
    * The recordingFile array should be keyed by format, but a particular format may not exist.
    * 
    * This method will attempt to retrieve a file format, catch any errors (return null in case
    * of errors)
    */
   public function getRecordingFileByFormat($format){
      $recordingFile = self::NO_RECORDING_FILE;
      try{
         $recordingFile = $this->recordingFiles[$format];
      }catch(Exception $e){
         $this->logger->error("getRecordingFileByFormat: Recording ID [" . $this->id . 
                  "] does not have a child file with format [" . $format . "]");
      }
      return $recordingFile;
   }
   
   private function executeConvertWebServiceCall($format){
      $updatedRecordingFile = null;
      try{
         $updatedRecordingFile = $this->serverRecordingManager->convertRecording($this->recordingid,$format);
      }catch(Elluminate_Exception $e){
         $this->logger->error("Recording Conversion Error:" . $e->getExceptionOutput());
      }
      return $updatedRecordingFile;
   }
}