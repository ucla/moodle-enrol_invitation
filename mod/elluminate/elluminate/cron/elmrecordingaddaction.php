<?php

/**
 * This class is responsible for running the cron task to pull back NEW recordings and 
 * update the Moodle Application Accordingly.
 * 
 * @author dwieser
 *
 */
class Elluminate_Cron_ELMRecordingAddAction implements Elluminate_Cron_Action{
   
   const CRON_NAME = "Elluminate_Cron_ELMRecordingAddAction";
   
   const CRON_FIRST_RUN_START = '1072933200';  //Jan 1 2004
   const YEAR_IN_SECONDS = 31536000;
   
   private $logger;
   
   private $recordingDAO;
   private $schedulingManager;
   private $sessionLoader;
   private $statusUpdater;
   private $cacheManager;
   private $recordingFactory;
   
   private $savedRecordingList;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Recordings_AddCron");
   }
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   /**
    * This function is invoked if the collaborate cron job to pullback recordings
    * has never been run.  It will attempt to pullback all recordings from an
    * arbitrary start date of Jan 1, 2004.  SAS will only accept date ranges of 1
    * year, so this initial pullback needs to be done in segments of a year at a time.
    */
   public function executeFirstCronAction($memoryLimit){
      $this->logger->info("Elluminate_Cron_RecordingAddAction executeFirstCronAction start");
      $timenow = time();
      
      $starttime = $this::CRON_FIRST_RUN_START;
      $resultSize = 0;
      
      while($starttime < $timenow) {
         if (Elluminate_Cron_Utils::memoryUsageExceeded($memoryLimit)){
            break;
         }
         $endtime = $starttime + self::YEAR_IN_SECONDS;
         if($endtime > $timenow) {
            $endtime = $timenow;
         }
         $this->logger->info("executeFirstCronAction: retrieve for start: " . $starttime . " end: " . $endtime);
         $recordingList = $this->executeServiceCall($starttime, $endtime);    
         if ($recordingList){
            $this->processRecordingList($recordingList);
         }else{
            $this->logger->info("executeFirstCronAction: no recordings");
         }
         $starttime = $endtime;
      }
      $this->logger->info("Elluminate_Cron_RecordingAddAction complete");
   }
   
   /**
    * Regular Cron Run - retrieve recordings since last run time
    * 
    * @param unknown_type $lastTime
    */
   public function executeCronAction($lastTime,$memoryLimit){
      $this->logger->debug("Elluminate_Cron_RecordingAddAction start");
      $timeNow = time();
      $this->logger->info("getRecordingSinceTime: startTime = " . $lastTime . " end time = " . $timeNow );
      $recordingList = $this->executeServiceCall($lastTime, $timeNow);
      $resultSize = 0;
      
      //If the pullback from the web service has brought us too close to the memory limit
      //don't process the records further
      if (Elluminate_Cron_Utils::memoryUsageExceeded($memoryLimit)){
         return;
      }
      if ($recordingList){
         $this->processRecordingList($recordingList);
      }else{
         $this->logger->info("getRecordingSinceTime: no recordings located");
      }
      $this->logger->debug("Elluminate_Cron_RecordingAddAction complete");
   }
   
   public function getResultString(){
      $resultString = "<br/>" . self::CRON_NAME .
         " complete:  Added [" . sizeof($this->savedRecordingList)  . "] recordings.";
      
      return $resultString;
   }
   
   private function processRecordingList($recordingList){
      $this->logger->info("processRecordingList: processing " . sizeof($recordingList) . " recording(s)");
      $this->saveRecordingList($recordingList);
      $this->logger->info("processRecordingList: processing complete, saved " . sizeof($this->savedRecordingList) . " recording(s)");
   }   

   private function saveRecordingList($recordingList){
      foreach($recordingList as $recording){
         if ($this->doesParentSessionExist($recording)){
            $result = $this->recordingDAO->saveRecording($recording);
            //Keep track of recordings that have been saved
            if ($result){
              $this->savedRecordingList[] = $recording;
              $this->cacheManager->addCacheContent(Elluminate_Cache_Constants::RECORDING_URL_CACHE,
                                                    Elluminate_Recordings_Constants::VCR_FORMAT,
                                                    $recording->recordingid,
                                                    $recording->url);
               $recordingFile = $this->recordingFactory->getRecordingFile();
               $recordingFile->recordingid = $recording->recordingid;
               $recordingFile->format = Elluminate_Recordings_Constants::VCR_FORMAT;
               $recordingFile->status = Elluminate_Recordings_Constants::AVAILABLE_STATUS;
               $recordingFile->save();
            }
         }
      }
   }
   
   private function doesParentSessionExist($recording){
      $parentSession = $this->sessionLoader->getSessionByMeetingId($recording->meetingid);
      if ($parentSession !=null && $parentSession->id){
         return true;
      }else{
         $this->logger->debug("Ignoring Recording [" . $recording->recordingid . "]: no parent session");
         return false;
      }
   }
   
   /**
    * Helper method to wrap the service call and catch any errors that may occur
    * 
    * In this case, the only output from the error is a log message, as the
    * cron runs mainly in the background.
    * 
    * @param unknown_type $startTime
    * @param unknown_type $endTime
    * @return multitype:Elluminate_Recording
    */
   private function executeServiceCall($startTime, $endTime){
      return $recordingList = $this->schedulingManager->getRecordingsForTime($startTime, $endTime);
   }
}