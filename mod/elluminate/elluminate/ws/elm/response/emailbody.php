<?php
class Elluminate_WS_ELM_Response_EmailBody implements Elluminate_WS_APIResponseHandler{

   private $logger;

   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_ELM_Response_EmailBody");
   }

   public function processResponse($apiResponse){
      $this->logger->debug("processResponse start");
      $session = null;
      try{
         $session = $this->getGuestLink($apiResponse);
      }catch(Exception $e){
         throw new Elluminate_Exception("processResponse: ELM Response Processing Error",0,'user_error_soaperror');
      }
      return $session;
   }
   
   private function getGuestLink($apiResponse){
      $emailBody = $apiResponse->emailBody;
      if ($emailBody == ''){
         return;
      }
      
      $elm_start_email_snippet = 'Meeting Link: ';
      $start_index = strrpos($emailBody, $elm_start_email_snippet);
      $end_email_snippet = 'Add to Calendar:';
      
      $start_index = $start_index + 14;
      $end_index = strpos($emailBody, $end_email_snippet, $start_index);
      
      $length = $end_index - $start_index;
      $link = trim(substr($emailBody, $start_index, $length));
      
      return $link;
   }
}
