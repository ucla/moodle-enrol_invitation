<?php
/**
 ****************************************************************************
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
 * Class: Elluminate_Session_Addcontroller
 *
 * Author: dwieser
 *
 * Date:  7/4/13
 *
 ******************************************************************************/
/**
 * Class Elluminate_Session_AddController
 *
 * This class is designed as the location for the logic required to take a session being created
 * by a user and do the preparation for getting it saved to SAS and the DB.
 *
 * This controller does NOT do the actual persisting of the session, that is still managed by the
 * Model itself.
 *
 * This will let us move a lot of logic out of the session object.
 */
class Elluminate_Session_Controller_Add {
   private $logger;

   //Helpers
   private $sessionLoader;
   private $sessionConfiguration;
   private $sessionValidator;
   private $sessionGrading;
   private $sessionCalendar;
   private $telephonyManager;

   //New Session Being Added
   private $newSession;

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
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Session_AddController");
   }

   public function createSession($sessionFormValues, $context, $userId) {
      $this->newSession = $this->sessionLoader->loadSessionFromForm($sessionFormValues);

      $this->newSession->creator = $userId;
      $this->newSession->addModerator($userId);

      $this->logger->debug("New Session Initialized : " . $this->newSession->name);

      //1.) Set values from the configuration
      $this->sessionConfiguration->setConfigurationValues($this->newSession, $context);

      //2.) Validate the new Session - will throw an exception in the case of issues
      $this->sessionValidator->validate($this->newSession);

      //3.) Invoke the model to save the session
      $this->newSession->createSession();

      //If Successful, then:

      //4.) Create Moodle Calendar Events for New Session
      $this->createCalendarEvents();

      //5.) Enable Moodle Grading if enabled
      if ($this->newSession->gradesession) {
         $this->sessionGrading->addSessionGradeBook(
            $this->newSession->id,
            $this->newSession->sessionname,
            $this->newSession->course,
            $this->newSession->grade);
      }

      //6.) Toggle Telephony On/Off
      $this->toggleSessionTelephony();

      return $this->newSession;
   }

   /**
    * Create Calendar Events.  This function should be called only when creating a new
    * session.
    *
    * There are several rules in play here:
    * 1.) Course Session Type: a single, open event is added to the calendar.  This
    * is available to all users enrolled in the course
    *
    * 2.) Private Session Type: a single private event is created for the meeting creator
    * only.  Additional invites are added from the user-edit screen
    *
    * 3.) Group Session Type: Create Group Mode Calendar Events
    *      -Events are created only for the child sessions, not the "parent"
    *
    */
   public function createCalendarEvents() {
      $this->logger->debug("createCalendarEvents for: " . $this->newSession->id);

      if ($this->newSession->isGroupSession()) {
         foreach ($this->newSession->childSessions as $childSession) {
            $this->logger->debug("createCalendarEvents Group Mode, group id [" . $childSession->groupid . "]");
            $this->sessionCalendar->addGroupEvent($childSession);
         }
         return;
      }

      if ($this->newSession->sessiontype == Elluminate_Session::PRIVATE_SESSION_TYPE) {
         $this->sessionCalendar->addPrivateUserEvent($this->newSession, $this->newSession->creator);
         return;
      }

      if ($this->newSession->sessiontype == Elluminate_Session::COURSE_SESSION_TYPE) {
         $this->sessionCalendar->addOpenEventForSession($this->newSession);
         return;
      }
   }

   private function toggleSessionTelephony() {
      $this->telephonyManager->toggleSessionTelephony($this->newSession->meetingid,
         $this->newSession->telephony);

      if ($this->newSession->isGroupSession()) {
         foreach ($this->newSession->childSessions as $childSession) {
            $this->telephonyManager->toggleSessionTelephony($childSession->meetingid, $childSession->telephony);
         }
      }
   }
}