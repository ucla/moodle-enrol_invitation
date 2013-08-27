<?php

class Elluminate_Exception extends Exception{
   
   const DEFAULT_CODE = 0;
   
   //Session Codes
   const CREATE = 1;
   const UPDATE = 2;
   const DELETE = 3;
   const LOAD = 4;
   
   //Other business objects
   const PRELOADS = 5;
   const RECORDINGS = 6;
   // This is a key to be used when calling get_string()
   // The resulting string is an error message suitable 
   // to display to the user.
   protected $user_message_key;

   protected $error_details;
   
   public function __construct($message = null, $code = 0, $user_message_key = null, $details = null) {
      $this->user_message_key = $user_message_key;
      parent::__construct($message, $code);
      $this->error_details = $details;
      $this->logError();
   }
   
   final public function getUserMessage() {
   	  return $this->user_message_key;
   }

   final public function getDetails() {
   	  return $this->error_details;
   }

   final public function getUserMessageKey() {
   	  return $this->user_message_key;
   }
    
   private function logError(){
      $logger = Elluminate_Logger_Factory::getLogger("Elluminate_Exception");
      $logger->error("Error Code: " . $this->code . " - " . $this->getExceptionOutput());
   }
   
   function getExceptionOutput() {
      $trace = $this->getTrace();
   
      $result = 'Exception: "';
      $result .= $this->getMessage();
      $result .= '" @ ';
      if($trace[0]['class'] != '') {
         $result .= $trace[0]['class'];
         $result .= '->';
      }
      $result .= $trace[0]['function'];
      $result .= '();';
   
      return $result;
   }
}