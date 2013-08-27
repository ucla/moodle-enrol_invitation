<?php
class Elluminate_Telephony_Instance{
   
   private $itemtype;
   private $uri;
   private $pin;
   private $meetingid;
   
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
      return "Telephony:" . 
               " type [" . $this->itemtype . "]" .
               " uri [" . $this->uri . "]" .
               " session [" . $this->sessionid . "]";
   }
}