<?php
class Elluminate_WS_ELM_Response_SetSessionResponse implements Elluminate_WS_APIResponseHandler{
    
   private $logger;

   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_ELM_Response_SessionResponse");
   }

   public function processResponse($apiResponse){
      $this->logger->debug("processResponse start");
      $session = null;
      try{
         $session = $this->loadSessionObject($apiResponse);
      }catch(Exception $e){
         throw new Elluminate_Exception("processResponse: ELM Response Processing Error",0,'user_error_soaperror');
      }
      return $session;      
   }
   
   /**
    * Populate an Elluminate_Session object with the details from the API response.
    * 
    * NOTE:  This does not use the session container, which would populate the
    * session with required dependencies like the DAO, because the session object
    * returned here is meant to just be a placeholder and typically the information 
    * is used to populate a session object created elsewhere in the application.
    * 
    * @param unknown_type $apiResult
    * @return Elluminate_Session
    */
   private function loadSessionObject($apiResult){
      $Session = new Elluminate_Session();
      $setSessionResponse = $apiResult->SetSessionResponse;
      
      $sessionResponse = $setSessionResponse->SessionResponse;
      
      $Session->meetingid = $sessionResponse->sessionId;
      $Session->sessionname = $sessionResponse->sessionName;
      $Session->timestart = $sessionResponse->startTime;
      $Session->timeend = $sessionResponse->endTime;
      $Session->creator = $sessionResponse->creatorId;
      $Session->boundarytime = $sessionResponse->boundaryTime;
      $Session->chairlist = $sessionResponse->chairList;
      $Session->course = $sessionResponse->groupingList;
      $Session->maxtalkers = $sessionResponse->maxTalkers;
      $Session->nonchairlist = $sessionResponse->nonChairList;
      $Session->recordingmode = $sessionResponse->recordingModeType;
      
      return $Session;
   }
}
