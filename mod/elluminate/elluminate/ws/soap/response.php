<?php

class Elluminate_WS_SOAP_Response{
   private $apiResponse;
   private $responseType;
   private $error;
   private $empty;
   
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
}