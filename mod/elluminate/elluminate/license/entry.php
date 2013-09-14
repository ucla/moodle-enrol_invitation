<?php

class Elluminate_License_Entry{
   const NO_VARIATION = null;
   
   private $id;
   private $optionname;
   private $variationname = self::NO_VARIATION;
   private $licensed;
   private $updated;
   
   private $logger;
    
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_License_Entry");
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
      return "License: optionname = " . $this->optionname .
      ": variationname = " . $this->variationname .
      ": licensed = " . $this->licensed;
   }
}