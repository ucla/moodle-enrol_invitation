<?php
class Elluminate_WS_ELM_Response_Url implements Elluminate_WS_APIResponseHandler{

   private $logger;

   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_ELM_Response_Url");
   }
    
   public function processResponse($apiResponse){
      $this->logger->debug("processResponse start");
      $responseUrl = null;
      try{
         $responseUrl = $apiResponse->url;
      }catch(Exception $e){
         throw new Elluminate_Exception("processResponse: ELM Response Processing Error",0,'user_error_soaperror');
      }
      return $responseUrl;
   }
}