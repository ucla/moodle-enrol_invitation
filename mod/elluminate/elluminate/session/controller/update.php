<?php
/******************************************************************************
 *
 * Copyright (c) 2013 Blackboard Inc., All Rights Reserved.                         *
 *
 * COPYRIGHT:
 *      This software is the property of Blackboard Inc.                           *
 *      It cannot be copied, used, or modified without obtaining an
 *      authorization from the authors or a mandated member of Blackboard.
 *      If such an authorization is provided, any modified version
 *      or copy of the software has to contain this header.
 *
 * WARRANTIES:
 *      This software is made available by the authors in the hope
 *      that it will be useful, but without any warranty.
 *      Blackboard Inc. is not liable for any consequence related to the
 *      use of the provided software.
 *
 * Class: Elluminate_Session_Controller_Update
 *
 * Author: dwieser
 *
 * Date:  7/4/13
 *
 ******************************************************************************/
/**
 * Class Elluminate_Session_Controller_Update
 *
 * Controller to handle logic and steps required to update an existing session
 */
class Elluminate_Session_Controller_Update {
   private $logger;

   //This is the list of fields that can be updated (not all fields can be)
   private $UPDATE_FIELDS = array("sessionname", "name", "description",
      "timeend", "recordingmode",
      "maxtalkers", "boundarytimedisplay", "boundarytime",
      "customname", "customdescription", "groupingid",
      "groupingid", "groupmode", "telephony",
      "allmoderators", "raisehandonenter",
      "mustbesupervised", "permissionson", "chairlist");

   //Helpers
   private $sessionLoader;
   private $sessionConfiguration;
   private $sessionValidator;
   private $sessionGrading;
   private $sessionCalendar;
   private $telephonyManager;

   //Session Built from form, with updates
   private $sessionWithUpdates;

   //Original Session
   private $existingSession;

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

   public function __construct() {
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Session_Controller_Update");
   }

   public function updateSession($sessionFormValues, $context) {
      $this->logger->debug("Starting session [" . $sessionFormValues->instance . "] update");
      $this->sessionWithUpdates = $this->sessionLoader->loadSessionFromForm($sessionFormValues);
      $this->existingSession = $this->sessionLoader->getSessionById($sessionFormValues->instance);

      //1. Chairlist is eligible for update during step 2, but is not included in the
      //form submit data, so we prepopulate the existing value before any further actions are taken
      $this->sessionWithUpdates->chairlist = $this->existingSession->chairlist;

      //2.) We will set any new values for configuration in the sessionWithUpdates.  These will
      //then be copied into the existing session with all other value updates.
      $this->sessionConfiguration->setConfigurationValues($this->sessionWithUpdates, $context);

      //3.) Validate Session
      $this->sessionValidator->validate($this->sessionWithUpdates, $this->existingSession);

      //4.) Check for session type changes
      if (!$this->existingSession->isGroupSession()) {
         $this->checkForSessionTypeChange();
      }

      //5.) Update original session with new values
      $startTimeChange = $this->hasStartTimeChanged();
      if ($this->existingSession->isGroupSession()) {
         $this->groupSessionUpdate($startTimeChange);
      } else {
         $this->existingSession = $this->updateSessionData(
            $this->existingSession,
            $this->sessionWithUpdates,
            $startTimeChange);
      }

      //6.) Update Session Grading if applicable
      $this->sessionGrading->updateSessionGrades($this->existingSession, $this->sessionWithUpdates);

      //7.) Save the session to DB and server
      $this->existingSession->updateSession();

      //8.) Success, update moodle calendar
      $this->sessionCalendar->updateAllEventsForSession($this->existingSession);

      //9.) If success, update session telephony
      $this->toggleSessionTelephony();

      $this->logger->debug("Session [" . $this->existingSession->id . "] successfully Updated");
      return $this->existingSession;
   }

   /**
    * Session has been updated, copy values from updated session into current session
    * Start Time: Validation for start time is skipped during the update process, but only if the start time
    *                    is the exact same value as the original session.  This means that the only time we ever need
    *                    to update start time is if the value from the submitted form is different than the original.
    *                    If the value of $startTimeChange is TRUE, we can assume that the date has been validated.
    *
    *                    GROUPS:  The start time has additional complexity for group sessions.  Since group sessions are
    *                    not created on SAS until a user views the page for a specific group, the start times for group
    *                    sessions can actually be different from one another.  For example:
    *                         Session A - Group 1 has a start time of 11:00AM (original value)
    *                         Session B - Group 2 was not loaded until 11:03AM, so it's start time is set to 11:15AM
    *
    *                    In this case, if we attempted to update the group session and set the start time for Session B
    *                    back to 11:00AM, SAS will throw a validation error.  We need to leave the start time as-is for
    *                    Session B unless the user has explicitly changed the start time for all group sessions to a new
    *                    value.  This new start time is guaranteed to be in the future because it will have been
    *                    validated.
    */
   private function updateSessionData($originalSession, $updatedSession, $startTimeChange) {
      $originalSession->timemodified = time();

      foreach ($this->UPDATE_FIELDS as $fieldName) {
         if ($updatedSession->$fieldName !== $originalSession->$fieldName) {
            $originalSession->$fieldName = $updatedSession->$fieldName;
         }
      }

      if ($startTimeChange){
         $originalSession->timestart = $updatedSession->timestart;
      }
      return $originalSession;
   }

   /**
    * During session update, a user can check or uncheck the restrict participants settings, which will
    * ultimately change the session type.  This is a relatively simple switch - the only potential
    * side effect is that a course type session may end up with a nonchairlist (leave this list intact
    * in case the user decides to switch back to private).
    *
    * The second thing to be checked here is calendar events.  Private and Course Sessions have different types
    * of events, and this switch needs to be handled
    * @see Elluminate_Moodle_Calendar
    */
   private function checkForSessionTypeChange() {
      if ($this->existingSession->sessiontype != $this->sessionWithUpdates->sessiontype) {
         $this->logger->debug(
            "Updating Session Type for ID [" . $this->existingSession->id . "]" .
            "to type: [" .$this->sessionWithUpdates->sessiontype . "]");

         if ($this->existingSession->isPrivateSession()){
            $this->logger->debug("Removing Private Session Calendar Events for: " . $this->existingSession->id);
            $this->sessionCalendar->updatePrivateSessionToCourseSession($this->existingSession);
         }

         $this->existingSession->sessiontype = $this->sessionWithUpdates->sessiontype;
      }
   }

   private function groupSessionUpdate($startTimeChange){
      $this->existingSession = $this->updateSessionData(
         $this->existingSession,
         $this->sessionWithUpdates,
         $startTimeChange);
      $this->updateChildSessionData($startTimeChange);
   }

   /**
    * For group sessions, it's actually the child sessions that need to be updated with the new data from the
    * session update.
    * @param $sessionWithUpdates
    */
   private function updateChildSessionData($startTimeChange) {
      foreach ($this->existingSession->childSessions as $childSession) {
         $this->logger->debug("updateChildSessionData for session id: " . $childSession->id);
         $childSession = $this->updateSessionData($childSession, $this->sessionWithUpdates, $startTimeChange);
         $childSession->updateChildSessionNaming();
      }
   }

   private function toggleSessionTelephony(){
      $this->telephonyManager->toggleSessionTelephony($this->existingSession->meetingid,
         $this->existingSession->telephony);

      if ($this->existingSession->isGroupSession()){
         foreach ($this->existingSession->childSessions as $childSession) {
            $this->telephonyManager->toggleSessionTelephony($childSession->meetingid, $childSession->telephony);
         }
      }
   }

   private function hasStartTimeChanged(){
      if ($this->existingSession->timestart != $this->sessionWithUpdates->timestart){
        return true;
      }
      return false;
   }
}