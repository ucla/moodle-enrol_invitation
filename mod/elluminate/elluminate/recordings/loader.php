<?php

class Elluminate_Recordings_Loader{
   private $recordingDAO;
   private $recordingFactory;
   
   private $logger;

   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Recordings_Loader");
   }
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }

   public function getRecordingById($id){
      $dbObject = $this->recordingDAO->loadRecording($id);
      $recording = $this->loadRecordingFromDB($dbObject);
      return $recording;
   }
   
   public function getRecordingByRecordingId($recordingid){
      $dbObject = $this->recordingDAO->loadRecordingByRecordingId($recordingid);
      $recording = $this->loadRecordingFromDB($dbObject);
      return $recording;
   }

   public function getRecordingsForMeetingId($meetingid){
      $recordingList = $this->recordingDAO->getRecordingList($meetingid);
      $returnList = null;
      
      if (sizeof($recordingList) > 0){
         $returnList = array();
         foreach ($recordingList as $dbrecording){
            $curRecording = $this->recordingFactory->getRecording();
            $this->populateFromDBObject($curRecording,$dbrecording);
            $curRecording->recordingFiles = $this->retrieveRecordingFiles($curRecording->recordingid);
            $returnList[] = $curRecording;
            $this->logger->debug("getRecordingsForMeetingId: Loaded Recording ID [" . $curRecording->id . "]");
         }
      }

      return $returnList;
   }
   
   public function getRecordingFileByIdAndFormat($recordingid,$format){
      $recordingFile = $this->loadRecordingFileFromDB($recordingid,$format);
      return $recordingFile;
   }

   private function loadRecordingFromDB($dbObject){
      $recording = null;      
      if ($dbObject){
         $recording = $this->recordingFactory->getRecording();
         $this->populateFromDBObject($recording,$dbObject);
         $recording->recordingFiles = $this->retrieveRecordingFiles($recording->recordingid);
         $this->logger->debug("loadRecordingFromDB: Loaded Recording ID [" . $recording->recordingid . "]");
      }
      return $recording;
   }

   private function retrieveRecordingFiles($recordingid){
      $recordingFiles = $this->recordingDAO->getRecordingFiles($recordingid);
      $recordingFileList = array();
      if (sizeof($recordingFiles) > 0){
         $recordingFileList = $this->processRecordingFiles($recordingFiles);
      }else{
         $this->logger->debug("No recording files for recording id [" . $recordingid . "]");
      }
      return $recordingFileList;
   }
   
   private function loadRecordingFileFromDB($recordingid, $format){
      $recordingFile = null;
      $dbObject = $this->recordingDAO->loadRecordingFileByIDFormat($recordingid,$format);
      if ($dbObject){
         $recordingFile = $this->recordingFactory->getRecordingFile();
         $this->populateRecordingFileFromDB($recordingFile,$dbObject);
      }
      return $recordingFile;
   }

   private function processRecordingFiles($recordingFiles){
      $recordingFileList = array();
      foreach ($recordingFiles as $fileDBObject){
         $file = $this->recordingFactory->getRecordingFile();
         $this->populateRecordingFileFromDB($file, $fileDBObject);
         $recordingFileList[$file->format] = $file;
         $this->logger->debug("Loaded Recording File [" . $file->id . "] " .
                  "with format [" . $file->format . "] " .
                  "for recording [" . $file->recordingid . "]");
      }
      return $recordingFileList;
   }

   /**
    * Populate the recording object based on a StdClass object loaded from DB
    * @param unknown_type $dbRecord
    */
   private function populateFromDBObject($recording,$dbRecord)
   {
      $recording->id = $dbRecord->id;
      $recording->meetingid = $dbRecord->meetingid;
      $recording->recordingid = $dbRecord->recordingid;
      $recording->recordingsize = $dbRecord->recordingsize;
      $recording->versionmajor = $dbRecord->versionmajor;
      $recording->versionminor = $dbRecord->versionminor;
      $recording->versionpatch = $dbRecord->versionpatch;
      $recording->startdate = $dbRecord->startdate;
      $recording->enddate = $dbRecord->enddate;
      $recording->roomname = $dbRecord->roomname;
      $recording->description = $dbRecord->description;
      $recording->visible = $dbRecord->visible;
      $recording->groupvisible = $dbRecord->groupvisible;
      $recording->created = $dbRecord->created;
      $recording->securesignon = $dbRecord->securesignon;
      
      $recording->versionmajor = $dbRecord->versionmajor;
      $recording->versionminor = $dbRecord->versionminor;
      $recording->versionpatch = $dbRecord->versionpatch;
      
      $version = new Elluminate_Config_Version();
      $version->processSplitVersion($dbRecord->versionmajor,$dbRecord->versionminor,$dbRecord->versionpatch);
      $recording->version = $version;

      return $recording;
   }

   private function populateRecordingFileFromDB($recordingFile, $dbRecord){
      $recordingFile->id = $dbRecord->id;
      $recordingFile->recordingid = $dbRecord->recordingid;
      $recordingFile->format = $dbRecord->format;
      $recordingFile->status = $dbRecord->status;
      $recordingFile->errorcode = $dbRecord->errorcode;
      $recordingFile->errortext = $dbRecord->errortext;
      $recordingFile->updated = $dbRecord->updated;
   }
}