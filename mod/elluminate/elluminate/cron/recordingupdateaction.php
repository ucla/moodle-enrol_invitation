<?php
/**
 * This class is responsible for running the cron task to update existing recording file status and
 * update the Moodle Application Accordingly.
 *
 * @author dwieser
 *
 */
class Elluminate_Cron_RecordingUpdateAction implements Elluminate_Cron_Action{
   const NO_RECORDINGS = 0;
   const CRON_NAME = "Elluminate_Cron_RecordingUpdateAction";
   
   private $logger;
   
   private $statusUpdater;
   private $licenseManager;
   
   private $recordsUpdated = 0;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Recordings_UpdateCron");
   }
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   public function executeFirstCronAction($memoryLimit){
      //nothing to do here - on first run the add action will take care of all adds and updates
   }
   
   public function executeCronAction($lastRunTime,$memoryLimit){
      $this->logger->info("Elluminate_Cron_RecordingUpdateAction executeCronAction start");
      
      if (! $this->licenseManager->isConversionLicensed()){
         $this->logger->debug("Exiting: Recording Conversion is not licensed");
         return self::NO_RECORDINGS;
      }
      
      $recordingIds = $this->statusUpdater->getEligibleFilesForUpdate();
      
      if (sizeof($recordingIds) == self::NO_RECORDINGS ){
         $this->logger->debug("updateRecordingFileStatus exiting: All recordings are up to date.");
         return self::NO_RECORDINGS;
      }
      
      $this->logger->info("Elluminate_Cron_RecordingUpdateAction executeCronAction complete");
      $this->recordsUpdated = $this->statusUpdater->doRecordingFileStatusUpdate($recordingIds);
   }
   
   public function getResultString(){
      $resultString = "<br/>" . self::CRON_NAME .
         " complete:  Updated [" . $this->recordsUpdated  . "] recordings.";
      
      return $resultString;
   }
}