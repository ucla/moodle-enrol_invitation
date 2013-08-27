<?php

class Elluminate_Moodle_Capabilities {
   
   private $context;
   
   const ELLUMINATE_MOD = "mod/elluminate:";
   const COURSE_MOD = "moodle/course:";
   const SITE_MOD = "moodle/site:";
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   public function checkCapability($capabilityName){
      return has_capability($this::ELLUMINATE_MOD . $capabilityName , $this->context);
   }
   
   public function checkCourseCapability($capabilityName){
      return has_capability($this::COURSE_MOD . $capabilityName , $this->context);
   }
   
   public function checkSiteCapability($capabilityName){
      return has_capability($this::SITE_MOD . $capabilityName , $this->context);
   }
}