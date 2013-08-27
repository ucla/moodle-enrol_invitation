<?php
class Elluminate_Cron_Runner{
   const CRON_FIRST_RUN = 1;
   const PHP_MEMORY_LIMIT = "memory_limit";
   const CRON_LAST_RUN = 'elluminate_last_cron_run';
   const CONTAINER_ACTION_LIST = "cronActionList";
   
   private $logger;
   private $runTime;
   private $moodleDAO;
   private $lastRunTime;
   private $moduleConfig;
   
   private $cronActionList;
   
   private $cronActionObjects;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Cron_Runner");
      $this->runTime = time();
   }
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   //this method is used for testing with mock action objects
   public function setCronActionObjects($actionObjects){
      $this->cronActionObjects = $actionObjects;
   }
   
   public function executeCronActions(){ 
      $resultString = "<br/>===<b> Executing Elluminate Cron Actions</b> === ";
      $this->logger->info("=========== Elluminate_Cron_Runner start ===========");
      if (!$this->checkValidElluminateSettings()){
         return;
      }
      $memoryLimit = $this->memoryInitialization();
      $cronRecord = $this->getLastCronRun();
      
      //Execute
      foreach($this->cronActionObjects as $actionObject){
         if ($this->lastRunTime == self::CRON_FIRST_RUN){
            $actionObject->executeFirstCronAction($memoryLimit);
         }else{
            $actionObject->executeCronAction($this->lastRunTime,$memoryLimit);
         }
         
         $resultString .= $actionObject->getResultString();
      }
            
      $resultString .= "<br/>===<b> Completed Elluminate Cron Actions</b> === ";
      $this->updateLastCronRun($cronRecord);
      $this->logger->info("=========== Elluminate_Cron_Runner: Complete - Memory Usage = " . 
               get_real_size(memory_get_usage()) . " ===========");
      return $resultString;
   }
   
   public function loadActions(){
      global $ELLUMINATE_CONTAINER;
      $this->initializeActions();
      
      $this->cronActionObjects = array();
      foreach($this->cronActionList as $cronAction){
         try{
            $action = $ELLUMINATE_CONTAINER[$cronAction];
            $this->cronActionObjects[] = $action; 
         }catch(Exception $e){
            $this->logger->debug("Could not instantiate action for: " . $cronAction);
         }
      }
   }
   
   /*
    * Based on the current scheduler, determine which cron actions need to be run
    * as defined in the Container.
    * 
    */
   private function initializeActions(){
      global $ELLUMINATE_CONTAINER;
      $scheduler = Elluminate_Config_Settings::getElluminateSetting('elluminate_scheduler');
      $this->logger->debug("Loading Cron Actions for Scheduler [" . $scheduler . "]");
      $this->cronActionList = $ELLUMINATE_CONTAINER[$scheduler . self::CONTAINER_ACTION_LIST];
   }
   
   private function checkValidElluminateSettings(){
      $settingsValid = true;
      if (! $this->moduleConfig->areCollaborateSettingsValid()){
         $this->logger->error('Invalid Collaborate Server Settings - cannot run cron job');
         $settingsValid = false;
      }
      return $settingsValid;
   }
   
   /**
    * Raise the memory limit for the time the cron runs.
    * We don't want to set unlimited memory here, because that could lead to server crashes
    * Note that this setting is only temporary, for this "page load"
    * 
    * @return unknown
    */
   private function memoryInitialization(){
      raise_memory_limit(MEMORY_EXTRA);
      $memoryLimit = get_real_size(ini_get(self::PHP_MEMORY_LIMIT));
      $this->logger->info("Elluminate_Cron_Runner: Memory Limit = " . get_real_size($memoryLimit));
      $this->logger->info("Elluminate_Cron_Runner: Memory Usage = " . get_real_size(memory_get_usage()));
      return $memoryLimit;
   }
   
   /**
    * Determine the last time the cron was run
    * @return unknown
    */
   private function getLastCronRun(){
      $cronRecord = $this->moodleDAO->getConfigRecord(self::CRON_LAST_RUN);
      if ($cronRecord != null){
         $this->lastRunTime = $cronRecord->value;
      } else {
         $this->lastRunTime = self::CRON_FIRST_RUN;
      }
      return $cronRecord;
   }
   
   private function updateLastCronRun($cronRecord){
      if ($cronRecord == null){
         $this->addNewCronRecord();
      }else{   
         $this->logger->debug("Updating elluminate_last_cron_run with value " . $this->runTime);
         $cronRecord->value = $this->runTime;
         $this->moodleDAO->updateConfigRecord($cronRecord);
      }
   }
   
   private function addNewCronRecord(){
      $this->logger->debug("Adding elluminate_last_cron_run with value " . $this->runTime);
      $cronRecord = new stdClass;
      $cronRecord->name = self::CRON_LAST_RUN;
      $cronRecord->value = $this->runTime;
      $this->moodleDAO->addConfigRecord($cronRecord);
   }
}