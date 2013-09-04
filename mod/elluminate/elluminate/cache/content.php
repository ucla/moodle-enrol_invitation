<?php
interface Elluminate_Cache_Content{
   public function setSchedulingManager($schedulingManager);
   
   /* This method will accept a content item key and 
   /* MUST return a object of type Elluminate_Cache_Item
    * with the appropriate key for the content item created.
    */
   public function buildCacheContent($itemKey);
   
   /**
    * This is used by the API to build cache entry based on 
    * values already known.  It will give the content type
    * interface a chance to update the content item (esp the key)
    * as required.
    * @param unknown_type $itemKey
    * @param unknown_type $itemValue
    */
   public function manualCacheAdd($subType, $itemKey, $itemValue);
}