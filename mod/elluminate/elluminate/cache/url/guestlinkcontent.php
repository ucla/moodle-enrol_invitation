<?php
class Elluminate_Cache_URL_GuestLinkContent implements Elluminate_Cache_Content{
   const NO_SUBTYPE = null;
   
   private $logger;
   
   private $schedulingManager;
  
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Cache_URL_GuestLink");
   }
    
   public function setSchedulingManager($schedulingManager){
      $this->schedulingManager = $schedulingManager;
   }

   /**
    * Item Key is SAS Session ID
    * @see Elluminate_Cache_Content::buildCacheContent()
    */
   public function buildCacheContent($itemKey){
      $guestLink =  $this->schedulingManager->getGuestLink($itemKey);
      return $this->buildCacheItem(self::NO_SUBTYPE,$itemKey,$guestLink);
   }
   
   public function manualCacheAdd($subType, $itemKey, $itemValue){
      return $this->buildCacheItem($subType, $itemKey, $itemValue);
   }
   
   private function buildCacheItem($subtype, $key, $cacheValue){
      $cacheContentItem = new Elluminate_Cache_Item();
      $cacheContentItem->itemtype = Elluminate_Cache_Constants::GUEST_LINK_CACHE;
      $cacheContentItem->subtype = $subtype;
      $cacheContentItem->itemkey = $key;
      $cacheContentItem->value = $cacheValue;
      return $cacheContentItem;
   }
}