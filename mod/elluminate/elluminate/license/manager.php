<?php

class Elluminate_License_Manager{
   private $licenseDAO;
   
   private $logger;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_License_Manager");
   }
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   public function isConversionLicensed(){
      $licensed = false;
      $conversionLicense = $this->licenseDAO->loadLicense(Elluminate_License_Constants::RECORDING_CONVERSION);
      if ($conversionLicense != null ){
         $licensed = $conversionLicense->licensed;
      }
      return $licensed;
   }
   
   public function isTelephonyLicensed(){
      $licensed = false;
      $telephonyLicense = $this->licenseDAO->loadLicense(Elluminate_License_Constants::TELEPHONY,
               Elluminate_License_Constants::TELEPHONY_INTEGRATED);
      if ($telephonyLicense != null ){
         $licensed = $telephonyLicense->licensed;
      }
      return $licensed;
   }
   
   public function saveLicense($license){
      $this->logger->debug("Saving " . $license->__toString());
      $updated = time();
      $existing = $this->checkExisting($license);
      if ($existing != null){
         $existing->license = $license;
         $existing->updated = $updated;
         $this->licenseDAO->update($existing);
      }else{
         $license->updated = $updated;
         $this->licenseDAO->add($license);
      }
   }
   
   public function getAllLicenses(){
      $this->logger->debug("getAllLicenses");
      $licenseList = array();
      $dbLicenseArray = $this->licenseDAO->getAllLicenses();
      if ($dbLicenseArray != null){
         foreach($dbLicenseArray as $dbLicense){
            $licenseList[] = $this->loadFromDatabase($dbLicense);
         }
      }
      return $licenseList;
   }
  
   private function checkExisting($license){
      $dbLicense = $this->licenseDAO->loadLicense($license->optionname, $license->variationname);
      if ($dbLicense){
         return $this->loadFromDatabase($dbLicense);
      }else{
         return null;
      }
   }
   
   private function loadFromDatabase($dbLicense){
      $licenseEntry = new Elluminate_License_Entry();
      $licenseEntry->id = $dbLicense->id;
      $licenseEntry->optionname = $dbLicense->optionname;
      $licenseEntry->variationname = $dbLicense->variationname;
      $licenseEntry->licensed = $dbLicense->licensed;
      $licenseEntry->updated = $dbLicense->updated;
      return $licenseEntry;
   }
   
   public function deleteAllLicenses() {
   	  return $this->licenseDAO->deleteAllLicenses();
   }
}