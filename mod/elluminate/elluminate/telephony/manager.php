<?php
class Elluminate_Telephony_Manager{
  
   const CACHE_NO_SUBTYPE = null;
   private $schedulingManager;
   private $cacheManager;
   private $licenseManager;
    
   private $logger;
    
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Telephony_Manager");
   }
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   public function toggleSessionTelephony($sessionid,$status){
      //Do nothing if we're not licensed
      if ( ! $this->licenseManager->isTelephonyLicensed()){
         return;
      }
      
      //It's possible that there be issues with the setTelephony call, but with the way this is currently
      //setup in SAS (being separate from setSession), even though setTelephony fails, the session is still
      //created.  To avoid causing orphaned sessions on SAS, we'll trap setTelephony errors here and write
      //a log.  
      //
      //The outcome of an error here would be that the session would have the SAS default of telephony ON
      //even if it's turned off in the moodle module.
      try{
         $telephonyInstances = $this->schedulingManager->setTelephony($sessionid,$status);
      }catch(Elluminate_Exception $e){
         return;
      }
      
      //Turning Off
      if ($status == false){
         $this->cacheManager->clearContentCacheItem(Elluminate_Cache_Constants::TELEPHONY_CACHE, $sessionid);
      }
      
      //Turning On
      if ($status == true){
         $this->cacheManager->addCacheContent(Elluminate_Cache_Constants::TELEPHONY_CACHE, 
                  self::CACHE_NO_SUBTYPE,
                  $sessionid, 
                  $telephonyInstances);
      }
   }
   
   /**
    * When a session is deleted, all we need to do is remove the telephony information from
    * the DB, no need to make a call to disable on SAS.
    * @param unknown_type $meetingid
    */
   public function deleteSessionTelephony($sessionid){
      $this->cacheManager->clearContentCacheItem(Elluminate_Cache_Constants::TELEPHONY_CACHE, $sessionid);
   }
}