<?php
class Elluminate_WS_ELM_Response_UploadRepositoryPresentationResponse implements Elluminate_WS_APIResponseHandler{

   private $logger;

   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_ELM_Response_UploadRepositoryPresentationResponse");
   }
    
   public function processResponse($apiResponse){
      $this->logger->debug("processResponse start");
      $presentationId = null;
      try{
         $outerPresentationResponse = $apiResponse->UploadRepositoryPresentationResponse;
         $presentationResponse = $outerPresentationResponse->PresentationResponse;
         $presentationId = $presentationResponse->presentationId;
      }catch(Exception $e){
         throw new Elluminate_Exception("processResponse: SAS Response Processing Error",0,'user_error_soaperror');
      }
      return $presentationId;
   }
}