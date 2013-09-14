<?php

class Elluminate_Session_Validator implements Elluminate_Validation_Validator {
   const VALIDATION_SUCCESS = TRUE;
   const YEAR_IN_SECONDS = 31536000;
   const GROUP_NAME_ERROR = "groupname_";

   //Moodle output helper
   private $moodleOutput;

   private $logger;

   public $SESSION_NAME_SPECIAL_CHARS = Array("<", "&", "\"", "#", "%");
   public $NON_ALPHANUMERIC = Array(".", "<", ">", ",", "(", ")",
      "?", "!", "@", "#", "$", "&", "%", "*", "-", "_", "+", "=", "[", "]", "{", "}", "\\", "|", ":");


   public function __construct() {
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Session_Validator");
   }

   public function __set($property, $value) {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }

   public function validate($checkSession, $existingSession = null) {
      $this->checkStartTimeGreaterThanEndTime($checkSession->timestart,
         $checkSession->timeend);

      $this->checkStartTimeEqualToEndTime($checkSession->timestart,
         $checkSession->timeend);

      if ($this->checkUpdateModeStartTimeChanged($checkSession, $existingSession)) {
         $this->checkStartTimeInPast($checkSession->timestart);
      }

      $this->checkLengthLessThanYear($checkSession->getSessionDuration());

      $this->checkStartTimeLessThanYear($checkSession->timestart);

      $this->validateName($checkSession);

      if ($checkSession->isGroupSession()) {
         $this->validateChildNames($checkSession);
      }
   }

   /**
    * This method is used in two ways:
    * 1.) During standard validation of a group session during create/update
    * 2.) As a standalone method from Elluminate_Group_CustomNaming during an on-the-fly group child session creation
    *
    * @param $groupSessionName
    * @throws Elluminate_Exception
    * @return Formatted, Validated Custom Group Session Name
    */
   public function validateCustomGroupName($groupSessionName) {
      $fmtSessionName = $this->replaceSessionNameSpecialChars($groupSessionName);
      $this->checkNameNotEmpty($fmtSessionName);
      $this->checkFirstCharacterAlphanumeric($fmtSessionName);
      return $fmtSessionName;
   }

   private function validateName($checkSession) {
      $originalName = $checkSession->sessionname;
      $checkSession->sessionname =
         $this->replaceSessionNameSpecialChars($originalName);

      $this->checkNameNotEmpty($checkSession->sessionname);

      $this->checkFirstCharacterAlphanumeric($checkSession->sessionname);
   }

   /**
    * Start Time cannot be greater than end time
    * @param unknown_type $startTime
    * @param unknown_type $endTime
    */
   private function checkStartTimeGreaterThanEndTime($startTime, $endTime) {
      if ($startTime > $endTime) {
         throw new Elluminate_Exception('', 0, 'invalidsessiontimes', $this->getTimeDetailsObject($startTime, $endTime));
      }
   }

   /**
    * Start Time cannot be greater than end time
    * @param unknown_type $startTime
    * @param unknown_type $endTime
    */
   private function checkStartTimeEqualToEndTime($startTime, $endTime) {
      if ($startTime == $endTime) {
         throw new Elluminate_Exception('', 0, 'samesessiontimes', $this->getTimeDetailsObject($startTime, $endTime));
      }
   }

   /**
    * Start Time cannot be in the past
    *
    * This validation does NOT occur in update mode
    * @param unknown_type $startTime
    */
   private function checkStartTimeInPast($startTime) {
      $timenow = time();
      if ($startTime < $timenow) {
         throw new Elluminate_Exception('', 0, 'starttimebeforenow', $this->getTimeDetailsObject($startTime));
      }
   }

   /**
    * End Time - Start Time must be less than a year
    * @param $startTime
    * @param $endTime
    */
   private function checkLengthLessThanYear($sessionDuration) {
      if ($sessionDuration > $this::YEAR_IN_SECONDS) {
         throw new Elluminate_Exception('', 0, 'meetinglessthanyear');
      }
   }

   /**
    * Start Time cannot be more than a year in the future
    * @param unknown_type $startTime
    * @param unknown_type $endTime
    */
   private function checkStartTimeLessThanYear($startTime) {
      $timenow = time();
      $year_later = $timenow + $this::YEAR_IN_SECONDS;
      if ($startTime > $year_later) {
         throw new Elluminate_Exception('', 0, 'meetingstartoverayear', $this->getTimeDetailsObject($startTime));
      }
   }

   /**
    * Replace all characters unsupported by SAS in the session name with ''
    * @param unknown_type $sessionName
    * @return mixed
    */
   private function replaceSessionNameSpecialChars($sessionName) {
      $replace = '';
      $strippedname = str_replace($this->SESSION_NAME_SPECIAL_CHARS, $replace, $sessionName);
      return $strippedname;
   }

   private function checkNameNotEmpty($sessionName) {
      if (empty($sessionName)) {
         throw new Elluminate_Exception('', 0, 'meetingnameempty');
      }
   }

   /**
    * This check is a requirement of SAS - the first character in the session name
    * must be alphanumeric.
    *
    * See MOOD-366 for more details - there is no easy way in PHP to check for
    * alphanumeric while supporting all latin character sets, hence this check
    * is done using a black list as opposed to a white list.
    *
    * @param unknown_type $sessionName
    */
   private function checkFirstCharacterAlphanumeric($sessionName) {
      $firstChar = substr($sessionName, 0, 1);
      $invalidChar = false;

      foreach ($this->NON_ALPHANUMERIC as $nonAlphaChar) {
         if ($firstChar == $nonAlphaChar) {
            $invalidChar = TRUE;
            break;
         }
      }

      if ($invalidChar) {
         throw new Elluminate_Exception('', 0, 'meetingnamemustbeginwithalphanumeric');
      }
   }

   /**
    * For a group session, we need to validate not only the parent session name, but
    * the potential child names for the scenario when a custom naming method has been
    * selected for group sessions.
    *
    * The options for custom naming are:
    *   1.) Append Group Name to Session Name
    *   2.) Use only Group Name
    *
    * In both cases, there is a dependency for the group names to be match the same
    * validation rules as the groups
    *
    * @param unknown_type $checkSession
    */
   private function validateChildNames($checkSession) {
      if ($checkSession->customname) {
         $currentGroups = $checkSession->loadGroupsForSession();
         foreach ($currentGroups as $group) {
            $groupName = $group->name;
            $this->preValidateCustomGroupName($checkSession, $groupName);
         }
      }
   }

   /**
    * This function will validate group names.  There is a try-catch in here because the group name is passed through
    * multiple validations, any of which may fail and throw an exception.  In order to get the correct messaging to the UI,
    * we want to catch all and wrap in a group type exception.
    *
    * @param $checkSession
    * @param $groupName
    * @throws Elluminate_Exception
    */
   private function preValidateCustomGroupName($checkSession, $groupName) {
      try {
         $customName = Elluminate_Group_CustomNaming::getCustomNamePreview($checkSession->sessionname, $groupName,
            $checkSession->customname);
         $this->logger->debug("Custom Group Name [" . $customName . "]");
         $this->validateCustomGroupName($customName);
      } catch (Elluminate_Exception $e) {
         //Wrap up the source validation failure in a group naming error
         $details = new StdClass;
         $details->groupname = $groupName;

         throw new Elluminate_Exception('', 0, $this::GROUP_NAME_ERROR . $e->getUserMessageKey(), $details);
      }
   }

   /**
    * Helper method to populate a StdClass object with start and end time.
    *
    * Used to display validation detail messages to user.
    * @param unknown_type $timeStart
    * @param unknown_type $timeEnd
    * @return StdClass
    */
   private function getTimeDetailsObject($timeStart, $timeEnd = '') {
      $details = new StdClass;
      $details->timestart = $this->moodleOutput->getMoodleDate($timeStart);
      if (!empty($timeEnd)) {
         $details->timeend = $this->moodleOutput->getMoodleDate($timeEnd);
      }
      return $details;
   }

   /**
    * The only valid past start time is the original start time set when the session
    * is created.
    *
    * If the start time of the updated session is the same as the existing start time,
    * then no check is done on the start time to confirm it's not in the past.
    *
    * If the start time has changed, we will validate it.
    *
    * If the existing session passed in is null (adding session), then we always
    * check the start time.
    *
    * @return bool
    */
   private function checkUpdateModeStartTimeChanged($checkSession, $existingSession) {
      if ($existingSession != null && $checkSession->timestart == $existingSession->timestart) {
         return false;
      }
      return true;
   }
}