<?php

class Elluminate_Session_Factory{
   
   private $logger;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Session_Factory");
   }
   
   public function newSession($sessionType){
      $newSession = $this->getSessionObject($sessionType);
      $newSession->sessiontype = $sessionType;
      return $newSession;
   }

   /**
    * Factory method to return the appropriate type of session
    * @param unknown_type $sessionType
    * @throws Elluminate_Exception
    * @return Elluminate_Session|Elluminate_Group_Session
    */
   private function getSessionObject($sessionType){
      global $ELLUMINATE_CONTAINER;
      
      if ($sessionType == Elluminate_Session::COURSE_SESSION_TYPE || $sessionType == Elluminate_Session::PRIVATE_SESSION_TYPE){
         $this->logger->debug("[Elluminate_Session]");
         return $ELLUMINATE_CONTAINER['session'];
      }
   
      if ($sessionType == Elluminate_Session::GROUP_SESSION_TYPE){
         $this->logger->debug("[Elluminate_Group_Session]");
         return $ELLUMINATE_CONTAINER['groupsession'];
      }
   
      if ($sessionType == Elluminate_Session::GROUP_CHILD_SESSION_TYPE){
         $this->logger->debug("[Elluminate_Group_ChildSession]");
         return $ELLUMINATE_CONTAINER['groupchildsession'];
      }
      //Bad Session Type
      throw new Elluminate_Exception(get_string('invalidsessiontype', 'elluminate') . $sessionType);
   }
}