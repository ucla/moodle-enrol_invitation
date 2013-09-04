<?php
class Elluminate_Session_Users {

   const NO_USERS = null;
   const EMPTY_USER_LIST = '';
   const USER_LIST_DELIM = ",";

   private $targetSession;
   private $moodleDAO;
   private $currentContext;
   private $logger;

   public function __construct() {
      $this->moodleDAO = new Elluminate_Moodle_DAO();
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Session_Users");
   }

   public function init($session, $context) {
      $this->targetSession = $session;
      $this->currentContext = $context;
   }

   public function setMoodleDAO($moodleDAO) {
      $this->moodleDAO = $moodleDAO;
   }

   /**
    * This function will return a list of participants (non-moderators) only for a given session.
    *
    * There are three scenarios governing this:
    *
    * 1. Private Session - participants are explicitly defined in nonchairlist are the only ones returned
    *
    * 2. Course Session - participants are determined based on anyone enrolled in the
    *    course, but users who have a session moderator role are excluded.
    *
    * 3. Group -??
    *
    * @return array
    */
   public function getSessionParticipantList() {
      if ($this->targetSession->isPrivateSession()) {
         if ($this->targetSession->nonchairlist != $this::EMPTY_USER_LIST) {
            return $this->moodleDAO->loadUserList($this->getParticipantArray());
         } else {
            return $this::NO_USERS;
         }
      }

      $allUserList = $this->moodleDAO->getAllCourseUsers($this->targetSession->course);
      $this->logger->debug("All User List, size = " . sizeof($allUserList));
      return $this->filterModeratorsFromUserList($allUserList);
   }

   /**
    * Get an Array of all users who are eligible to be participants for a
    * session
    *
    * Uses the moodle built in function to get users by capability, and then
    * removes any users from that list who are already present because they are
    * currently participants in the meeting.
    *
    * @param unknown_type $existingUserList
    * @return Array $availableParticipants
    */
   public function getAvailableParticipants() {
      $allParticipants = $this->moodleDAO->getAllCourseUsers($this->targetSession->course);
      return $this->filterUserList($allParticipants, $this->targetSession->getAllUsersArray());
   }

   /**
    * Get an Array of all users who are eligible to be moderators for this
    * session
    *
    * Uses the moodle built in function to get users by capability, and then
    * removes any users from that list who are already present because they are
    * currently participants in the meeting.
    *
    * @return Array $availableModerators
    */
   public function getAvailableModerators() {
      $allModerators = $this->moodleDAO->getCourseModerators($this->currentContext);
      if (sizeof($allModerators) > 0) {
         return $this->filterUserList($allModerators, $this->targetSession->getAllUsersArray());
      } else {
         return null;
      }
   }

   /**
    * After moodle has returned a list of eligible users to participate or moderate a session,
    * we need to remove the existing users from that list so there aren't duplicates
    *
    *
    *
    * @param array $sourceUserList - this should be an array keyed by user ID containing USER objects
    *                                as the value.  The key will be used to determine duplicates.
    *
    * @param array $usersToFilter - this should be a simple array of keys that should be filtered out only
    *                               DO NOT pass in an array of full user objects in this parameter
    *
    * @return array - copy of original source user list array with ids from second parameter filtered out.
    */
   private function filterUserList($sourceUserList, $usersToFilter) {
      $availableUserIds = array_keys($sourceUserList);

      $userDifferences = array_diff($availableUserIds, $usersToFilter);
      $filteredUserList = array();
      foreach ($userDifferences as $uid) {
         $filteredUserList[$uid] = $sourceUserList[$uid];
      }
      return $filteredUserList;
   }

   private function filterModeratorsFromUserList($userList) {
      $moderators = $this->getModerators();
      $filteredUserList = array();
      if (!empty($moderators) && $moderators != null && $userList != null) {
         $filteredUserList = $this->filterUserList($userList, array_keys($moderators));
         $this->logger->debug("filterModeratorsFromUserList, return size: " . sizeof($filteredUserList));
      } else {
         $filteredUserList = $userList;
      }
      return $filteredUserList;
   }

   private function getModerators() {
      $moderators = $this->moodleDAO->getCourseModerators($this->currentContext);
      $this->logger->debug("Moderator List, size = " . sizeof($moderators));
      return $moderators;
   }

   private function getParticipantArray() {
      return explode(self::USER_LIST_DELIM, $this->targetSession->nonchairlist);
   }
}