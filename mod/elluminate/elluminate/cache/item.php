<?php
class Elluminate_Cache_Item{ 
   private $id;
   private $itemtype;
   private $subtype;
   private $itemkey;
   private $value;
   private $created;
    
   private $logger;
    
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Cache_Item");
   }
    
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
    
   /**
    * Helper function to return a StdClass version of this object
    * to allow public access on all member variables.
    *
    * This is required by the $DB moodle object to do DB operations
    * with the object
    *
    * @return StdClass
    */
   public function getDBInsertObject()
   {
      return get_object_vars($this);
   }
    
   public function __toString(){
      return "CACHE: type = " . $this->itemtype .
      ": key = " . $this->itemkey .
      ": created = " . $this->created;
   }
}