<?php
class Elluminate_Cache_URL_RecordingContent implements Elluminate_Cache_Content{  
   private $schedulingManager;
   
   private $logger;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Cache_URL_RecordingContent");
   }
   
   public function setSchedulingManager($schedulingManager){
      $this->schedulingManager = $schedulingManager;  
   }
   
   /**
    * Item Key in this case is the SAS ID for the recording that a URL is being requested for
    * 
    * @see Elluminate_Cache_Content::buildCacheContent()
    */
   public function buildCacheContent($itemKey){
      $this->logger->debug("buildCacheContent [" . $itemKey . "]");
      $recordingFiles = $this->schedulingManager->getRecordingPlaybackDetails(array($itemKey));
      
      $cacheItems = array();

      foreach ($recordingFiles as $recordingFile){
         $cacheItem = $this->buildCacheItem($recordingFile->format, $itemKey, $recordingFile->url);
         $cacheItems[] = $cacheItem;   
      }
      
      return $cacheItems;
   }
   
   public function manualCacheAdd($subType, $itemKey, $itemValue){
      return $this->buildCacheItem($subType, $itemKey, $itemValue);
   }
   
   private function buildCacheItem($subtype, $key, $cacheValue){
      $cacheContentItem = new Elluminate_Cache_Item();
      $cacheContentItem->itemtype = Elluminate_Cache_Constants::RECORDING_URL_CACHE;
      $cacheContentItem->subtype = $subtype;
      $cacheContentItem->itemkey = $key;
      $cacheContentItem->value = $cacheValue;
      return $cacheContentItem;
   }
}