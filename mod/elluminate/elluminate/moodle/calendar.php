<?php

// Moodle Calendar Libraries
require_once($CFG->libdir . '/eventslib.php');
require_once($CFG->dirroot . '/calendar/lib.php');

/**
 *
 * Helper Class to encapsulate dealing with the Moodle Calendar.
 *
 * @author dwieser
 *
 */
class Elluminate_Moodle_Calendar implements Elluminate_Session_Calendar {
   public function addOpenEventForSession($eventSession) {
      $calEvent = $this->populateEventFromSession($eventSession);
      $calEvent->visibility = instance_is_visible('elluminate', $eventSession);
      calendar_event::create($calEvent);
   }

   public function addGroupEvent($eventSession) {
      $calEvent = $this->populateGroupEventFromSession($eventSession);
      $calEvent->visibility = instance_is_visible('elluminate', $eventSession);
      calendar_event::create($calEvent);
   }

   public function updateAllEventsForSession($eventSession) {
      global $DB;

      try {
         $oldevents = $DB->get_records('event', array('modulename' => 'elluminate', 'instance' => $eventSession->id));
      } catch (Exception $e) {
         throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }

      foreach ($oldevents as $oldevent) {
         $updatedEvent = $oldevent;

         //Update Fields
         $updatedEvent->description = $eventSession->description;
         if ($eventSession->isPrivateSession()) {
            //It's possible that we might be switching from a course type session to a private type session, so we need
            //to set the appropriate fields here.  If the update does not impact the session type, there is no harm done.
            $updatedEvent->name = get_string('calendarname', 'elluminate', $eventSession->name);
            $updatedEvent->eventtype = 'user';
            $updatedEvent->courseid = '';
         } else {
            //See above, this might be a switch from private->course type session.
            $updatedEvent->name = $eventSession->name;
         }
         $updatedEvent->timestart = $eventSession->timestart;
         $updatedEvent->timeduration = $eventSession->getSessionDuration();
         $updatedEvent->visible = instance_is_visible('elluminate', $eventSession);
         calendar_event::create($updatedEvent);
      }
   }

   /**
    * Add a user specific calendar event for a private session
    *
    * This happens when a user is invited to a private meeting
    *
    * @param Elluminate_Session $eventSession
    * @param unknown_type $eventUser
    */
   public function addPrivateUserEvent($eventSession, $eventUser) {
      $event = new StdClass;
      $event->description = $eventSession->description;
      $event->courseid = 0;
      $event->groupid = 0;
      $event->format = 1;
      $event->eventtype = '';
      $event->name = get_string('calendarname', 'elluminate', $eventSession->name);
      $event->instance = $eventSession->id;
      $event->modulename = 'elluminate';
      $event->userid = $eventUser;
      $event->visible = 1;

      $event->timestart = $eventSession->timestart;
      $event->timeduration = $eventSession->getSessionDuration();
      $event->timemodified = time();
      calendar_event::create($event);
   }

   /**
    * Delete a private calendar event for a given session and user
    * @see Elluminate_Session_Calendar::deletePrivateUserEvent()
    */
   public function deletePrivateUserEvent($eventSession, $eventUser) {
      global $DB;
      try {
         return $DB->delete_records('event', array('modulename' => 'elluminate',
            'instance' => $eventSession->id, 'userid' => $eventUser));
      } catch (Exception $e) {
         throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
   }

   /*
    * It's possible that a session can be switched from a private session to a course session.
    *
    * Private Sessions have multiple per-user calendar events, while course-type sessions have a single
    * event for the entire course.
    *
    * In order to prevent duplicate calendar entries, this type of switch needs to involve clearing out
    * all private session calendar events and then adding back in a course style event.
    */
   public function updatePrivateSessionToCourseSession($eventSession) {
      global $DB;
      try {
         $DB->delete_records('event', array('modulename' => 'elluminate', 'instance' => $eventSession->id));
         $this->addOpenEventForSession($eventSession);
      } catch (Exception $e) {
         throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
   }

   private function populateEventFromSession($eventSession) {
      $event = new StdClass;
      $event->description = $eventSession->description;
      $event->courseid = $eventSession->course;
      $event->eventtype = 'open';
      $event->name = $eventSession->name;
      $event->instance = $eventSession->id;
      $event->modulename = 'elluminate';

      $event->timestart = $eventSession->timestart;
      $event->timeduration = $eventSession->getSessionDuration();
      return $event;
   }

   private function populateGroupEventFromSession($eventSession) {
      $event = new StdClass;
      $event->groupid = $eventSession->groupid;
      $event->description = $eventSession->description;
      $event->courseid = $eventSession->course;
      $event->eventtype = 'group';
      $event->name = $eventSession->sessionname;
      $event->instance = $eventSession->groupparentid;
      $event->modulename = 'elluminate';

      $event->timestart = $eventSession->timestart;
      $event->timeduration = $eventSession->getSessionDuration();

      return $event;
   }
}