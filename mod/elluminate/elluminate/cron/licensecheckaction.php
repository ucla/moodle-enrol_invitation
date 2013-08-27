<?php

/**
 * This class is responsible for running the cron task to run a nightly check of licensing
 * 
 * @author dwieser
 *
 */
class Elluminate_Cron_LicenseCheckAction implements Elluminate_Cron_Action{
   const CRON_NAME ="Elluminate_Cron_LicenseCheckAction";
   //24 hours check time
   const CHECK_TIME = 86400;
   
   const LAST_RUN_KEY = "elluminate_last_license_check";
   
   const FIRST_LICENSE_CHECK = 1;
   
   private $logger;
   
   private $schedulingManager;
   private $licenseManager;
   private $moodleDAO;
   private $runTime;
   private $lastLicenseCheckRecord;
   
   private $licenseCount = 0;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Cron_LicenseCheckAction");
   }   
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   public function executeFirstCronAction($memoryLimit = null){
      $this->logger->info("Elluminate_Cron_LicenseCheckAction executeFirstCronAction start");
      $this->runTime = time();
      
      //We get this value but ignore it since this is our first cron run
      $this->getLastLicenseCheck();
      $this->deleteLicenses();
      $this->updateLicenses();
      $this->updateLastLicenseCheck();
      $this->logger->info("Elluminate_Cron_LicenseCheckAction executeFirstCronAction complete");
   }
   
   public function executeCronAction($lastCronRun,$memoryLimit){
      $this->logger->info("Elluminate_Cron_LicenseCheckAction executeCronAction start");
      $this->runTime = time();
      
      $lastLicenseCheck = $this->getLastLicenseCheck();
      $elapsedTime = $this->runTime - $lastLicenseCheck;
      
      if ($elapsedTime > self::CHECK_TIME){
         $this->deleteLicenses();
         $this->updateLicenses();
         $this->updateLastLicenseCheck();
      }else{
         $this->logger->info("Elluminate_Cron_LicenseCheckAction: check time not elapsed");
      }
      
      $this->logger->info("Elluminate_Cron_LicenseCheckAction executeCronAction complete");
   }
   
   public function getResultString(){
      $resultString = "<br/>" . self::CRON_NAME . " complete.  Processed [" . $this->licenseCount . "] licenses.";
      return $resultString;
   }
   
   private function updateLicenses(){
      $licenseArray = $this->executeWebServiceCall();
      if (is_array($licenseArray)){
         foreach($licenseArray as $license){
            $this->licenseManager->saveLicense($license);
            $this->licenseCount++;
         }
      }
   }
   
   private function executeWebServiceCall(){
      $licenseArray = null;
      $this->logger->debug("Elluminate_Cron_LicenseCheckAction executeWebServiceCall start");
      try{
         $licenseArray = $this->schedulingManager->getLicenses();
      }catch(Elluminate_Exception $e){
         $this->logger->error("Could not retrieve licensing information from server: ",$e->getExceptionOutput());
      }
      $this->logger->debug("Elluminate_Cron_LicenseCheckAction executeWebServiceCall complete");
      return $licenseArray;
   }
   
   private function getLastLicenseCheck(){
      $lastLicenseCheckRecord = $this->moodleDAO->getConfigRecord(self::LAST_RUN_KEY);
      if ($lastLicenseCheckRecord != null){
         $lastLicenseCheckTime = $lastLicenseCheckRecord->value;
      } else {
         $lastLicenseCheckTime = self::FIRST_LICENSE_CHECK;
      }
      $this->lastLicenseCheckRecord = $lastLicenseCheckRecord;
      $this->logger->debug("Last License Check [" . $lastLicenseCheckTime . "]");
      return $lastLicenseCheckTime;
   }
   
   private function updateLastLicenseCheck(){
      if ($this->lastLicenseCheckRecord == null){
         $this->addNewCronRecord();
      }else{
         $this->logger->debug("Updating elluminate_last_license_check with value " . $this->runTime);
         $this->lastLicenseCheckRecord->value = $this->runTime;
         $this->moodleDAO->updateConfigRecord($this->lastLicenseCheckRecord);
      }
   }
   
   private function addNewCronRecord(){
      $this->logger->debug("Adding elluminate_last_license_check with value " . $this->runTime);
      $cronRecord = new stdClass;
      $cronRecord->name = self::LAST_RUN_KEY;
      $cronRecord->value = $this->runTime;
      $this->moodleDAO->addConfigRecord($cronRecord);
   }
   
   private function deleteLicenses() {
   	  $this->logger->debug("Deleting all licenses");
   	  $this->licenseManager->deleteAllLicenses();
   }
}