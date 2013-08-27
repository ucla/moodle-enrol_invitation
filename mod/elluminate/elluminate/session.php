<?php

class Elluminate_Session {

   const USER_LIST_DELIM = ",";

   //Meeting Init Values
   const MEETING_NOT_INIT = 0;
   const MEETING_INIT_WAITING = 1;
   const MEETING_INIT_COMPLETE = 2;

   //Session Type
   const COURSE_SESSION_TYPE = 0;
   const PRIVATE_SESSION_TYPE = 1;
   const GROUP_SESSION_TYPE = 2;
   const GROUP_CHILD_SESSION_TYPE = 3;

   const MAX_TALKERS_DEFAULT = 1;
   const BOUNDARY_TIME_DEFAULT = 15;

   const MINSECS = 60;
   const MILLISECONDS = 1000;

   const GRADING_ENABLED = 1;
   const GRADING_DISABLED = 0;

   const NAME_MAX_LENGTH = 64;

   private $id;
   private $meetingid;
   private $meetinginit = self::MEETING_NOT_INIT;
   private $course;
   private $creator;
   private $sessiontype;
   private $name;
   private $sessionname;
   private $description;
   private $intro;
   private $introformat;
   private $customname = 0;
   private $customdescription = 0;
   private $timestart;
   private $timeend;
   private $recordingmode;
   private $boundarytime;
   private $boundarytimedisplay = 0;
   private $maxtalkers = 1;
   private $chairlist;
   private $nonchairlist;
   private $grade;
   private $gradesession = self::GRADING_DISABLED;
   private $telephony = 0;
   private $timemodified;

   //Group Specific Properties
   private $groupingid;
   private $groupmode;
   private $groupid = 0;
   private $groupparentid = 0;

   //These are data values that are loaded temporarily during session creation and edit
   //not stored in DB.
   private $section;
   private $mustbesupervised = false;
   private $raisehandonenter = false;
   private $permissionson = false;
   private $allmoderators = false;
   private $restrictparticipants = false;

   //Helper objects
   private $mypreload;
   private $sessionDAO;
   private $sessionServerManager;
   private $moodleDAO;
   private $sessionCalendar;
   private $sessionGrading;
   private $telephonyManager;
   private $preloadFactory;

   private $logger;

   //Groups
   protected $groupAPI;
   private $customNamingHelper;

   // ** GET/SET Magic Methods **
   public function __get($property) {
      if (property_exists($this, $property)) {
         return $this->$property;
      }
   }

   public function __set($property, $value) {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }

   // ** GET/SET Magic Methods **

   public function __construct() {
      $this->timemodified = time();
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Session");
   }

   /**
    * This function will handle the creation of a new session
    * by updating the SAS Server and the Database
    *
    * When this method is called for group mode, it will create a DB entry only
    * for a parent session and then call "createGroupSession" to take care of
    * creating child records.
    *
    * @throws Elluminate_Exception
    *
    */
   public function createSession() {
      $this->createServerSession();
      $this->saveNewSessionToDB();
   }

   /**
    * Just in Time Initialization for a session on SAS
    */
   public function initServerSession() {
      $this->createServerSession();
      $this->updateSessionInDB();
      $this->telephonyManager->toggleSessionTelephony($this->meetingid, $this->telephony);
   }

   public function isGroupSession() {
      return false;
   }

   /*
    * This method is designed to be called by the session loader after a session
    * is retrieved from the DB.  It should be used for any additional initialization
    * or setup required after a DB Load
    */
   public function loadSession() {
      //Nothing to do for regular sessions
   }

   /**
    * Save current session to DB as a new session
    * @param unknown_type $sessionid
    * @throws Elluminate_Exception
    */
   protected function saveNewSessionToDB() {
      $this->id = $this->sessionDAO->createSession($this);
   }

   /**
    * Wrap call to create a session and trap errors that may occur
    *
    * @param Elluminate_Session $createSession
    * @throws Elluminate_Exception
    */
   protected function createServerSession() {
      $this->sessionServerManager->createSession($this);
      $this->logger->info("Session Created on server successfully, meeting id = " . $this->meetingid);
      $this->meetinginit = $this::MEETING_INIT_COMPLETE;
   }

   /**
    * Delete Session from Server and DB
    * @throws Elluminate_Exception
    */
   public function deleteSession() {
      if ($this->meetingid != null) {
         $this->deleteServerSession();
      }
      $this->deleteDBSession();
      $this->sessionGrading->deleteGradeBook($this->id, $this->course);
      $this->telephonyManager->deleteSessionTelephony($this->meetingid);
   }

   public function setSessionInitStatus($status) {
      $this->timemodified = time();
      $this->meetinginit = $status;
      $this->updateSessionInDB();
   }

   /**
    * Delete Session from DB
    *
    * @throws Elluminate_Exception
    */
   protected function deleteDBSession() {
      $this->sessionDAO->deleteSession($this);
      $this->logger->info("Session DB Delete Success [" . $this->meetingid . "]");
   }

   /**
    * Make a service call to delete session
    *
    * @throws Elluminate_Exception
    */
   protected function deleteServerSession() {
      $this->sessionServerManager->deleteSession($this->meetingid);
      $this->logger->info("Session Server Delete Success [" . $this->meetingid . "]");
   }

   /**
    * Update this session by making a call to the server and
    * if success save to the DB.
    *
    * @throws Elluminate_Exception
    */
   public function updateSession() {
      $this->logger->debug("Elluminate_Session: updateSession");
      $this->updateSessionOnServer();
      $this->updateSessionInDB();
      $this->logger->debug("updateSession complete, id = " . $this->id);
   }

   public function switchSessionToGroupMode($newGroupMode) {
      $this->sessiontype = Elluminate_Session::GROUP_SESSION_TYPE;
      $this->groupmode = $newGroupMode;
      $this->timemodified = time();

      //If this is a child group session, we need to pass in the parent ID to
      //get the correct course module
      if ($this->getSessionType() == Elluminate_Group_Session::GROUP_CHILD_SESSION_TYPE) {
         $switchid = $this->groupparentid;
      } else {
         $switchid = $this->id;
      }
      $this->moodleDAO->switchCourseModuleToGroupMode($switchid, $this->groupmode);
      $this->updateSessionInDB();
   }

   public function getSessionType() {
      return $this->sessiontype;
   }

   /**
    * Update current session to DB
    * @param unknown_type $sessionid
    * @throws Elluminate_Exception
    */
   protected function updateSessionInDB() {
      $this->sessionDAO->updateSession($this);
   }

   /**
    * Invoke interface to update session, trap errors
    *
    * @param Elluminate_Session $updateSession
    * @throws Elluminate_Exception
    */
   protected function updateSessionOnServer() {
      $this->sessionServerManager->updateSession($this);
      $this->logger->info("Session Server Update Successful for : " . $this->meetingid);
   }

   /**
    * Users for this session have been updated, make a call to the server
    * and update in the DB.
    *
    * @throws Elluminate_Exception
    *
    */
   public function updateSessionUsers() {
      $this->updateSessionUsersOnServer();
      $this->updateSessionInDB();
   }

   /**
    * Update Users for this session on the server
    *
    * @throws Elluminate_Exception
    */
   public function updateSessionUsersOnServer() {
      $this->sessionServerManager->updateUsers($this);
      $this->logger->debug("Update Users for Session " . $this->id . ", server success");
   }

   /**
    * Check for a preload in the DB.
    *
    * If exists, set member variable and return true
    *
    */
   public function checkForPreloads() {
      $hasPreload = false;

      //MOOD-462 - no preloads can exist if meeting not
      //initialized
      if ($this->meetinginit == self::MEETING_NOT_INIT ||
         $this->meetingid == null
      ) {
         return $hasPreload;
      }

      $preload = $this->preloadFactory->getPreload();
      $preload->loadPreloadForSession($this);
      if ($preload->id) {
         $this->mypreload = $preload;
         $hasPreload = true;
      }
      return $hasPreload;
   }

   public function getPreload() {
      return $this->mypreload;
   }

   /**
    * Based on session type, determine if session is course type
    *
    * @return boolean
    */
   public function isCourseSession() {
      if ($this->sessiontype == self::COURSE_SESSION_TYPE) {
         return TRUE;
      } else {
         return FALSE;
      }
   }

   /**
    * Based on session type, determine if session is private type
    *
    * @return boolean
    */
   public function isPrivateSession() {
      if ($this->sessiontype == self::PRIVATE_SESSION_TYPE) {
         return TRUE;
      } else {
         return FALSE;
      }
   }

   /**
    * These 3 synch methods are required in the scenario where moodle has locked a gradebook entry.
    *
    * When locked, updates to attendance won't be reflected in the gradebook.
    *
    * If that grade is then unlocked, we need to set the gradebook entry to match the attendance entry
    *
    */
   public function synchSessionGradeBook() {
      $this->sessionGrading->addSessionGradeBook($this->id,
         $this->sessionname,
         $this->course,
         $this->grade);
   }

   public function synchSessionGradeBookForUser($userid) {
      $this->sessionGrading->synchGradeBookForUser($this->id,
         $this->course,
         $this->sessionname,
         $this->grade, $userid);
   }

   public function synchSessionGradeBookForAllUsers() {
      $this->sessionGrading->synchGradeBookForAllUsers($this->id, $this->course,
         $this->sessionname, $this->grade);
   }

   /**
    * Log Attendance for a particular user for this session
    * @param unknown_type $userId
    */
   public function logAttendance($userId) {
      $this->sessionGrading->logSessionAttendance($this->id, $userId, $this->grade);
   }

   /**
    * Log Grade Book Grades for a particular user for this session
    * @param unknown_type $userId
    */
   public function logToGradeBook($userId) {
      $this->logger->debug("logToGradeBook " . $this->sessionname);
      $this->sessionGrading->logToGradeBook($this->id,
         $this->course, $this->sessionname, $this->grade, $userId);
   }

   public function getAttendeeCount() {
      return $this->sessionGrading->getAttendeeCount($this->id);
   }

   /**
    * Get the duration of the current session
    *
    * @return integer - duration of meeting in milliseconds
    */
   public function getSessionDuration() {
      return max($this->timeend - $this->timestart, 0);
   }

   /**
    * Given a user ID, check if that user is a part of this session
    * as EITHER a moderator or a participant
    *
    * @param String $userToCheck
    * @return boolean
    */
   public function isUserInMeeting($userToCheck) {
      $result = FALSE;
      if ($this->isUserModerator($userToCheck) ||
         $this->isUserParticipant($userToCheck)
      ) {
         $result = TRUE;
      }
      return $result;
   }

   /**
    * Given a user ID, check if that user is a MODERATOR in this session
    *
    * @param $userIdToCheck
    * @return boolean
    */
   public function isUserModerator($userIdToCheck) {
      $returnValue = FALSE;
      $chairList = explode(self::USER_LIST_DELIM, $this->chairlist);
      foreach ($chairList as $user) {
         if ($userIdToCheck == $user) {
            $returnValue = TRUE;
            break;
         }
      }
      return $returnValue;
   }

   /**
    * Given a user ID, check if that user is a PARTICIPANT in this session
    *
    * @param $userIdToCheck
    * @return boolean
    */
   public function isUserParticipant($userIdToCheck) {
      $returnValue = FALSE;
      $nonChairList = explode(self::USER_LIST_DELIM, $this->nonchairlist);
      foreach ($nonChairList as $user) {
         if ($userIdToCheck == $user) {
            $returnValue = TRUE;
            break;
         }
      }
      return $returnValue;
   }

   /**
    * Get the list of moderators as an Array object
    *
    * @return multitype:
    */
   public function getModeratorArray() {
      if ($this->chairlist != '' && $this->chairlist != null) {
         return explode(self::USER_LIST_DELIM, $this->chairlist);
      } else {
         return array();
      }
   }

   /**
    * Get the list of moderators as an Array object, without
    * the meeting creator
    *
    * @return multitype:
    */
   public function getModeratorArrayWithoutCreator() {
      $moderatorArray = array();
      foreach (explode(self::USER_LIST_DELIM, $this->chairlist) as $userid) {
         if ($userid != $this->creator) {
            $moderatorArray[] = $userid;
         }
      }
      return $moderatorArray;
   }

   public function getModeratorCount() {
      return sizeof($this->getModeratorArray());
   }

   /**
    * Get the list of participants as an Array object
    *
    * @return multitype:
    */
   public function getParticipantArray() {
      if ($this->nonchairlist != '' && $this->nonchairlist != null) {
         return explode(self::USER_LIST_DELIM, $this->nonchairlist);
      } else {
         return array();
      }
   }

   public function getParticipantCount() {
      return sizeof($this->getParticipantArray());
   }

   /**
    * Get a complete list of all users in the session - moderators AND participants
    * @return multitype:
    */
   public function getAllUsersArray() {
      return array_merge($this->getModeratorArray(), $this->getParticipantArray());
   }

   /**
    * Add a user id to the chairlist for this session
    *
    * @param String $userId
    */
   public function addModerator($userId) {
      //Don't add if already exists
      if (!$this->isUserModerator($userId)) {
         if (!empty($this->chairlist)) {
            $this->chairlist = $this->chairlist . $this::USER_LIST_DELIM . $userId;
         } else {
            $this->chairlist = $userId;
         }
      }
   }

   /**
    * Add a user id to the non-chairlist (participants) for this session
    *
    * @param String $userId
    */
   public function addParticipant($userId) {
      //Don't add if already exists
      if (!$this->isUserParticipant($userId)) {
         if (!empty($this->nonchairlist)) {
            $this->nonchairlist = $this->nonchairlist . $this::USER_LIST_DELIM . $userId;
         } else {
            $this->nonchairlist = $userId;
         }
      }
   }

   /**
    * Remove a given moderator user id from the chairlist for this session
    *
    * @param String $userId
    */
   public function removeModerator($userId) {
      $this->chairlist = implode($this::USER_LIST_DELIM,
         $this->removeUserFromList($userId, $this->getModeratorArray()));
   }

   /**
    * Remove a given participant id from the nonchairlist for this session
    *
    * @param String $userId
    */
   public function removeParticipant($userId) {
      $this->nonchairlist = implode($this::USER_LIST_DELIM,
         $this->removeUserFromList($userId, $this->getParticipantArray()));
   }

   /**
    * Get the Moodle User Record for the Creator of the Session
    * NOTE: This is the full user record, not the user id, which
    * is stored in the ->creatorid member variable.
    */
   public function getSessionCreator() {
      return $this->sessionDAO->getSessionCreator($this->creator);
   }

   /**
    * Returns an array of user objects for all current moderators in the session
    *
    * The creator of the meeting is a special moderator that cannot be managed,
    * so they are removed from this list.
    */
   public function getModeratorUserListWithoutCreator() {
      return $this->moodleDAO->loadUserList($this->getModeratorArrayWithoutCreator());
   }

   /**
    * Returns an array of Moodle user objects for all current participants in the session
    */
   public function getParticipantList() {
      return $this->moodleDAO->loadUserList($this->getParticipantArray());
   }

   /**
    * Call SAS to get the session launch URL and return
    * @return Session JNLP launch URL
    *
    */
   public function getLaunchURL($user) {
      $this->logger->debug("getLaunchURL");
      $sessionURL = $this->sessionServerManager->getSessionURL($this->meetingid,
         $user->displayname, $this->getLaunchURLUserID($user->id));
      $this->logger->info("getLaunchURL(), id = " . $this->id . ", url = " . $sessionURL);
      return $sessionURL;
   }

   /**
    * Get a user record from the database and format for the launching of a session.
    *
    * Remove special characters:
    * - at beginning of name only (makes Collaborate Crash!)
    * %# anywhere in name
    * SAS API states $ and " need to be removed, but testing shows these are acceptable.
    *
    * When making changes here, be aware that names need to support non-latin character
    * sets (i.e. arabic) and any regex formatting needs to consider that.
    *
    * @return unknown
    */
   public function getSessionLaunchUser($userid) {
      $user = $this->moodleDAO->getMoodleUserRecord($userid);
      $strippedName = $user->displayname;
      $strippedName = preg_replace('/^[-]+/', '', $strippedName);
      $strippedName = preg_replace('/[%#]+/', '', $strippedName);
      $this->logger->debug("formatDisplayName result: " . $strippedName);
      $user->displayname = $strippedName;
      return $user;
   }

   /**
    * Based on meeting start time - boundary time and current time, has meeting started yet?
    *
    * @return boolean
    */
   public function hasSessionStarted() {
      $timenow = time();
      return $this->timestart - $this->getBoundaryTimeSeconds() <= $timenow;
   }

   /**
    * Based on meeting end time and current time, has meeting ended?
    *
    * @return boolean
    */
   public function hasSessionEnded() {
      $timenow = time();
      return $this->timeend < $timenow;
   }

   public function isSessionInProgress() {
      if ($this->hasSessionStarted() && (!$this->hasSessionEnded())) {
         return true;
      } else {
         return false;
      }
   }

   /**
    * Get the value of boundary time in seconds (stored in DB as minutes)
    *
    * @return number
    */
   public function getBoundaryTimeSeconds() {
      return $this->boundarytime * $this::MINSECS;
   }

   public function getBoundaryTimeRealTime() {
      $boundaryTimeMilliSeconds = $this->getBoundaryTimeSeconds();
      $boundaryStartTime = $this->timestart - $boundaryTimeMilliSeconds;
      return $boundaryStartTime;
   }

   /**
    * Helper function to return a StdClass version of this object
    * to allow public access on all member variables.
    *
    * This is required by the $DB moodle object to do DB operations
    * with the object
    *
    * @return StdClass
    */
   public function getDBInsertObject() {
      return get_object_vars($this);
   }

   /**
    * Helper function to remove a user ID from an array of user IDs
    *
    * @param $userId
    * @param  $userList
    */
   private function removeUserFromList($removeUserId, $userList) {
      $shortenedArray = Array();
      foreach ($userList as $userid) {
         if ($userid != $removeUserId) {
            $shortenedArray[] = $userid;
         }
      }
      return $shortenedArray;
   }

   /**
    * Add a private calendar event for a particular user and this session
    *
    * This will only be valid if this session is private
    *
    * @param unknown_type $userid
    */
   public function addPrivateUserCalendarEvent($userid) {
      if ($this->isPrivateSession()) {
         $this->logger->debug("Adding Private Calendar Event, Session: " . $this->id . " user " . $userid);
         $this->sessionCalendar->addPrivateUserEvent($this, $userid);
      }
   }

   /**
    * Delete a private calendar event for a particular user and this session
    *
    * This will only be valid if this session is private
    *
    * @param unknown_type $userid
    */
   public function deletePrivateUserCalendarEvent($userid) {
      if ($this->isPrivateSession()) {
         $this->logger->debug("Removing Private Calendar Event, Session: " . $this->id . " user " . $userid);
         $this->sessionCalendar->deletePrivateUserEvent($this, $userid);
      }
   }

   public function isSessionGradeScaled() {
      if ($this->grade < 0) {
         return true;
      } else {
         return false;
      }
   }

   public function isSessionGradeNumeric() {
      if ($this->grade >= 0) {
         return true;
      } else {
         return false;
      }
   }

   /**
    * Get a listing of all fields in this object
    *
    * This is a bit of a hack to make testing easier.  With so many
    * member variables in this object, it's easier to be able to
    * test by creating mock objects programmatically using this
    * method and then comparing the values to toString()
    *
    * @return multitype:
    */
   public function getFields() {
      $fields = array();
      foreach ($this as $var => $value) {
         $fields[] = $var;
      }
      return $fields;
   }

   /**
    * return a string representation of this object for logging
    * and testing
    */
   public function __toString() {
      return "id = " . $this->id + ", name = " + $this->sessionname;
   }

   /**
    * when getting the URL to launch a SAS session, we want to send a user id in these scenarios:
    *
    * 1.) Session is Private - in this case we want to enforce checking of user ID against the
    * nonchairlist before letting them in.
    * 2.) User is moderator.  In this case, we need to pass user ID to make sure the user is granted
    * the correct permissions when joining the session.
    *
    * In all other cases, we pass in a blank user ID and the user's display name.
    *
    */
   public function getLaunchURLUserID($userid) {
      $processedUserID = '';
      if ($this->isPrivateSession()) {
         $processedUserID = $userid;
      }

      if ($this->isUserModerator($userid)) {
         $processedUserID = $userid;
      }
      return $processedUserID;
   }
}
