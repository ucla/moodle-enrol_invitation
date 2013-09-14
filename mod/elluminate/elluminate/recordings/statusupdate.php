<?php
class Elluminate_Recordings_StatusUpdate{

   //short update interval - for manual update requests
   //we only allow checks every 2 minutes
   const SHORT_UPDATE = 120;
    
   //regular update interval - for conversions in progress or not available
   //10 minutes
   const REGULAR_UPDATE = 600;
    
   //long update interval - for conversions available or with errors
   //60 minutes
   const LONG_UPDATE = 3600;
    
   const CRON_MODE = "cron";
   const MANUAL_MODE ="manual";
    
   const NO_RECORDINGS_PROCESSED = 0;
   
   const ALL_FORMATS = null;
    
   private $logger;
   
   private $recordingLoader;
   private $recordingDAO;
   private $schedulingManager;
   private $cacheManager;

   private $recordingIdUpdateList;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Recordings_StatusUpdate");
   }
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
    
   /**
    * This function will accept a list of SAS recording IDs and perform a getRecordingPlaybackDetails
    * Web Service Call in batch mode for that list of IDs.
    *
    * Once the results have been returned, any updates are saved into the recording_files table
    *
    * @param Array $recordingIdList - list of SAS recording IDs to check status for
    * @return string
    */
   public function doRecordingFileStatusUpdate($recordingIdList){
      $this->logger->debug("doRecordingFileStatusUpdate start");
      if ($recordingIdList == null || sizeof($recordingIdList) == 0){
         $this->logger->error("doRecordingFileStatusUpdate: Invalid Recording ID List: null or 0 elements");
         return self::NO_RECORDINGS_PROCESSED;
      }

      $recordingFiles = $this->executeWebServiceCall($recordingIdList);
      $this->processUpdates($recordingFiles);
      
      $resultSize = sizeof($recordingIdList);
      $this->logger->debug("doRecordingFileStatusUpdate complete updated " . $resultSize . " recordings");
      return $resultSize;
   }
   
   /**
    * This function will do an update for a single file format.
    * 
    * The main reason for this is the requirement for VCR format to utilize
    * a different API call to retrieve URL.  If we only need a status update 
    * for MP3/MP4, there should not be a call to SAS.
    * 
    * @param unknown_type $recordingid
    * @param unknown_type $format
    */
   public function doSingleFileRecordingFileStatusUpdate($recordingid,$format){
      $recordingFiles = $this->executeWebServiceCall(array($recordingid));
      $this->processUpdates($recordingFiles,$format);
   }
    
   /**
    * This function can be used to retrieve a list of recordings eligible for
    * update.  The results of this list can be passed to 
    *   doRecordingFileStatusUpdate
    * to execute the actual update.  
    * 
    * Two modes are available
    * CRON: Time Interval for checks will be minimum 10 minutes
    * MANUAL: Time Interval for checks will be minimum 2 minutes
    *  
    * @param String $updateMode - "cron" or "manual"
    * 
    * Returns:  Array of Recording IDs for update
    */
   public function getEligibleFilesForUpdate($updateMode = self::CRON_MODE){
      if ($updateMode == self::MANUAL_MODE){
         $timeInterval = self::SHORT_UPDATE;
      }else{
         $timeInterval = self::REGULAR_UPDATE;
      }
      
      //Start time for check
      $runTime = time();
      $this->recordingIdUpdateList = array();
      
      //1. In Progress Query
      $this->getInProgressIds($runTime, $timeInterval);
 
      //2. Available/Not Applicable Query
      $this->getAvailableAndNotApplicableIds($runTime);

      $this->logger->debug("Retrieved Recording File Update List. Files to Check [" . 
               sizeof($this->recordingIdUpdateList) . "]");
      
      return $this->recordingIdUpdateList;
   }
    
   /**
    * Get list of recording files eligible for a status update
    * 
    * In Progress - use short or regular update time, depending on mode
    * 
    * NOTE: status not_available is never included in a check query.  There
    * is no point in processing those results if a conversion request has not been
    * made.
    * 
    * @param $runTime
    * @param $timeInterval
    */
   private function getInProgressIds($runTime, $timeInterval){
      $regularIntervalStatusList = array(Elluminate_Recordings_Constants::IN_PROGRESS_STATUS);
      $regularIntervalCheckTime = $runTime - $timeInterval;

      $this->getRecordingIdsForUpdate($regularIntervalStatusList, $regularIntervalCheckTime);
   }
    
   /**
    * Get list of recording files eligible for a status update
    * Not Applicable or Available - use LONG update time
    * @param array $recordingIdList
    * @param String $runTime - start time for query
    */
   private function getAvailableAndNotApplicableIds($runTime){
      $longIntervalStatusList = array(Elluminate_Recordings_Constants::AVAILABLE_STATUS,
               Elluminate_Recordings_Constants::NOT_APPLICABLE_STATUS);
      $longIntervalCheckTime = $runTime - self::LONG_UPDATE;
       
      $this->getRecordingIdsForUpdate($longIntervalStatusList, $longIntervalCheckTime);     
   }
    
   private function executeWebServiceCall($recordingIdList){
      $this->logger->debug("executeWebServiceCall start");
      $recordingFiles = $this->schedulingManager->getRecordingPlaybackDetails($recordingIdList);
      $this->logger->debug("executeWebServiceCall complete");
      return $recordingFiles;
   }
    
   private function processUpdates($recordingFiles,$format = self::ALL_FORMATS){
      $this->logger->debug("processUpdates: processing [" . sizeof($recordingFiles) . "] files");
      foreach ($recordingFiles as $recordingFile){
         //see BBEN-725
         if (! $this->isValidFormat($recordingFile->format)){
            $this->logger->error("Skipping Recording File[" . $recordingFile->recordingid . "] - Bad Format");
            continue;
         }
         //Skip updates for formats that have not been explicitly requested
         if ($format != self::ALL_FORMATS &&
                   $recordingFile->format != $format){
            continue;
         }else{
            $this->updateRecordingFile($recordingFile);
         }
      }
   }
   
   private function updateRecordingFile($updatedRecordingFile){
      //Load Existing Recording From DB
      $existingRecordingFile = $this->recordingLoader->getRecordingFileByIdAndFormat($updatedRecordingFile->recordingid,
               $updatedRecordingFile->format);
       
      if ($existingRecordingFile){
         $this->updateExistingRecordingFile($existingRecordingFile, $updatedRecordingFile);
         $existingRecordingFile->save();
         $this->logger->debug("Update for Recording File ID [" . $existingRecordingFile->id . "]," .
                  " format [" . $existingRecordingFile->format . "]");
         $updatedRecordingFile = $existingRecordingFile;
      }else{
         $updatedRecordingFile->save();
         $this->logger->debug("Add Recording File record for Recording ID [" . $updatedRecordingFile->recordingid . "]," .
                  "format [" . $updatedRecordingFile->format . "]");
      }
      $this->updateURLCache($updatedRecordingFile);     
   }
   
   /**
    * 
    * @param unknown_type $updatedRecordingFile
    */
   private function updateURLCache($recordingFile){
      if ($recordingFile->url != ''){
         $this->cacheManager->addCacheContent(Elluminate_Cache_Constants::RECORDING_URL_CACHE,
                  $recordingFile->format,
                  $recordingFile->recordingid,
                  $recordingFile->url);
      }
   }
    
   private function updateExistingRecordingFile($existingfile, $updatedfile){
      $existingfile->status = $updatedfile->status;
      $existingfile->errorcode = $updatedfile->errorcode;
      $existingfile->errortext = $updatedfile->errortext;
      $existingfile->url = $updatedfile->url;
   }
    
   /**
    * Make a DAO call to get all recordings with the given statuses that have a last update time
    * prior to the provided time.
    *
    * Add the results to the parent list of recordings to be updated.
    *
    * @param unknown_type $statusList
    * @param unknown_type $updateTime
    */
   private function getRecordingIdsForUpdate($statusList, $lastUpdateTime){
      $rawList = $this->recordingDAO->getRecordingFileIdsByStatusAndTime($statusList,$lastUpdateTime);
      $this->getFilteredRecordingIdList($rawList);
   }

   /**
    * Process the raw list of recording files to come up with only the unique recording ids
    *
    * array is a set of stdclass objects with values:
    *    -id
    *    -recordingid
    *
    * @param unknown_type $rawList
    */
   private function getFilteredRecordingIdList($rawList){
      //No DB Results, exit method
      if ($rawList == null){
         return;
      }
      
      foreach ($rawList as $recording){
         if (! in_array($recording->recordingid,$this->recordingIdUpdateList)){
            $this->recordingIdUpdateList[] = $recording->recordingid;
         }
      }
   }
   
   private function isValidFormat($format){
      if ($format == Elluminate_Recordings_Constants::MP3_FORMAT || 
               $format == Elluminate_Recordings_Constants::MP4_FORMAT ||
               $format == Elluminate_Recordings_Constants::VCR_FORMAT){
         return true;   
      }
      return false;
   }
}