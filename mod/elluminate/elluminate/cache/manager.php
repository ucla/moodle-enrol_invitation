<?php
class Elluminate_Cache_Manager {
   const BAD_CONTENT_TYPE = null;
   const NO_SUBTYPE = null;
   const NO_CACHE = null;
    
   private $logger;
    
   private $cacheDAO;
   private $schedulingManager;
    
   private $cacheContentTypes;
   
   private $cacheContents;
    
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Cache_Manager");
      $this->cacheContents = array();
   }
    
   public function initContentHandlers(){
      global $ELLUMINATE_CONTAINER;
      foreach($this->cacheContentTypes as $contentType){
         $cacheContentHandler = $ELLUMINATE_CONTAINER[$contentType];
         $this->cacheContents[$contentType] = $cacheContentHandler;
      }
   }
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }

   public function getCacheContent($contentType,$subType = null, $itemKey){
      $cacheValue = self::NO_CACHE;
      if ($this->isValidContentType($contentType)){
         $cacheValue = $this->attemptCacheRetrieval($contentType,$subType,$itemKey);
         if ($cacheValue == self::NO_CACHE){
            $cacheValue = $this->attemptCacheRebuild($contentType,$subType, $itemKey);
         }
      }
      return $cacheValue;
   }
    
   /**
    * This function is called to effectively "refresh" the cache for a single
    * content type and key, by first removing any existing cache and then
    * calling the associated handler to re-populate.
    *
    * @param unknown_type $contentType
    * @param unknown_type $itemKey
    * @return string
    */
   public function updateCacheContent($contentType, $subType, $itemKey){
      $cacheValue = self::NO_CACHE;
      if ($this->isValidContentType($contentType)){
         $cacheValue = $this->attemptCacheRebuild($contentType,$subType, $itemKey);
      }
      return $cacheValue;
   }

   /**
    * In most cases, the cache manager is designed to be called with a getCacheContent
    * call, which will first check the DB for a cached content item, and if not present
    * will call the associated content interface for that content type to create the cache
    * content (in most cases with a SAS call).
    *
    * However, there are circumstances where the information needed to add a cache
    * entry has been retrieved already as part of a larger batch process/SAS Call.
    *
    * This method provides an easy method of adding a cache item in the scenario where the
    * key and value are already known.
    *
    * This method will always attempt a cache removal prior - this prevents the need for the
    * calling code to always do a get/remove prior to the add.
    *
    * @param unknown_type $contentType
    * @param unknown_type $itemKey
    * @param unknown_type $itemValue
    */
   public function addCacheContent($contentType, $subType, $itemKey, $itemValue){
      $cacheValue = self::NO_CACHE;
      if ($this->isValidContentType($contentType)){
         $contentTypeHandler = $this->getContentHandler($contentType);
         $cacheResults = $contentTypeHandler->manualCacheAdd($subType, $itemKey, $itemValue);
         
         //If the item value is an array, this means multiple subtypes are getting
         //saved so we should clear all subtypes.  If it's a single item, just
         //clear the subtype specified.
         if (is_array($itemValue)){
            $this->removeCacheItem($contentType,self::NO_SUBTYPE, $itemKey);
         }else{
            $this->removeCacheItem($contentType,$subType, $itemKey);
         }
         
         //Add updated cache entries
         $cacheValue = $this->processContentHandlerResults($cacheResults, $subType);
      }
      return $cacheValue;
   }
   
   /**
    * Clear cache for a particular type and item.
    * 
    * If this cache content type has subtypes, all of those subtypes are removed
    * as well.
    * @param unknown_type $contentType
    * @param unknown_type $itemKey
    */
   public function clearContentCacheItem($contentType,$itemKey){
      $this->cacheDAO->clearCache($contentType,self::NO_SUBTYPE, $itemKey);
   }
    
   public function clearCacheContent($contentType = null){
      $this->logger->debug("clearAllCache request started, type [" . $contentType . "]");
      $this->cacheDAO->clearAllCache($contentType);
      $this->logger->debug("clearAllCache request completed");
   }
    
   private function isValidContentType($contentType){
      if (in_array($contentType,$this->cacheContentTypes)){
         return true;
      }else{
         $this->logger->error("PROGRAMMER ERROR: Bad Cache Content Type Specified [" . $contentType . "]");
         return false;
      }
   }
    
   private function attemptCacheRebuild($contentType, $subType, $itemKey){
      $cacheValue = self::NO_CACHE;
      $cacheResults = $this->buildCacheContent($contentType, $itemKey);
      
      if ($cacheResults != self::NO_CACHE){
         
         //remove all cache entries for this item (will clear ALL subtypes)
         $this->removeCacheItem($contentType,self::NO_SUBTYPE, $itemKey);
         $cacheValue = $this->processContentHandlerResults($cacheResults,$subType);
      }
      return $cacheValue;
   }
   
   private function processContentHandlerResults($cacheResults, $subType){
      if ($cacheResults == self::NO_CACHE){
         return self::NO_CACHE;
      }
      
      if (is_array($cacheResults)){
         $cacheValue = $this->processCacheResultArray($cacheResults, $subType);
      }else{
         $cacheValue = $this->processCacheResultSingleItem($cacheResults);
      }
      return $cacheValue;
   }
    
   /**
    * Loop through the set of results
    * @param unknown_type $cacheItems
    * @param unknown_type $subType
    * @return string
    */
   private function processCacheResultArray($cacheResults, $subType){
      $cacheValue = self::NO_CACHE;
      foreach($cacheResults as $cacheItem){
         $this->processCacheResultSingleItem($cacheItem);
         if ($cacheItem->subtype == $subType){
            $cacheValue = $cacheItem->value;
         }
      }
      return $cacheValue;
   }
    
   private function processCacheResultSingleItem($cacheItem){
      if ($cacheItem != null){
         //Don't save a blank cache item
         if ($cacheItem->value != self::NO_CACHE){
            $cacheValue = $cacheItem->value;
            $this->saveCache($cacheItem);
         }
      }
      return $cacheValue;
   }
    
   private function attemptCacheRetrieval($contentType,$subType,$itemKey){
      $cacheValue = self::NO_CACHE;
      $dbRecord = $this->cacheDAO->loadCache($contentType, $subType, $itemKey);
      if ($dbRecord != null){
         $cacheItem = $this->loadCacheItemFromDatabase($dbRecord);
         $cacheValue = $cacheItem->value;
      }
      return $cacheValue;
   }
    
   private function loadCacheItemFromDatabase($dbRecord){
      $cacheItem = new Elluminate_Cache_Item();
      $cacheItem->id = $dbRecord->id;
      $cacheItem->itemtype = $dbRecord->itemtype;
      $cacheItem->itemkey = $dbRecord->itemkey;
      $cacheItem->value = $dbRecord->value;
      return $cacheItem;
   }

   /**
    * This function does a call to delete a cache key from the database.
    *
    * Even though the key may not exist, this is a cheaper operation
    * than checking for existence and then deleting.
    *
    * Error for non-existent key is trapped at the DAO level.
    *
    * @param unknown_type $contentType
    * @param unknown_type $itemKey
    * @return string|Elluminate_Cache_Item
    */
   private function removeCacheItem($contentType, $subtype, $itemKey){
      $dbRecord = $this->cacheDAO->clearCache($contentType,$subtype,$itemKey);
   }
    
   private function buildCacheContent($contentType,$itemKey){
      $contentTypeHandler = $this->getContentHandler($contentType);
      if ($contentTypeHandler){
         return $contentTypeHandler->buildCacheContent($itemKey);
      }else{
         return self::BAD_CONTENT_TYPE;
      }
   }
    
   private function saveCache($cacheItem){
      $cacheItem->created = time();
      $this->cacheDAO->saveCache($cacheItem);
   }
    
   private function getContentHandler($contentType){
      $contentHandler = $this->cacheContents[$contentType];
      if ($contentHandler == null){
         $this->logger->error("PROGRAMMER ERROR: Bad Cache Content Type Specifie [" . $contentType . "]");
         return self::BAD_CONTENT_TYPE;
      }
      return $contentHandler;
   }
}