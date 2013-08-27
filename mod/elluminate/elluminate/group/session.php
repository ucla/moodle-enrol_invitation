<?php

class Elluminate_Group_Session extends Elluminate_Session {
   const GROUP_MODE_NONE = 0;
   const GROUP_MODE_PRIVATE = 1;
   const GROUP_MODE_VISIBLE = 2;

   private $childSessions;

   private $groupList;
   private $groupNameList;

   //Helpers and Dependencies
   private $groupDAO;

   private $logger;

   private $sessionLoader;

   public function __construct() {
      $this->childSessions = array();
      parent::__construct();
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Group_Session");
   }

   // ** GET/SET Magic Methods **
   public function __get($property) {
      if (property_exists($this, $property)) {
         return $this->$property;
      } else {
         return parent::__get($property);
      }
   }

   public function __set($property, $value) {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      } else {
         parent::__set($property, $value);
      }
      return $this;
   }

   // ** GET/SET Magic Methods **

   /**
    * Create a new Session - called by elluminate_add_instance
    *
    * @see Elluminate_Session::createSession()
    */
   public function createSession() {
      $this->createServerSession();
      $this->saveNewSessionToDB();
      $this->createChildSessions();
   }

   /*
    * This method is designed to be called by the session loader after a session
   * is retrieved from the DB.  It should be used for any additional initialization
   * or setup required after a DB Load
   *
   * In the case of a group session, this will populate the child group sessions.
   */
   public function loadSession() {
      $this->loadChildSessionsFromDatabase();
   }

   /**
    * Group Sessions are only updated on SAS if they've actually been created there, since group sessions
    * are created on the fly.
    *
    * @throws Elluminate_Exception
    */
   public function updateSession() {
      $this->logger->debug("Elluminate_Group_Session: updateSession");
      if ($this->meetinginit == Elluminate_Session::MEETING_INIT_COMPLETE) {
         $this->logger->debug("Scheduling Manager Group Session Update for: " . $this->id);
         $this->updateSessionOnServer();
      }
      $this->updateSessionInDB();
      $this->updateGroupChildren();
   }

   /**
    * Delete Session from Server and DB
    *
    * Note that Moodle takes care of deleting all related grading and calendar entries
    *
    * @throws Elluminate_Exception
    */
   public function deleteSession() {
      $this->logger->debug("deleteSession id : " . $this->id);
      if ($this->meetinginit == Elluminate_Session::MEETING_INIT_COMPLETE) {
         $this->deleteServerSession();
      }
      $this->deleteDBSession();

      $this->deleteGroupChildSession();
   }

   /**
    * Given a group ID will return that specific child session.
    *
    * @param unknown_type $groupId
    * @throws Elluminate_Exception
    * @return unknown
    */
   public function getGroupChildSession($groupId) {
      if (isset($this->childSessions[$groupId])) {
         $childSession = $this->childSessions[$groupId];
      } else {
         $childSession = null;
      }
      return $childSession;
   }

   public function switchSessionToNonGroupMode() {
      //Parent Session
      $this->sessiontype = Elluminate_Session::COURSE_SESSION_TYPE;
      $this->groupmode = 0;
      $this->timemodified = time();
      $this->updateSessionInDB();

      //Child Sessions
      $this->switchChildrenToNonGroupSessions();
   }

   public function loadGroupsForSession() {
      if ($this->groupingid) {
         $groupList = $this->groupAPI->getAllGroupsForGrouping($this->course, $this->groupingid);
      } else {
         $groupList = $this->groupAPI->getAllGroups($this->course);
      }
      return $groupList;
   }

   private function switchChildrenToNonGroupSessions() {
      foreach ($this->childSessions as $childSession) {
         $this->logger->debug("Converting Child Session: " . $childSession->id . " to non-group.");
         $childSession->switchSessionToNonGroupMode();
      }
   }

   private function updateGroupChildren() {
      foreach ($this->childSessions as $childSession) {
         $childSession->updateSession();
      }
   }

   /**
    * Call the DAO to load child sessions from DB, then
    * populate Session objects and store in array.
    */
   private function loadChildSessionsFromDatabase() {
      $rawSessions = $this->groupDAO->getChildSessions($this);
      foreach ($rawSessions as $rawSession) {
         $childSession = $this->sessionLoader->loadSessionFromDatabaseObject($rawSession);
         $this->logger->debug("Loaded Child Session [" . $childSession->id .
         "], group [" . $childSession->groupid . "]");
         $this->childSessions[$childSession->groupid] = $childSession;
      }
   }

   /**
    * Parent Group Session is being deleted, clear away all child record
    */
   private function deleteGroupChildSession() {
      foreach ($this->childSessions as $childSession) {
         $this->logger->debug("Deleting Child Session id: " . $childSession->id);
         $childSession->deleteSession();
      }
   }

   /**
    * Invoke the Groups API to get a listing of groups for the current session.
    * @param unknown_type $mode
    */
   private function loadChildGroupsForCreate() {
      $groupsExist = false;
      if ($this->groupingid > 0) {
         $this->groupList = $this->groupAPI->getAllGroupsForGrouping($this->course, $this->groupingid);
      } else {
         $this->groupList = $this->groupAPI->getAllGroups($this->course);
      }
      if ($this->groupList != null) {
         $this->buildGroupNameList();
         $groupsExist = true;
      }
      return $groupsExist;
   }

   public function getSessionType() {
      return Elluminate_Session::GROUP_SESSION_TYPE;
   }

   public function isGroupSession() {
      return true;
   }

   /**
    * After loading a group session from the DB, it is necessary to
    * do a check if any new groups have been added since the initial creation.
    * If so, new sessions will be added to the DB and the active child session list.
    *
    * We do lazy loading on here by using the current context to get only groups
    * available to the current user.
    *
    * A note about deleted groups:
    *  We don't take any action here for deleted groups.  The only real logic that
    *  could be done is to delete a session if the group is deleted.  However, that
    *  session may have recordings, etc associated to it that would need to be adjusted.
    *  So, a session for a deleted group will remain in the DB.  This session becomes
    *  no longer accessible without using a direct url link.
    *
    *  Also, there is no concern about deleting groups leading to no groups
    *  existing anymore.  Moodle always considers a default group to be
    *  "All Participants"
    */
   public function checkForNewGroups($context) {
      $this->logger->debug("checkForGroupUpdates for Session ID [" . $this->id . "]");
      $groupList = $this->loadAvailableGroups($context);

      foreach ($groupList as $group) {
         if (!array_key_exists($group->id, $this->childSessions)) {
            $this->logger->debug("New Group [" . $group->id . "]");
            $childSession = $this->createChildSessionForGroup($group);
            $this->childSessions[$group->id] = $childSession;
         }
      }
   }

   private function loadAvailableGroups($context) {
      return $this->groupAPI->getAvailableGroups($context);
   }

   /*
    * Create the child sessions for each group that currently exists for the course that
   * the user is working with.
   */
   private function createChildSessions() {
      if ($this->loadChildGroupsForCreate())
         foreach ($this->groupList as $group) {
            $childSession = $this->createChildSessionForGroup($group);
            $this->childSessions[$group->id] = $childSession;
         }
   }

   private function createChildSessionForGroup($group) {
      $this->logger->debug("createChildSessionForGroup, Session ID: " . $this->id . " Group ID: " . $group->id);
      $childSession = $this->sessionLoader->loadChildSessionFromParent($this);
      $childSession->createChildSession($group->id, $group->name);
      return $childSession;
   }

   private function buildGroupNameList() {
      $groupNames = array();
      foreach ($this->groupList as $group) {
         $groupNames[$group->id] = format_string($group->name);
      }
      $this->groupNameList = $groupNames;
   }
}