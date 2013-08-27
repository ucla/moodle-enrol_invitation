<?php

class Elluminate_Cache_Telephony_Content implements Elluminate_Cache_Content{
   const URI_SUFFIX = "_uri";
   const PIN_SUFFIX = "_pin";
    
   private $schedulingManager;

   private $logger;

   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Cache_URL_VCRRecording");
   }

   public function setSchedulingManager($schedulingManager){
      $this->schedulingManager = $schedulingManager;
   }

   /**
    * Item Key in this case is the Session ID for the telephony session
    *
    * @see Elluminate_Cache_Content::buildCacheContent()
    */
   public function buildCacheContent($itemKey){
      $this->logger->debug("buildCacheContent [" . $itemKey . "]");
      $telephonyInstances = $this->schedulingManager->getTelephony($itemKey);
      return $this->processTelephonyInstances($itemKey, $telephonyInstances);
   }

   /**
    * This is a special case where the manual add to the cache will actually
    * pass in an array of telephony objects via the itemValues parameter.
    * 
    * @see Elluminate_Cache_Content::manualCacheAdd()
    */
   public function manualCacheAdd($subType, $itemKey, $itemValues){
      return $this->processTelephonyInstances($itemKey, $itemValues);
   }
    
   /**
    * There will be 2 cache entries for each telephony item - URI (#) and PIN
    * 
    * Items typically exist for moderator and participant
    * 
    * @param unknown_type $itemKey
    * @param unknown_type $telephonyInstances
    * @return multitype:NULL Elluminate_Cache_Item
    */
   private function processTelephonyInstances($itemKey, $telephonyInstances){
      $cacheItems = array();
      foreach($telephonyInstances as $telephony){     
         $uriSubType = $telephony->itemtype . self::URI_SUFFIX;
         $cacheItems[] = $this->buildCacheItem($uriSubType, $itemKey, $telephony->uri);
         
         $pinSubType = $telephony->itemtype . self::PIN_SUFFIX;
         $cacheItems[] = $this->buildCacheItem($pinSubType, $itemKey, $telephony->pin);
      }   
      return $cacheItems;
   }

   private function buildCacheItem($subType, $key, $cacheValue){
      $cacheContentItem = new Elluminate_Cache_Item();
      $cacheContentItem->itemtype = Elluminate_Cache_Constants::TELEPHONY_CACHE;
      $cacheContentItem->subtype =$subType;
      $cacheContentItem->itemkey = $key;
      $cacheContentItem->value = $cacheValue;
      return $cacheContentItem;
   }
}