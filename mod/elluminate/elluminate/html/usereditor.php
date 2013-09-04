<?php
class Elluminate_HTML_UserEditor {

   const MODERATOR_EDIT_MODE = 1;
   const PARTICIPANT_EDIT_MODE = 2;

   const GUEST_USER = 0;

   const USER_LIST_DELIM = ",";

   private $userEditType;
   private $mySession;
   private $context;

   private $currentUserList;
   private $availableUserList;

   private $formCurrentUserList;
   private $formAvailableUserList;
   private $submitMode;

   private $sessionUserHelper;

   private $logger;

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
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_HTML_UserEditor");
   }

   /**
    * Based on the editing type, load the current/available user lists
    *
    * @return StdClass - object containing all of the above lists
    */
   public function loadUserLists() {
      $this->sessionUserHelper->init($this->mySession, $this->context);
      if ($this->userEditType == $this::MODERATOR_EDIT_MODE) {
         $this->currentUserList = $this->mySession->getModeratorUserListWithoutCreator();
         $this->availableUserList = $this->sessionUserHelper->getAvailableModerators();
      } else {
         $this->currentUserList = $this->mySession->getParticipantList();
         $this->availableUserList = $this->sessionUserHelper->getAvailableParticipants();
      }
   }

   /**
    * Handle a submission of the moderator form to change users
    *
    * @param $data - form submission
    */
   public function handleSubmit($data) {
      $notice = '';

      try {
         //Clean data and setup member variables
         $this->cleanSubmitData($data);

         //Keep a copy of original lists in the case of an error
         $originalChairList = $this->mySession->chairlist;
         $originalNonChairList = $this->mySession->nonchairlist;

         $usersUpdated = $this->handleAddRemove();

         if ($usersUpdated){
            $this->mySession->updateSessionUsers();
         }else{
            $notice = get_string('user_edit_invalid_selection', 'elluminate');
         }
      } catch (Elluminate_Exception $e) {
         $this->logger->debug("ADD ERROR = " . $this->getAddRemoveErrorKey($this->submitMode));
         $notice = get_string($this->getAddRemoveErrorKey($this->submitMode), 'elluminate') .
            ': ' . get_string($e->getUserMessage(), 'elluminate');
         $this->logger->debug("notice = " . $notice);
         //Roll back user lists for session
         $this->mySession->chairlist = $originalChairList;
         $this->mySession->nonchairlist = $originalNonChairList;
      }
      return $notice;
   }

   public function getPageTitle() {
      $pageTitle = '';
      if ($this->userEditType == self::MODERATOR_EDIT_MODE) {
         $pageTitle = get_string('editingmoderators', 'elluminate');
      } else if ($this->userEditType == self::PARTICIPANT_EDIT_MODE) {
         $pageTitle = get_string('editingparticipants', 'elluminate');
      }
      return $pageTitle;
   }

   /**
    * Make sure all the submitted data matches the expected data types
    */
   private function cleanSubmitData($data) {
      if (isset($data->availableUsers)) {
         $this->formAvailableUserList = clean_param_array($data->availableUsers, PARAM_INT);
      }
      if (isset($data->currentUsers)) {
         $this->formCurrentUserList = clean_param_array($data->currentUsers, PARAM_INT);
      }
      if (isset($data->submitvalue)) {
         $this->submitMode = clean_param($data->submitvalue, PARAM_ALPHA);
      }
   }

   /**
    * Get the correct error key based on if users are being added or removed
    * @param $mode
    * @return string
    */
   private function getAddRemoveErrorKey() {
      if ($this->submitMode == 'add') {
         return 'couldnotadduserstosession';
      } else {
         return 'couldnotremoveusersfromsession';
      }
   }

   /**
    * Given the submitted form data, either add or remove users from the
    * user lists for the related session.
    *
    * @return boolean user lists have been updated true/false
    */
   private function handleAddRemove() {
      $this->logger->debug("handleAddRemove" . $this->submitMode);
      if ($this->submitMode == 'add') {
         return $this->handleUserAdd();
      }

      if ($this->submitMode == 'remove') {
         return $this->handleUserRemove();
      }
   }

   /**
    * Handle User Edit Form submit with action of "add"
    *
    * $data-availableUsers[] contains the list of current users from the form submit
    *
    * This array can contain a single value or multiple values, depending on if the
    * user has done a multi-select on the form.
    */
   private function handleUserAdd() {
      $userEdit = false;
      $this->logger->debug("handleUserAdd start");

      //This is most likely caused by user clicking "add" with nothing selected in the available user list
      if (!isset($this->formAvailableUserList)) {
         return $userEdit;
      }

      foreach ($this->formAvailableUserList as $userId) {
         if ($userId == self::GUEST_USER){
            continue;
         }
         if ($this->userEditType == $this::MODERATOR_EDIT_MODE) {
            $this->mySession->addModerator($userId);
         } else {
            $this->mySession->addParticipant($userId);
         }
         $this->mySession->addPrivateUserCalendarEvent($userId);
         $userEdit = true;
      }
      return $userEdit;
   }

   /**
    * Handle User Edit Form submit with action of "remove"
    *
    * $data-currentUsers[] contains the list of current users from the form submit
    */
   private function handleUserRemove() {
      $this->logger->debug("handleUserRemove start");
      $userEdit = false;

      //This is most likely caused by user clicking "remove" with nothing selected in the current user list
      if (!isset($this->formCurrentUserList)) {
         return $userEdit;
      }

      foreach ($this->formCurrentUserList as $userId) {
         if ($userId == self::GUEST_USER){
            continue;
         }
         if ($this->userEditType == $this::MODERATOR_EDIT_MODE) {
            $this->mySession->removeModerator($userId);
         } else {
            $this->mySession->removeParticipant($userId);
         }
         $this->mySession->deletePrivateUserCalendarEvent($userId);
         $userEdit = true;
      }
      return $userEdit;
   }

   /**
    * Get the HTML select element snippet for current users
    */
   public function getCurrentUserSelectHTML() {
      return $this->getUserSelectHTML($this->currentUserList);
   }

   /**
    * Get the HTML select element snippet for available users
    */
   public function getAvailableUserSelectHTML() {
      return $this->getUserSelectHTML($this->availableUserList);
   }

   /**
    * Build the HTML snippet for the given user list
    *
    * @return String $userListHTML
    */
   public function getUserSelectHTML($userList) {
      $userListHTML = '';

      if (!empty($userList)) {
         foreach ($userList as $currentUser) {
            $userListHTML .= '<option value="' . $currentUser->id . '">';
            $userListHTML .= fullname($currentUser) . ' (' . format_string($currentUser->username) . ')';
            $userListHTML .= "</option> \n";
         }
      }
      return $userListHTML;
   }

   /**
    * Get the localization key for the HTML title element for the current users list
    */
   public function getCurrentUserString() {
      if ($this->userEditType == $this::MODERATOR_EDIT_MODE) {
         return get_string('existingmoderators', 'elluminate', sizeof($this->currentUserList));
      } else {
         return get_string('existingparticipants', 'elluminate', sizeof($this->currentUserList));
      }
   }

   /**
    * Get the localization key for the HTML title element for the available users list
    */
   public function getAvailableUserString() {
      if ($this->userEditType == $this::MODERATOR_EDIT_MODE) {
         return get_string('availablemoderators', 'elluminate', sizeof($this->availableUserList));
      } else {
         return get_string('availableparticipants', 'elluminate', sizeof($this->availableUserList));
      }
   }

   /**
    * Get the localization string for the HTML add buttom
    */
   public function getAddButtonString() {
      if ($this->userEditType == $this::MODERATOR_EDIT_MODE) {
         return get_string('addmoderators', 'elluminate');
      } else {
         return get_string('addparticipants', 'elluminate');
      }
   }

   /**
    * Get the localization string for the HTML remove button
    */
   public function getRemoveButtonString() {
      if ($this->userEditType == $this::MODERATOR_EDIT_MODE) {
         return get_string('removemoderators', 'elluminate');
      } else {
         return get_string('removeparticipants', 'elluminate');
      }
   }
}