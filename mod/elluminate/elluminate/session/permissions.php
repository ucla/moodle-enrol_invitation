<?php
class Elluminate_Session_Permissions{
    
   const PERMISSION_DENIED = false;
   const PERMISSION_GRANTED = true;
    
   //API Interfaces
   private $groupsAPI;
   private $capabilities;
   
   //Session Specific Values
   private $pageSession;
   private $courseModule;
   private $userid;
    
   //Permission Denied Details
   private $permissionFailureKey;
    
   private $logger;
   
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
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Session_Permissions");
   }
   
   public function setContext($context){
      $this->capabilities->setContext($context);
   }
    
   public function doesUserHaveLoadPermissionsForSession(){
      //1. Can user view the session
      if (! $this->doesUserHaveViewPermissionsForSession()){
         return self::PERMISSION_DENIED;
      }

      //2. Can user join this session
      if (! $this->capabilities->canUserJoinSession()){
         $this->permissionFailureKey = 'meetinggeneralpermissionserror';
         return self::PERMISSION_DENIED;
      }
      
      //3. Override view session permission level for private sessions.  Users can only join explicitly if they're 
      //on the invite list.  This is a SAS restriction - meeting cannot be launched if user id is not previously in
      //invitee list on SAS
      if($this->pageSession->isPrivateSession()){
         if (! $this->pageSession->isUserInMeeting($this->userid)){
            $this->permissionFailureKey = 'privatesessionnotinvited';
            return self::PERMISSION_DENIED;
         }
      }
      return self::PERMISSION_GRANTED;
   }
    
   public function doesUserHaveViewPermissionsForSession(){
      //1. Can View Session
      if (! $this->capabilities->canUserViewSession()){
         $this->permissionFailureKey = 'meetinggeneralpermissionserror';
         return self::PERMISSION_DENIED;
      }
      
      //2. If session is hidden, can user view hidden sessions
      if (!$this->hiddenSessionPermissionCheck()){
         return self::PERMISSION_DENIED;
      }
      
      //3. If session is private, must be invited or manager
      if ($this->pageSession->isPrivateSession()){
         if (! $this->canJoinPrivateSession()){
            $this->permissionFailureKey = 'privatesessionnotinvited';
            return self::PERMISSION_DENIED;
         }
      }

      //4. User is member of available group
      if (!$this->groupSessionPermissionCheck()){
         return self::PERMISSION_DENIED;
      }

      return self::PERMISSION_GRANTED;
   }
    
   
   public function doesUserHaveManageAttendancePermissionsForSession(){
      if(!$this->capabilities->canUserManageAttendance()){
         return self::PERMISSION_DENIED;
      }
      return self::PERMISSION_GRANTED;
   }
   
   public function doesUserHaveViewAttendancePermissionsForSession(){
      //1. Can user view the session in general
      if (! $this->doesUserHaveViewPermissionsForSession()){
         return self::PERMISSION_DENIED;
      }

      //2. Session Grading is setup correctly
      if (! $this->pageSession->gradesession || $this->pageSession->grade == 0){
         $this->permissionFailureKey = 'sessionnotgraded';
         return self::PERMISSION_DENIED;
      }

      //3. User has role permission to view attendance
      if (!$this->capabilities->canUserViewAttendance()){
         $this->permissionFailureKey = 'viewattendanceepermissionserror';
         return self::PERMISSION_DENIED;
      }

      return self::PERMISSION_GRANTED;
   }
    
   public function doesUserHaveManageUserPermissionsForSession($type){
      //1. Can user view the session in general

      if (! $this->doesUserHaveViewPermissionsForSession()){
         return self::PERMISSION_DENIED;
      }

      //2. If type is participant editing, meeting must be private type
      if (! $this->checkValidParticipantSessionType($type)){
         $this->permissionFailureKey = 'participanteditbadsessiontype';
         return self::PERMISSION_DENIED;
      }
      
      //3. Based on type, check for either participant or moderator editing
      if ($type === Elluminate_HTML_UserEditor::MODERATOR_EDIT_MODE){
         if (!$this->capabilities->canUserManageModerators()){
            $this->permissionFailureKey = 'meetingattendancepermissionserror';
            return self::PERMISSION_DENIED;
         }
      }else{
         if (!$this->capabilities->canUserManageParticipants()){
            $this->permissionFailureKey = 'meetingattendancepermissionserror';
            return self::PERMISSION_DENIED;
         }
      }
      return self::PERMISSION_GRANTED;
   }
    
   private function hiddenSessionPermissionCheck(){
      if (!$this->courseModule->visible && ! $this->capabilities->canUserViewHiddenSessions()){
         $this->permissionFailureKey = 'meetingprivatepermissionserror';
         return self::PERMISSION_DENIED;
      }else{
         return self::PERMISSION_GRANTED;
      }
   }
    
   private function groupSessionPermissionCheck(){
      //group id is blank for "all participants" group and everyone can get in, check for all other groups
      if ($this->pageSession->isGroupSession() && $this->pageSession->groupid){
         if (! in_array($this->pageSession->groupid, array_keys($this->groupsAPI->getAvailableGroups($this->courseModule)))) {
            $this->permissionFailureKey = 'groupsessionnotinvited';
            return self::PERMISSION_DENIED;
         }
      }
      return self::PERMISSION_GRANTED;
   }
    
   private function canJoinPrivateSession(){
      if ($this->capabilities->canUserManagePrivateSession() ||
               $this->pageSession->isUserInMeeting($this->userid)){
         return self::PERMISSION_GRANTED;
      }else{
         return self::PERMISSION_DENIED;
      }
   }
   
   /**
    * Don't allow editing of participants unless we're in a private session 
    * @param unknown_type $type
    * @return string
    */
   public function checkValidParticipantSessionType($type){
      if (! $this->pageSession->isPrivateSession() &&
               $type == Elluminate_HTML_UserEditor::PARTICIPANT_EDIT_MODE){
         return self::PERMISSION_DENIED;
      }
      return self::PERMISSION_GRANTED;
   }
   
   public function doesUserHaveManagePermissionsForSession(){
      return $this->capabilities->canManageActivities();
   }
   
   public function doesUserHaveManagePreloadPermissionsForSession(){
      return $this->capabilities->canManagePreloads();
   }
   
   public function doesUserHaveViewGuestLinkPermissionsForSession(){
      if ($this->pageSession->isPrivateSession()){
         return self::PERMISSION_DENIED;
      }
      
      if (! $this->capabilities->canViewGuestLink()){
         return self::PERMISSION_DENIED;
      }
      
      return self::PERMISSION_GRANTED;
   }
   
   public function doesUserHaveModeratePermissionsForSession(){
      if ($this->pageSession->isUserModerator($this->userid)){
        return self::PERMISSION_GRANTED;
      }
      
      if ($this->capabilities->canUserModerateSession()){
         return self::PERMISSION_GRANTED;
      }
      
      return self::PERMISSION_DENIED;
   }
   
   public function doesUserHaveDeletePermissionsForSession(){
      return $this->capabilities->canUserDeleteSession();
   }
}