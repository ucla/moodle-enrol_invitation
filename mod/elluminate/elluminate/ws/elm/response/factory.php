<?php
class Elluminate_WS_ELM_Response_Factory{
   const RESPONSE_CLASS_PREFIX = 'Elluminate_WS_ELM_Response_';
    
   private $logger;
    
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_ELM_Response_Factory");
   }
    
   /**
    * Will return an object implementing the Elluminate_WS_APILoader interface that can be used
    * to process a particular response object
    */
   public function getResponseHandler($responseType){
      global $ELLUMINATE_CONTAINER;
      $className = self::RESPONSE_CLASS_PREFIX . $responseType;
      $this->logger->debug("getResponseHandler [" . $responseType . "]");

      $responseHandler = null;

      if (class_exists($className)){
         $responseHandler = $ELLUMINATE_CONTAINER[$responseType];
      }else{
         $this->logger->error("Unknown SAS Response Type: " . $responseType);
         throw new Elluminate_Exception(get_string('responseerror','elluminate',$responseType), 0, 'user_error_soaperror');
      }
      return $responseHandler;
   }
}