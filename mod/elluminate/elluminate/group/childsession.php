<?php
class Elluminate_Group_ChildSession extends Elluminate_Session{
   private $logger;

   public function __construct(){
      parent::__construct();
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Group_ChildSession");
   }

   // ** GET/SET Magic Methods **
   public function __get($property)
   {
      if (property_exists($this, $property)) {
         return $this->$property;
      }else{
         return parent::__get($property);
      }
   }

   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }else{
         parent::__set($property,$value);
      }
      return $this;
   }

   /**
    * On update there may actually be a change to the custom naming settings for groups
    * so we need to re-process this.
    *
    */
   public function updateChildSessionNaming(){
      if ($this->hasCustomNaming()){
         $groupName = $this->groupAPI->getGroupName($this->groupid);
         $this->processCustomNamingUpdates($groupName);
      }
   }

   public function updateSession(){
      $this->logger->debug("Elluminate_Group_ChildSession: updateSession: " . $this->id);
      if ($this->meetinginit == Elluminate_Session::MEETING_INIT_COMPLETE){
         $this->updateSessionOnServer();
      }

      $this->updateSessionInDB();
   }

   public function createChildSession($groupid, $groupname){
      $this->groupid = $groupid;
      if ($this->hasCustomNaming()){
         $this->processCustomNamingUpdates($groupname);
      }
      $this->saveNewSessionToDB();
   }

   public function deleteSession(){
      $this->logger->debug("Elluminate_Group_ChildSession: deleteSession: " . $this->id);
      if($this->meetinginit == Elluminate_Session::MEETING_INIT_COMPLETE){
         $this->deleteServerSession();
      }
      $this->deleteDBSession();
   }

   public function isGroupSession(){
      return true;
   }

   public function getParentGroupName(){
      return $this->groupAPI->getGroupName($this->groupid);
   }

   public function getSessionType(){
      return Elluminate_Session::GROUP_CHILD_SESSION_TYPE;
   }

   public function logToGradeBook($userId){
      $parentSessionName = $this->sessionDAO->loadParentSessionName($this->groupparentid);
      $this->logger->debug("logToGradeBook, session name: " . $parentSessionName);
      $this->sessionGrading->logToGradeBook($this->groupparentid,
               $this->course,$parentSessionName, $this->grade, $userId);
   }
   
   /**
    * Log Attendance for a particular user for this group child session.  In this case,
    * all grades are logged for the parent session, not the child.  This leads to only
    * one gradebook column instead of multiple.
    * @param unknown_type $userId
    */
   public function logAttendance($userId){
      $this->logger->debug("logAttendance: " . $this->grade);
      $this->sessionGrading->logSessionAttendance($this->groupparentid,$userId,$this->grade);
   }
   
   /**
    * Session Name and Description can be updated based on settings for the parent session.
    *
    * If the name changes, validation is required to be run again.
    * 
    * During create and update, there is form-level validation to prevent errors with 
    * group custom naming.  If we're at this point and validation fails, it's most likely 
    * this is happening because we're creating a new group child session for a group
    * that was added after initial session creation.  In this case, there is nothing to
    * do about the error except name the session with a name indicating an error.
    *
    * @throws Elluminate_Exception
    */
   private function processCustomNamingUpdates($groupName){
      if ($this->customname > 0){
         $this->sessionname = 
            $this->customNamingHelper->buildCustomGroupName($this->sessionname, $groupName, $this->customname);
      }
      
      if ($this->customdescription){
         $this->description = $this->customNamingHelper->buildCustomDescription($this->description,$groupName);
      }
   }

   private function hasCustomNaming(){
      if ($this->customname > 0 || $this->customdescription > 0){
         return true;
      }else{
         return false;
      }
   }
}