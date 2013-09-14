<?php
/**
 * See http://wikicentral.bbbb.net/display/BBCH/Moodle+Groups for details on the business rules
 * for this class.
 *    
 * This is a utility class designed to assist when a session needs to be switched from group mode
 * to no group mode and vice versa.  This typically occurs when the "Force Group Mode" setting has
 * been enabled and the group mode default setting is different than the setting for a particular
 * session.
 *    
 * @author dwieser
 *
 */
class Elluminate_Group_Switcher{
	private $logger;
	
	private $sessionLoader;
	private $moodleDAO;
	
	public function __construct(){
		$this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Group_Switcher");
	}
	
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
	
   /**
    * @param $originalSession - session to convert
    * @param $course - moodle course object to check for force group mode
    * @return $returnSession - if required, non-group session now re-loaded as a group session
    */
   public function checkForRequiredGroupModeChange($originalSession, $courseid){
      $course = $this->loadCourse($courseid);
   	$returnSession = $originalSession;
   	if ($course->groupmodeforce){
	   	if ($originalSession->groupmode != $course->groupmode){
	   		if ($course->groupmode > 0){
	   			$returnSession = $this->switchFromNonGroupToGroup($originalSession,$course->groupmode);
	   		}
	   	}
   	}
   	return $returnSession;
   } 

   public function sessionHasGroupModeOverride($checkSession, $courseid, $moduleGroupMode){
   	$groupModeOverride = false;	
   	$course = $this->loadCourse($courseid);
   
   	if ($course->groupmodeforce && 
   			$course->groupmode == Elluminate_Group_Session::GROUP_MODE_NONE &&
   			$checkSession->isGroupSession()){
   	         $this->logger->debug("sessionHasGroupModeOverride: Force Group Mode Override Required");
               $groupModeOverride = true;
		}
		
		/**
		 * This handles the special case where a session is:
		 *  -initially created with Force Group Mode ON (Visible or Separate)
		 *  -after the session is created, the force group mode is turned to OFF
		 *  
		 * In this scenario, the group mode in moodle is not actually turned on at a
		 * course module level, it is instead handled at a course level.  When force
		 * group mode is turned off, we end up with orphaned group child sessions.
		 * 
		 * We only process this condition if group mode force is OFF.  If it's on, then
		 * it is NORMAL for the course module group mode to not match the session group mode
		 */
		if (!$course->groupmodeforce &&
		         $moduleGroupMode == Elluminate_Group_Session::GROUP_MODE_NONE &&
		         $checkSession->isGroupSession()){
		   $this->logger->debug("sessionHasGroupModeOverride: Course Module Override Required");
         $groupModeOverride = true;
		}
   	return $groupModeOverride;
   }
	/**
	 * 
	 * @param Elluminate Session $nonGroupSession
	 * @param String $newGroupMode - visible or separate group mode
	 */
	private function switchFromNonGroupToGroup($nonGroupSession, $newGroupmode){
		$this->logger->debug("switchFromNonGroupToGroup for session :" . $nonGroupSession->id);
		//Save new session
		$nonGroupSession->switchSessionToGroupMode($newGroupmode);
		
		$groupSession = $this->sessionLoader->getSessionById($nonGroupSession->id);
		return $groupSession;
	}
	
	private function loadCourse($courseid){
	   return $this->moodleDAO->getCourseObject($courseid);
	}
}