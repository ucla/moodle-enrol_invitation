<?php
/**
 * Class Elluminate_Group_CustomNaming
 *
 * Author: Danny Wieser
 *
 * See MOOD-576 for details on handling of long custom group names
 */
class Elluminate_Group_CustomNaming {

   const CUSTOMNAME_GROUPNAMEONLY = 1;
   const CUSTOMNAME_APPENDGROUPNAME = 2;
   const CUSTOM_APPEND_CHAR = '-';
   const CUSTOMNAME_TOO_LONG_ELLIPSES = "...";
   const CUSTOMNAME_TOO_LONG_LENGTH = 5;
   const NAME_SPLIT_LENGTH = 31;
   const BLANK_NAME = '';
   const BLANK_DESC = '';
   const HALF = 2;

   const VALID_GROUP_NAME = true;
   const INVALID_GROUP_NAME = false;

   private $logger;
   private $validator;
   private $validationError;

   public function __construct() {
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Group_CustomNaming");
      $this->validationError = false;
   }

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

   /**
    * This method will return the group custom name with no validation
    * performed to allow for the session validator to perform the required validation.
    */
   public static function getCustomNamePreview($sessionname, $groupname, $customtype) {
      $customNaming = new Elluminate_Group_CustomNaming();
      return $customNaming->getNewSessionName($sessionname, $groupname, $customtype);
   }

   public function buildCustomGroupName($sessionname, $groupname, $customtype) {
      $validatedGroupName = $this->validateGroupName($groupname);

      $newSessionName = $this->getNewSessionName($sessionname, $validatedGroupName, $customtype);
      $this->logger->debug("buildCustomGroupName new name [" . $newSessionName . "]");

      return $newSessionName;
   }

   public function buildCustomDescription($description, $groupName) {
      if ($description == self::BLANK_DESC) {
         return self::BLANK_DESC;
      } else {
         return $groupName . self::CUSTOM_APPEND_CHAR . $description;
      }
   }

   private function validateGroupName($groupName) {
      try {
         $validGroupName = $this->validator->validateCustomGroupName($groupName);
         $groupName = $validGroupName;
      } catch (Elluminate_Exception $validationFailure) {
         $groupName = get_string('badgroupname', 'elluminate');
         $this->logger->error("Group Naming Error for group name [" . $groupName . "]");
         $this->validationError = true;
      }
      return $groupName;
   }

   /**
    * Return the correct custom session group name based on the select
    * custom naming type and the results of the validation.
    *
    * @param $sessionname
    * @param $groupname
    * @param $customtype
    * @return Ambigous <string, unknown>
    */
   public function getNewSessionName($sessionname, $groupname, $customtype) {
      if ($customtype == self::CUSTOMNAME_GROUPNAMEONLY) {
         return $this->groupNameOnlyMode($sessionname, $groupname);
      }

      if ($customtype == self::CUSTOMNAME_APPENDGROUPNAME) {
         return $this->appendGroupNameMode($sessionname, $groupname);
      }
   }

   private function groupNameOnlyMode($sessionname, $groupname) {
      if ($this->validationError) {
         return $this->appendName($sessionname, $groupname);
      } else {
         if ($this->isNameTooLong($groupname)) {
            $groupname = $this->truncateName($groupname, Elluminate_Session::NAME_MAX_LENGTH);
         }
         return $groupname;
      }
   }

   private function appendGroupNameMode($sessionname, $groupname) {
      if ($this->isNameTooLong($this->appendName($sessionname, $groupname))) {
         return $this->appendAndTruncate($sessionname, $groupname);
      } else {
         return $this->appendName($sessionname, $groupname);
      }
   }

   private function appendName($sessionname, $groupname) {
      return $sessionname . self::CUSTOM_APPEND_CHAR . $groupname;
   }

   private function isNameTooLong($name) {
      if (strlen($name) > Elluminate_Session::NAME_MAX_LENGTH) {
         return true;
      } else {
         return false;
      }
   }

   private function appendAndTruncate($sessionName, $groupName){
      $nameMaxLength = Elluminate_Session::NAME_MAX_LENGTH - strlen(self::CUSTOM_APPEND_CHAR);

      //If groupname > 32 chars, session name is truncated at 32.  Otherwise, use full available length
      if (strlen ($groupName) > self::NAME_SPLIT_LENGTH){
         $sessionNameTruncateLength = self::NAME_SPLIT_LENGTH;
      }else{
         $sessionNameTruncateLength = $nameMaxLength - strlen($groupName);
      }

      //We only truncate session name if > 32
      if (strlen($sessionName) > self::NAME_SPLIT_LENGTH){
         $sessionName = $this->truncateName($sessionName, $sessionNameTruncateLength);
      }

      //Use either 32 chars OR the rest of the available space for group name
      if (strlen($groupName) > self::NAME_SPLIT_LENGTH){
         $groupNameTruncateLength = $nameMaxLength - strlen($sessionName);
         $groupName = $this->truncateName($groupName, $groupNameTruncateLength);
      }

      return $this->appendName($sessionName, $groupName);
   }

   private function truncateName($name, $truncateLength) {
      $truncateLength = $truncateLength - strlen(self::CUSTOMNAME_TOO_LONG_ELLIPSES);
      if (strlen($name) > $truncateLength) {
         return substr($name, 0, $truncateLength) . self::CUSTOMNAME_TOO_LONG_ELLIPSES;
      } else {
         return $name;
      }
   }
}