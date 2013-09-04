<?php

interface Elluminate_Session_Calendar{
   public function addOpenEventForSession($eventSession);
   public function updateAllEventsForSession($eventSession);
   public function addPrivateUserEvent($eventSession, $eventUser);
   public function deletePrivateUserEvent($eventSession, $eventUser);
   public function addGroupEvent($eventSession);

}