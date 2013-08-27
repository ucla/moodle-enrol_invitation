<?php
class Elluminate_Config_Version{
   const VERSION_SEPARATOR = ".";
   const MAX_PARTS = 4;
   const DEFAULT_VERSION = 0;
    
   //Default will be ver 0.0.0
   private $versionmajor = self::DEFAULT_VERSION;
   private $versionminor = self::DEFAULT_VERSION;
   private $versionpatch = self::DEFAULT_VERSION;
    
   private $logger;
    
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
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Config_Version");
   }
   
   public function processWholeVersion($wholeVersion){
      //Strip out non-numeric characters that are occasionally passed back in testing environments
      $wholeVersion = preg_replace("/[^0-9.]/", "", $wholeVersion);
      $this->splitVersion($wholeVersion);
   }
   
   public function processSplitVersion($major,$minor, $patch){
      $this->versionmajor = $major;
      $this->versionminor = $minor;
      $this->versionpatch = $patch;
   }
   
   public function isGreaterThanOrEqualTo($compareVersion){
      $greaterThanOrEqualTo = false;
      if ($this->versionmajor >= $compareVersion->versionmajor ){
         if ($this->versionminor >= $compareVersion->versionminor ){
            if ($this->versionpatch >= $compareVersion->versionpatch ){
               $greaterThanOrEqualTo = true;
            }
         }
      }
      return $greaterThanOrEqualTo;
   }
      
      
   
   private function splitVersion($version){
      if ($version == ''){
         $this->logger->error("Invalid Version: Blank");
         return;
      }
      $versionParts = explode(self::VERSION_SEPARATOR,$version);
      $numberOfParts = sizeof($versionParts);

      if ($numberOfParts == 0 || $numberOfParts > self::MAX_PARTS) {
         $this->logger->error("Invalid Recording Version Number: " . $version);
         return;
      }

      $this->versionmajor = $versionParts[0];
       
      if ($numberOfParts > 1) {
         $this->versionminor = $versionParts[1];
      }

      if ($numberOfParts > 2) {
         $this->versionpatch = $versionParts[2];
      } 
   }
}