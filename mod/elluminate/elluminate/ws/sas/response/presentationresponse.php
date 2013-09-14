<?php
class Elluminate_WS_SAS_Response_PresentationResponse implements Elluminate_WS_APIResponseHandler{

   private $logger;

   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_SAS_Response_presentationResponse");
   }
    
   public function processResponse($apiResponse){
      $this->logger->debug("processResponse start");
      $presentationId = null;
      try{
         $presentationResponse = $apiResponse->PresentationResponse;
         $presentationId = $presentationResponse->presentationId;
      }catch(Exception $e){
         throw new Elluminate_Exception("processResponse: SAS Response Processing Error",0,'user_error_soaperror');
      }
      return $presentationId;
   }
}