<?php

class Elluminate_Logger_Logger {
   //Log Directories
   //{MOODLE_DATA_ROOT}/blackboard_collaborate/{MODULE_NAME}/logs
   private $dataDir;
   private $logDir;
   private $moduleDir;

   //Data used to build fileName
   private $logSuffix;
   private $logKey;
   private $className;
   private $siteName;
   private $fileName;

   //Variable Name used to retrieve log setting from global $CFG object
   private $configParamName;

   const BB_DATA_DIR = "blackboard_collaborate";
   const BB_LOG_DIR = "/logs/";
   const BB_LOG_EXT = ".log";
   const BB_LOG_SEPARATOR = ' : ';
   const BB_LOG_SUFFIX_SEPARATOR = "-";

   const DEBUG_LEVEL = 1;
   const DEBUG_LEVEL_DESC = 'debug';

   const INFO_LEVEL = 2;
   const INFO_LEVEL_DESC = 'info ';

   const WARN_LEVEL = 3;
   const WARN_LEVEL_DESC = 'warn ';

   const ERROR_LEVEL = 4;
   const ERROR_LEVEL_DESC = 'error';

   const DEFAULT_LOG_LEVEL = 4;
    
   public function init($logKey, $className, $dataRoot, $siteName, $configParamName){
      if ($dataRoot == null){
         error_log("ERROR: [ Logger.php ] Log Directory is undefined! ");
      }else{
         $this->logKey = $logKey;
         $this->dataDir = $dataRoot . "/" . self::BB_DATA_DIR;
         $this->moduleDir = $this->dataDir . "/" . $logKey;
         $this->logDir = $this->moduleDir . self::BB_LOG_DIR;
         $this->className = $className;
          
         //Suffix = - + key (-sas)
         $this->logSuffix = $logKey;
         $this->siteName = $siteName;
         $this->configParamName = $configParamName;
         $this->setFileName();
         $this->checkDirectories();
      }
   }
   
   public function getLogDir(){
      return $this->logDir;
   }

   public function debug($message){
      if ($this->getCurrentLogLevel() <= self::DEBUG_LEVEL){
         $this->log(self::DEBUG_LEVEL_DESC, $message);
      }
   }

   public function info($message){
      if ($this->getCurrentLogLevel() <= self::INFO_LEVEL){
         $this->log(self::INFO_LEVEL_DESC, $message);
      }
   }

   public function warn($message){
      if ($this->getCurrentLogLevel() <= self::WARN_LEVEL){
         $this->log(self::WARN_LEVEL_DESC, $message);
      }
   }

   public function error($message){
      if ($this->getCurrentLogLevel() <= self::ERROR_LEVEL){
         $this->log(self::ERROR_LEVEL_DESC, $message);
      }
   }

   private function log($level, $message){
      if ($this->logDir == null){
         error_log("ERROR: [ Logger.php ] Log Directory is undefined! ");
      }else{
         $this->writeToFile($level,$message);
      }
   }

   private function writeToFile($level, $message){
      $fh = @fopen($this->fileName, "a");
      if ($fh == null){
         error_log("ERROR : [ Logger.php ] Could not open file: " . $this->fileName);
      }else{
         @fwrite($fh,$this->getLogMessage($level,$message));
         @fclose($fh);
      }
   }

   private function getCurrentLogLevel(){
      global $CFG;
      if (!isset($CFG->{$this->configParamName})){
    	    return self::DEFAULT_LOG_LEVEL;
      }else{
         return $CFG->{$this->configParamName};
      }
   }

   private function getLogMessage($level, $message){
      return gmdate("Y-m-d H:i:s") . self::BB_LOG_SEPARATOR
      . strtoupper($level) . self::BB_LOG_SEPARATOR
      . "[" . $this->logKey . self::BB_LOG_SEPARATOR . $this->className . "]" . self::BB_LOG_SEPARATOR
      . $message . "\n";
   }

   // mysitename-20130306-sas.log
   private function setFileName(){
      $today_timestamp = gmdate("Ymd");
      $this->fileName = $this->logDir. "/"
            .str_replace(' ','_',$this->siteName) .
            self:: BB_LOG_SUFFIX_SEPARATOR .
            $today_timestamp .
            self::BB_LOG_SUFFIX_SEPARATOR .
            $this->logSuffix . self::BB_LOG_EXT;
   }

   private function checkDirectories(){
      global $CFG;
      @mkdir($this->dataDir, $CFG->directorypermissions);
      @mkdir($this->moduleDir, $CFG->directorypermissions);
      @mkdir($this->logDir, $CFG->directorypermissions);
   }
}