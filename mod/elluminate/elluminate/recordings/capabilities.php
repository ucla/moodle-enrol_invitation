<?php
/**
 * Permissions Related to Recordings:
 * 
 * deleteanyrecordings
 * deleterecordings (delete where user is creator of session)
 * editallrecordings
 * editownrecordings
 * enablerecordings (hide/show recordings where user is creator of session)
 * manageanyrecordings (hide/show)
 * managerecordings (hide/show if group member)
 * viewrecordings
 * 
 * @author dwieser
 *
 *
 * TODO: a lot of the logic here related to session and user checking should be moved out into
 *     @see Elluminate_Recordings_Permissions
 * so this remains a simple facade to the moodle role capabilities
 * 
 * Also:
 *      @see Elluminate_Session_Capabilities 
 *      @see Elluminate_Session_Permissions
 */
class Elluminate_Recordings_Capabilities{
   
   private $capabilitiesChecker;
   private $logger;
   
   public function __construct (){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Recordings_Capabilities");
   }
   
   // ** GET/SET Magic Methods **
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
   // ** GET/SET Magic Methods **
    
   public function setContext($context){
      $this->capabilitiesChecker->context = $context;
   }
   
   //EDIT DESCRIPTION
   public function canUserEditAnyRecording(){
      return $this->capabilitiesChecker->checkCapability('editallrecordings');
   }
   
   public function canUserEditOwnRecordings(){
      return $this->capabilitiesChecker->checkCapability('editownrecordings');
   }
   
   //DELETE RECORDING
   public function canUserDeleteAnyRecording(){
      return $this->capabilitiesChecker->checkCapability('deleteanyrecordings');
   }
   
   public function canUserDeleteOwnRecordings(){
      return $this->capabilitiesChecker->checkCapability('deleterecordings');
   }
   
   //TOGGLE VISIBILITY
   public function canUserManageAnyRecording(){
      return $this->capabilitiesChecker->checkCapability('manageanyrecordings');
   }
   
   public function canUserManageOwnRecordings(){
      return $this->capabilitiesChecker->checkCapability('enablerecordings');
   }

   //VIEW IN GENERAL
   public function canViewRecordings(){
      return $this->capabilitiesChecker->checkCapability('viewrecordings');
   }
   
   public function canManageGroupSettings(){
      return $this->capabilitiesChecker->checkCapability('managerecordings');
   }
   
   public function canConvertAnyRecording(){
      return $this->capabilitiesChecker->checkCapability('convertallrecordings');
   }
   
   public function canConvertOwnRecordings(){
      return $this->capabilitiesChecker->checkCapability('convertownrecordings');
   }
}