<?php
class Elluminate_Grades_GradebookEntry{
   
   private $userid;
   private $rawgrade;
   private $dategraded;
   
   public function init($userid, $rawgrade)
   {
      $this->userid = $userid;
      $this->rawgrade = $rawgrade;
      $this->dategraded = time();
   }
   
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
   
   public function getStdClassObject()
   {
      return get_object_vars($this);
   }
}