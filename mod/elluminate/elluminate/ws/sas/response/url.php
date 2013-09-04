<?php
class Elluminate_WS_SAS_Response_Url implements Elluminate_WS_APIResponseHandler{
    
   private $logger;

   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_SAS_Response_urlResponse");
   }
   
   public function processResponse($apiResponse){
      $this->logger->debug("processResponse start");
      $responseUrl = null;
      try{
         $responseUrl = $apiResponse->url;
      }catch(Exception $e){
         throw new Elluminate_Exception("processResponse: SAS Response Processing Error",0,'user_error_soaperror');
      }
      return $responseUrl;
   }
}