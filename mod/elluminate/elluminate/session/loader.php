<?php
class Elluminate_Session_Loader{
	
	const NO_SESSION_FOUND = null;
	
	const NO_CONTEXT = null;
	
	const BAD_SESSION_TYPE = -1;
	
	//External Dependencies
	private $sessionDAO;
	private $sessionFactory;	
	
	private $logger;	
	private $currentContext;
	
	public function __construct(){
		$this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Session_Loader");
	}

	public function __set($property, $value)
	{
		if (property_exists($this, $property)) {
			$this->$property = $value;
		}
		return $this;
	}
	
	/**
	 * Load a Session by it's ID (Internal Moodle ID)
	 * 
	 * @param unknown_type $sessionID
	 * @throws Elluminate_Exception
	 * @return unknown
	 */
	public function getSessionById($sessionID) {
		$this->logger->debug("getSessionById: " . $sessionID);
		$dbObject = $this->loadSessionFromDB($sessionID);
		if ($dbObject == $this::NO_SESSION_FOUND) {
			throw new Elluminate_Exception("Session ID of " . 
                  $sessionID . " is invalid - could not be loaded from database.", 
                  Elluminate_Exception::LOAD, 'user_error_processing');
		} else {
			$loadedSession = $this->loadSessionFromDatabaseObject($dbObject);
		}
	
		return $loadedSession;
	}
	
	/**
	 * 
	 * Load a session by ID and Group.  This is done by first loading the Parent
	 * Group.  This auto-loads all of the child groups. 
	 * 
	 * @param $sessionID
	 * @param $groupId
	 * @param $courseModule - Moodle Course Module - needed to check for presence of new groups
	 * @throws Elluminate_Exception
	 * @return populated
	 */
	public function getSessionByIdAndGroup($sessionID,$groupId,$courseModule){
		$dbObject = $this->loadSessionFromDB($sessionID);
		if ($dbObject == $this::NO_SESSION_FOUND) {
			throw new Elluminate_Exception("Session ID of " .
					$sessionID . " is invalid - could not be loaded from database.",
					Elluminate_Exception::LOAD, 'user_error_processing');
		} else {
			$loadedSession = $this->loadSessionFromDatabaseObject($dbObject);
		}
		
		$this->logger->debug("getSessionByIdAndGroup: session found for id: " . $sessionID);
		if ($loadedSession->isGroupSession()){
		   $this->logger->debug("getSessionByIdAndGroup: group session");
         return $this->loadGroupChildSession($loadedSession,$groupId,$courseModule);		
		}else{
			return $loadedSession;
		}
	}
	
	/**
	 * Given a session MEETING (SAS) ID, load that session from
	 * the database and populate the session
	 * 
	 * @param unknown_type $sessionid
	 * @return boolean
	 */
	public function getSessionByMeetingId($meetingID){
		$dbObject = $this->sessionDAO->loadSessionByMeetingId($meetingID);
		if ($dbObject) {
			$loadedSession = $this->loadSessionFromDatabaseObject($dbObject);
		} else {
		   $loadedSession = null;
		}
		return $loadedSession;
	}
	
	/**
	 * This constructor will convert the stdClass object returned from the
	 * Moodle mod_form into a Session Object
	 *
	 * Only partial data is returned from the form, so only partial setup of
	 * the new session is completed.
	 *
	 * @param StdClass $modFormStdClass
	 * @return $elluminate_session
	 */
	public function loadSessionFromForm($modFormStdClass){
	   //If force group mode is set, then the mod form group mode will be 0, and the
	   //real group mode will be stored in forcegroupmode.  This is because when group mode
	   //is forced, the group mode field is just a text field and not a form value.
	   if (isset($modFormStdClass->forcegroupmode)){
	      $modFormStdClass->groupmode = $modFormStdClass->forcegroupmode;
	   }

	   $newSession = $this->getNewSessionObject($modFormStdClass);
		$this->populateSessionFromForm($newSession,$modFormStdClass);
		return $newSession;
	}
	
	/**
	 * 
	 * 
	 * @param StdClass $dbObject
	 * @return populated Elluminate_Session or Elluminate_Group_Session
	 */
	public function loadSessionFromDatabaseObject($dbObject){
	   $newSession = $this->getNewSessionObject($dbObject);
		$this->populateSessionFromDatabase($newSession, $dbObject);
		
		$newSession->currentContext = $this->currentContext;
		
		//Session Data is fully populated, do any additional loading native to the session object
		$newSession->loadSession();
		
		return $newSession;
	}
	
	public function loadChildSessionFromParent($parentSession){
		$childSession = $this->getNewChildSession();
		$this->populateChildFromParent($parentSession, $childSession);
		return $childSession;
	}
	
	/**
	 * Given a session ID, load that session from the database and populate the session
	 * @param unknown_type $sessionid
	 * @return boolean
	 */
	private function loadSessionFromDB($sessionid){
		$success = false;
		$dbObject = $this->sessionDAO->loadSession($sessionid);
		if ($dbObject){
			return $dbObject;
		}
		return $this::NO_SESSION_FOUND;
	}
	
	/**
	 * newSession constructor will accept an object of type StdClass and
	 * return an Elluminate_Session Object
	 *
	 * @param unknown_type $databaseObject
	 * @return unknown
	 */
	private function populateSessionFromDatabase($newSession, $databaseObject)
	{
		$newSession->id = $databaseObject->id;
		$newSession->meetingid = $databaseObject->meetingid;
		$newSession->meetinginit = $databaseObject->meetinginit;
		$newSession->course = $databaseObject->course;
		$newSession->creator = $databaseObject->creator;
		$newSession->sessiontype = $databaseObject->sessiontype;
		$newSession->name = $databaseObject->name;
		$newSession->sessionname = $databaseObject->sessionname;
		$newSession->description = $databaseObject->description;
		$newSession->intro = $databaseObject->intro;
		$newSession->introformat = $databaseObject->introformat;
		$newSession->customname = $databaseObject->customname;
		$newSession->customdescription = $databaseObject->customdescription;
		$newSession->timestart = $databaseObject->timestart;
		$newSession->timeend = $databaseObject->timeend;
		$newSession->recordingmode = $databaseObject->recordingmode;
		$newSession->boundarytime = $databaseObject->boundarytime;
		$newSession->boundarytimedisplay = $databaseObject->boundarytimedisplay;
		$newSession->maxtalkers = $databaseObject->maxtalkers;
		$newSession->chairlist = $databaseObject->chairlist;
		$newSession->nonchairlist = $databaseObject->nonchairlist;
		$newSession->grade = $databaseObject->grade;
		$newSession->gradesession = $databaseObject->gradesession;
		$newSession->telephony = $databaseObject->telephony;
		$newSession->timemodified = $databaseObject->timemodified;
	
		//Group Mode Settings
		$newSession->groupingid = $databaseObject->groupingid;
		$newSession->groupmode = $databaseObject->groupmode;
		$newSession->groupid = $databaseObject->groupid;
		$newSession->groupparentid = $databaseObject->groupparentid;
	
		if (isset($databaseObject->section)){
			$newSession->section = $databaseObject->section;
		}
		
		if ($newSession->sessiontype == Elluminate_Session::PRIVATE_SESSION_TYPE){
		   $newSession->restrictparticipants = true;
		}else{
		   $newSession->restrictparticipants = false;
		}
	}
	
	private function populateSessionFromForm($newSession, $modFormStdClass){
		$newSession->course = $modFormStdClass->course;
		$newSession->name = $modFormStdClass->name;
		$newSession->description = $modFormStdClass->description['text'];
		$newSession->timestart = $modFormStdClass->timestart;
		$newSession->timeend = $modFormStdClass->timeend;
		$newSession->recordingmode = $modFormStdClass->recordingmode;
		$newSession->boundarytime = $modFormStdClass->boundarytime;
		$newSession->grade = $modFormStdClass->grade;
		$newSession->maxtalkers = $modFormStdClass->maxtalkers;
		
		if (isset($modFormStdClass->section)){
			$newSession->section = $modFormStdClass->section;
		}
		
		if (empty ($modFormStdClass->sessionname)) {
			$newSession->sessionname = $modFormStdClass->name;
		}else{
			$newSession->sessionname = $modFormStdClass->sessionname;
		}
		
		//Groups
		$newSession->groupingid = $modFormStdClass->groupingid;
		$newSession->groupmode = $modFormStdClass->groupmode;
		
		//Optional values, may not be present
		if (isset($modFormStdClass->boundarytimedisplay)){
			$newSession->boundarytimedisplay = $modFormStdClass->boundarytimedisplay;
		}
		
		if (isset($modFormStdClass->gradesession)){
			$newSession->gradesession = $modFormStdClass->gradesession;
		}

		if (isset($modFormStdClass->customname)){
			$newSession->customname = $modFormStdClass->customname;
		}
		
		if (isset($modFormStdClass->customdescription)){
			$newSession->customdescription = $modFormStdClass->customdescription;
		}
		
		if (isset($modFormStdClass->restrictparticipants)){
		   $newSession->restrictparticipants = $modFormStdClass->restrictparticipants;
		}
		
		if (isset($modFormStdClass->telephony_formvalue)){
		   $newSession->telephony = $modFormStdClass->telephony_formvalue;
		}
	}
	
	/**
	 * Loading a session from a StdClass object occurs in 2 scenarios:
	 *   -Loading Data from the Session Edit Form (@see mod_form.php)
	 *   -Loading a Session from the Database 
	 * 
	 * In both cases, we need to determine the session type from the raw
	 * data in order to get the right Session Class instantiated from the
	 * Loader.
	 * 
	 * The rules being followed here are:
	 *   
	 *   Group Sessions:
	 *      -group mode != 0
	 *          
	 *    Regular Session
	 *    	-not a group session (else)
	 *       
	 * @param unknown_type $rawSession
	 * @return Ambigous <NULL, number>
	 */
	private function determineSessionType($rawSession){

		if ($this->isGroupMode($rawSession)){
			if ($this->isGroupChild($rawSession)){
				return Elluminate_Session::GROUP_CHILD_SESSION_TYPE;
			}else{
				$sessionType = Elluminate_Session::GROUP_SESSION_TYPE;
			}
		}else{
			$sessionType = $this->getSessionType($rawSession);
		}
		return $sessionType;
	}
	
	private function getNewSessionObject($sessionDetails){
	   $sessionType = $this->determineSessionType($sessionDetails);
	   $newSession = $this->sessionFactory->newSession($sessionType);
	   $newSession->sessiontype = $sessionType;
	   return $newSession;
	}
	
	private function getNewChildSession(){
	   $sessionType = Elluminate_Session::GROUP_CHILD_SESSION_TYPE;
      $childSession = $this->sessionFactory->newSession($sessionType);
	   $childSession->sessiontype = $sessionType;
	   return $childSession;
	}
	
	/**
	 * Accepts a StdClass object that comes from either the DB or the Moodle
	 * Module Form Submit, and based on the data in that object returns
	 * the correct session type.
	 * 
	 * @param stdclass $rawSession
	 * @return Session Type - @see Elluminate_Session
	 */
	private function getSessionType($rawSession){
	   $sessionType = Elluminate_Session::COURSE_SESSION_TYPE;
	   if (property_exists($rawSession, 'sessiontype')){
	      $sessionType = $rawSession->sessiontype;
	   }
	   
	   if (property_exists($rawSession, 'restrictparticipants')){
	      if ($rawSession->restrictparticipants == true){
	         $sessionType = Elluminate_Session::PRIVATE_SESSION_TYPE;
	      }
	   }
	   return $sessionType;
	}
	
	private function isGroupMode($rawSession){
		$groupmode = 0;
		$isGroupMode = false;
		if (isset($rawSession->groupmode)){
			$groupmode = $rawSession->groupmode;
		}
		if ($groupmode){
			$isGroupMode = true;
		}	
		return $isGroupMode;
	}
	
	private function isGroupChild($rawSession){
		$isGroupChild = false;
		$groupParentId = 0;
		if (property_exists($rawSession, 'groupparentid')){
			$groupParentId = $rawSession->groupparentid;
		}
		if ($groupParentId != 0){
			$isGroupChild = true;
		}
		return $isGroupChild;
	}
	
	private function populateChildFromParent($parentSession, $childSession){
		$childSession->course = $parentSession->course;
		$childSession->creator = $parentSession->creator;
		$childSession->name = $parentSession->name;
		$childSession->sessionname = $parentSession->sessionname;
		$childSession->description = $parentSession->description;
		$childSession->intro = $parentSession->intro;
		$childSession->introformat = $parentSession->introformat;
		$childSession->customname = $parentSession->customname;
		$childSession->customdescription = $parentSession->customdescription;
		$childSession->timestart = $parentSession->timestart;
		$childSession->timeend = $parentSession->timeend;
		$childSession->recordingmode = $parentSession->recordingmode;
		$childSession->boundarytime = $parentSession->boundarytime;
		$childSession->boundarytimedisplay = $parentSession->boundarytimedisplay;
		$childSession->maxtalkers = $parentSession->maxtalkers;
		$childSession->chairlist = $parentSession->chairlist;
		$childSession->nonchairlist = $parentSession->nonchairlist;
		$childSession->grade = $parentSession->grade;
		$childSession->gradesession = $parentSession->gradesession;
		$childSession->timemodified = $parentSession->timemodified;
		$childSession->sessiontype = $parentSession->sessiontype;
		
		$childSession->groupingid = $parentSession->groupingid;
		$childSession->groupmode = $parentSession->groupmode;

		$childSession->section = $parentSession->section;
		
		//Now override specific values for the child
		$childSession->id = '';
		$childSession->groupparentid = $parentSession->id;
		$childSession->meetinginit = Elluminate_Session::MEETING_NOT_INIT;
		
		//Force override of this setting - doesn't apply to groups
		$childSession->restrictparticipants = false;
		$childSession->telephony = $parentSession->telephony;
		return $childSession;
	}
	
	/**
	 * Given a parent group session, try to load a child session.
	 * 
	 * Handle situations where child does not exist
	 * 
	 * @param unknown_type $loadedSession
	 * @param unknown_type $groupId
	 * @param unknown_type $courseModule
	 * @throws Elluminate_Exception
	 * @return unknown
	 */
	private function loadGroupChildSession($loadedSession, $groupId, $courseModule){
	   $returnSession = $loadedSession->getGroupChildSession($groupId);
	   	
	   //If return session is null, first do a check to make sure that
	   //a new group hasn't been added to the course.  If so, a new
	   //session is created and then returned.
	   
	   if ($returnSession == null){
	      $this->logger->debug("loadGroupChildSession return NULL");
	      $loadedSession->checkForNewGroups($courseModule);
	      $returnSession = $loadedSession->getGroupChildSession($groupId);
	   }
	   
	   //Now if we're null, we throw an error
	   if ($returnSession == null){
	      throw new Elluminate_Exception(get_string('groupiderror','elluminate'),
	                0 , get_string('groupiderror','elluminate',$groupId));
	   }
	   return $returnSession;
	}
}