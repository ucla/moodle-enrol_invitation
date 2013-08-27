<?php
class Elluminate_Cache_DAO{
   const TABLE_NAME = "elluminate_cache";
    
   private $logger;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Cache_DAO");
   }
   
   public function saveCache($cacheItem){
      global $DB;
      $id = $DB->insert_record(self::TABLE_NAME, $cacheItem->getDBInsertObject());
      if (!$id){
         throw new Elluminate_Exception('Could not save url to DB', 0, 'user_error_database');
      }
      return $id;
   }
   
   /**
    * Clear a particular cache item - trap any errors and ignore - this function may be called even
    * if cache record does not exist.
    * @param unknown_type $type
    * @param unknown_type $key
    */
   public function clearCache($type,$subtype = null,$key){
      global $DB;
      
      $itemExisted = false;
      try{
         if ($subtype != null){
            $query = array('itemtype'=>$type,'subtype'=>$subtype,'itemkey'=>$key);
         }else{
            $query = array('itemtype'=>$type,'itemkey'=>$key);
         }
         $DB->delete_records(self::TABLE_NAME, $query);
         $itemExisted = true;
      }catch(Exception $e){
         $this->logger->debug("No cache exists for type: " . $type . " and key: " . $key);
      }
      return $itemExisted;
   }
    
   public function loadCache($type, $subtype, $key){
      global $DB;
      if ($subtype != null){
         $query = array('itemtype'=>$type,'subtype'=>$subtype, 'itemkey'=>$key);
      }else{
         $query = array('itemtype'=>$type, 'itemkey'=>$key);
      }
      return $DB->get_record(self::TABLE_NAME, $query);
   }
   
   public function clearAllCache($contenttype = null){
      global $DB;
      if ($contenttype){
         $this->logger->debug("type delete:" . $contenttype);
         $DB->delete_records(self::TABLE_NAME,  array('itemtype'=>$contenttype));
      }else{
         $DB->delete_records(self::TABLE_NAME);
      }
   }
}