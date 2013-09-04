<?php
/**
 * Capabilities Related to Session
 *
 * joinmeeting
 * manage
 * manageparticipants
 * managemoderators
 * moderatemeeting
 * view
 * viewguestlink
 * viewattendance
 * managepreloads
 *
 * This is meant as a simple facade to allow testing of moodle capabilities.  
 * There should be very little integration here, with the most complex logic being
 * a combination of different moodle role permissions 
 * 
 * (i.e. manage private sessions = managemoderators + manageparticipants)
 * 
 * If additional permission check logic is required beyond the basic role 
 * perms here, @see Elluminate_Session_Permissions. 
 *
 * @author dwieser
 */
class Elluminate_Session_Capabilities{
    
   private $capabilitiesChecker;
   private $logger;

   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Session_Capabilities");
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
    
   
   //This value doesn't get set until runtime, but this object
   //is created by the pimple container, so we need an explicit
   //setter.
   public function setContext($context){
      $this->capabilitiesChecker->context = $context;
   }

   public function canUserViewAttendance()
   {
      return $this->capabilitiesChecker->checkCapability('viewattendance');
   }
    
   public function canUserManageAttendance()
   {
      return $this->capabilitiesChecker->checkCapability('manageattendance');
   }
   /**
    * Check if user is a session participant
    *
    * @param unknown_type $userID
    */
   public function canUserJoinSession()
   {
      return $this->capabilitiesChecker->checkCapability('joinmeeting');
   }
   
   /**
    * Delete Permissions

    *
    * TODO: shouldn't there be a collab role capabilities for this?
    * Esp. odd since we have permissions sets for delete recording and preload
    *
    * At the moment, I'm using the ability to join/moderate the meeting as the
    * indicator of ability to delete
    */
   public function canUserDeleteSession()
   {
      if ($this->canUserJoinSession() && $this->canUserModerateSession()){
         return true;
      }else{
         return false;
      }
   }
    
   /**
    * Can the current user manage the moderator list for current session
    */
   public function canManageModerators()
   {
      $canManagerModerators = false;
      $canView = $this->capabilitiesChecker->checkCapability('view');
      $canManage = $this->capabilitiesChecker->checkCapability('managemoderators');
      if ($canView && $canManage){
         $canManagerModerators = true;
      }
      return $canManagerModerators;
   }

   /**
    * Can current user view the guest link for a session?
    */
   public function canViewGuestLink(){
      return $this->capabilitiesChecker->checkCapability('viewguestlink');
   }
    
   /**
    * Can current user manage activities
    */
   public function canManageActivities(){
      return $this->capabilitiesChecker->checkCourseCapability('manageactivities');
   }
    
   /**
    * Can User manage session participants
    *
    */
   public function canManageParticipants(){
      return $this->capabilitiesChecker->checkCapability('manageparticipants');
   }
    
   public function canManagePreloads(){
      return $this->capabilitiesChecker->checkCapability('managepreloads');
   }
    
   public function canUserViewHiddenSessions(){
      return $this->capabilitiesChecker->checkCourseCapability('viewhiddenactivities');
   }
    
   public function canUserViewSession(){
      return $this->capabilitiesChecker->checkCapability('view');
   }
    
   public function canUserManagePrivateSession(){
      if ($this->capabilitiesChecker->checkCapability('managemoderators') ||
               $this->capabilitiesChecker->checkCapability('manageparticipants')){
         return true;
      }else{
         return false;
      }
   }
    
   public function canUserModerateSession(){
      return $this->capabilitiesChecker->checkCapability('moderatemeeting');
   }
   
   public function canUserManageParticipants(){
      return $this->capabilitiesChecker->checkCapability('manageparticipants');
   }
   
   public function canUserManageModerators(){
      return $this->capabilitiesChecker->checkCapability('managemoderators');
   }
}