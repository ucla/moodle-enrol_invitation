<?php
class Elluminate_Group_SessionInitializer {

   private $logger;

   private $initializerSession;
   private $sessionContext;

   const DEFAULT_BOUNDARY_INCREASE = 30;
   const MIN_TIME_INCREMENT = 15;

   //Session Waiting State, max wait time is 10 seconds
   //These are variables so we can override from tests
   private $MAX_WAIT_ATTEMPTS = 5;
   private $INIT_WAITING_DELAY = 2;

   const SECONDS_IN_MINUTE = 60;

   //See MOOD-468 - 2 minute (2 * 60)
   const TIME_BUFFER = 120;

   private $overrideTime;

   private $sessionConfiguration;
   private $sessionLoader;

   public function __construct() {
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Group_SessionInitializer");
      $this->overrideTime = 0;
   }

   public function __set($property, $value) {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }

   /**
    * When a group session is initially created in Moodle, the SAS meeting
    * is not actually created until the first person joins.  This prevents creating
    * sessions for groups that may never use the meeting.
    *
    * This method is responsible for the initialization required to create a meeting
    * at the point when that first user is joining.
    *
    * The tricky logic here is that when the session is created, the start time
    * potentially needs to be adjusted because the first user might not actually
    * join until after the original start time (meaning that start time would be
    * in the past and not accepted by SAS).
    *
    * No processing at all is done for sessions that are over.  Group child sessions is this
    * scenario will never actually get created on SAS.
    *
    */
   public function initSession($context) {
      $this->sessionContext = $context;
      if ($this->initializerSession->meetinginit != Elluminate_Session::MEETING_INIT_COMPLETE &&
         !$this->initializerSession->hasSessionEnded()
      ) {
         $this->processInitialization();
      }
   }

   /**
    * If two users attempt to initialize the session at the same time, we will initiate a wait of 3 seconds which
    * will pause the loading of the page and/or launching of the session.  If we reach the 3 second wait and return
    * and the session is still not initialized, an error will be displayed to the user.
    */
   private function processInitialization() {
      if ($this->initializerSession->meetinginit == Elluminate_Session::MEETING_NOT_INIT) {
         $this->logger->debug("Session State: MEETING_NOT_INIT");
         return $this->initializeServerMeeting();
      }
      if ($this->initializerSession->meetinginit == Elluminate_Session::MEETING_INIT_WAITING) {
         $this->waitForSessionInitialization();
      }
   }

   private function waitForSessionInitialization() {
      $sessionId = $this->initializerSession->id;
      $this->logger->debug("Session [" . $sessionId . "] State: MEETING_INIT_WAITING " .
      ": sleep for [" . $this->INIT_WAITING_DELAY . "]");

      $sleepCounter = 0;
      $waitSession = $this->initializerSession;
      while ($sleepCounter < $this->MAX_WAIT_ATTEMPTS &&
         $waitSession->meetinginit == Elluminate_Session::MEETING_INIT_WAITING) {
         sleep($this->INIT_WAITING_DELAY);
         $waitSession = $this->sessionLoader->getSessionById($sessionId);
         $this->logger->debug("Retry Attempt [" . $sleepCounter . "]: " .
         "meeting_init is [" .
         $waitSession->meetinginit . "]");
         if ($waitSession->meetinginit == Elluminate_Session::MEETING_INIT_COMPLETE) {
            $this->logger->debug("Session State moved to MEETING_INIT_COMPLETE");
            $this->updateWaitingReferencedSession($waitSession);
         }
         $sleepCounter++;
      }
   }

   /**
    * This class works on a referenced session object that was loaded and is used on the
    * view layer.
    *
    * In the case of this session originally loaded in a wait state, we now need to
    * update the values so the launch/view page has the correct data to display for the session.
    *
    * The values updated here are the only ones that can be changed during the initialization process.
    *
    * @param $waitSession
    */
   private function updateWaitingReferencedSession($waitSession) {
      $this->initializerSession->timestart = $waitSession->timestart;
      $this->initializerSession->timeend = $waitSession->timeend;
      $this->initializerSession->boundarytime = $waitSession->boundarytime;
      $this->initializerSession->meetingid = $waitSession->meetingid;
      $this->initializerSession->meetinginit = $waitSession->meetinginit;
   }

   private function initializeServerMeeting() {
      $currentTime = $this->getCurrentTime();
      if ($this->initializerSession->timestart < $currentTime) {
         $this->getNewStartTime($currentTime);
      }

      //Update the session with module configuration-based values
      $this->sessionConfiguration->setConfigurationValues($this->initializerSession, $this->sessionContext);

      //Set waiting mode prior to SAS call to avoid duplicate
      //create requests
      $this->initializerSession->setSessionInitStatus(Elluminate_Session::MEETING_INIT_WAITING);
      try {
         $this->initializerSession->initServerSession();
      } catch (Exception $e) {
         //reset meeting init state and then pass error up the chain
         $this->initializerSession->setSessionInitStatus(Elluminate_Session::MEETING_NOT_INIT);
         throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_processing');
      }
      return true;
   }

   /**
    * See MOOD-323 for additional details on this logic.
    */
   private function getNewStartTime($currentTime) {
      // Get the current time, determine the next 15 minute increment
      // in the future, set up the new start time accordingly
      $boundaryTime = $this->initializerSession->boundarytime;
      $minutes = date('i', $currentTime);
      $millis = date('s', $currentTime);

      $addminutes = $this::MIN_TIME_INCREMENT - ($minutes % $this::MIN_TIME_INCREMENT);

      $newStartTime = $currentTime + ($addminutes * $this::SECONDS_IN_MINUTE) - $millis;

      //If start time has been adjusted (max increment would be 15 minutes in the future)
      //we always set the boundary time to "DEFAULT_BOUNDARY_INCREASE" minutes to ensure there
      //is no difficulty accessing the session
      //The only exception is if the boundary time is already > the default
      if ($boundaryTime < $this::DEFAULT_BOUNDARY_INCREASE) {
         $newBoundaryTime = $this::DEFAULT_BOUNDARY_INCREASE;
      } else {
         $newBoundaryTime = $boundaryTime;
      }

      // calculate new end time - keep the session length the same even though the
      // start time has changed
      $sessionLength = $this->initializerSession->timeend - $this->initializerSession->timestart;
      $newEndTime = $newStartTime + $sessionLength;

      //New values calculated, set in session object
      $this->initializerSession->timestart = $newStartTime;
      $this->initializerSession->timeend = $newEndTime;
      $this->initializerSession->boundarytime = $newBoundaryTime;
      $this->logger->debug("Times for Session ID [" . $this->initializerSession->id . "]" .
      " adjusted to start [" . $newStartTime . "] end [" . $newEndTime . "]" .
      " boundary [" . $newBoundaryTime . "]");
   }

   /**
    * This function will handle two scenarios:
    * 1.) Testing of this class by overriding the retrieval of current system time
    * with a specifically set time.  Time buffer is added so that testing acts the same
    * as the system time logic. (i.e. set the overrideTime to the current time, not the
    * buffer time)
    *
    * 2.) Returning the current time with a buffer added.  This is meant to handle minor
    * time differences between SAS and the Moodle server.  For example, if the SAS server is 45 seconds
    * ahead, at 13:59:30 on the Moodle server, the SAS will already have already rolled over to the next hour
    * and have a time of 14:00:15.  This will cause rejection of the session start time.
    * The buffer will cause time within TIME_BUFFER minutes of the rollover to be updated to the next start time.
    *
    * See:  MOOD-468
    *
    * @return number
    */
   private function getCurrentTime() {
      if ($this->overrideTime > 0) {
         $currentTime = $this->overrideTime + self::TIME_BUFFER;
      } else {
         $currentTime = time() + self::TIME_BUFFER;
      }
      $this->logger->debug("getCurrentTime [" . $currentTime . "]");
      return $currentTime;
   }
}