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
 * Class: Elluminate_Session_Configuration
 *
 * Author: dwieser
 *
 * Date:  7/4/13
 *
 ******************************************************************************/
/*
* Based on the values in the module configuration, certain settings are set
* in the session.  These are values that are not stored in the moodle DB
* and are only used by the scheduling server when creating or updating the
* session.
*
* This class is invoked both on add and update.
*
*/
class Elluminate_Session_Configuration {
   private $logger;

   private $sessionUsers;

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
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Session_Configuration");
   }

   public function setConfigurationValues($session, $context) {
      if (Elluminate_Config_Settings::getElluminateSetting('elluminate_all_moderators') == 1) {
         $session->allmoderators = true;
      }

      if (Elluminate_Config_Settings::getElluminateSetting('elluminate_must_be_supervised') == 1) {
         $session->mustbesupervised = true;
      }

      if (Elluminate_Config_Settings::getElluminateSetting('elluminate_raise_hand') == 1) {
         $session->raisehandonenter = true;
      }

      if (Elluminate_Config_Settings::getElluminateSetting('elluminate_permissions_on') == 1) {
         $session->permissionson = true;
      }

      if (Elluminate_Config_Settings::getElluminateSetting('elluminate_pre_populate_moderators') == 1) {
         $this->prePopulateModerators($session, $context);
      }
      return $session;
   }

   /*
   * If the Collaborate Configuration Setting "Pre Populate Moderators" is set to
   * true, this function will get a list of all users in the current moodle context
   * who have permissions to be a moderator for the session and automatically
   * add them to the moderator list.
   */
   private function prePopulateModerators($session, $context) {
      $this->logger->debug("Prepopulating Session Moderators For " . $session->name);

      $this->sessionUsers->init($session, $context);
      $moderators = $this->sessionUsers->getAvailableModerators();

      foreach ($moderators as $moderator) {
         $this->logger->debug("Prepopulating Session Moderator: " . $moderator->id);
         $session->addModerator($moderator->id);
      }
   }
}