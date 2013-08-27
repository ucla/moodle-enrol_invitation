<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of schedulingmanagerfactory
 *
 * @author matthewschmidt
 */

class Elluminate_WS_SchedulingManagerFactory {

   const CONTAINER_LOOKUP_SUFFIX = "Implementation";

   public static function getSchedulingManager() {
      global $ELLUMINATE_CONTAINER;
      $logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_SchedulingManagerFactory");

      $scheduler = Elluminate_Config_Settings::getElluminateSetting('elluminate_scheduler');

      if ($scheduler == ''){
         throw new Elluminate_Exception('The Blackboard Collaborate module has not been configured.  Please contact your administrator.', 0, 'user_error_unconfiguredmodule');
      }

      $logger->debug("Scheduling Manager Type [" . $scheduler . "]");
      return $ELLUMINATE_CONTAINER[$scheduler . self::CONTAINER_LOOKUP_SUFFIX];
   }

   public static function getSchedulingManagerWithSettings($serverurl, $username, $password) {
      global $ELLUMINATE_CONTAINER;
      $logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_SchedulingManagerFactory");
      $connectArgs = array(
         "serverurl" => $serverurl,
         "username" => $username,
         "password" => $password
      );

      $soapHelper = new Elluminate_WS_SOAP_Helper();
      $soapResponse = $soapHelper->send_command('GetSchedulingManager', null, $connectArgs);

      //ELM returns undefined
      //SAS returns SchedulingManagerResponse -> manager
      //Will do a check to ensure the correct result

      $apiResponse = $soapResponse->apiResponse;
      if (!isset($apiResponse->SchedulingManagerResponse)) {
         $scheduler = "ELM";
      } else {
         $schedulerResponse = $apiResponse->SchedulingManagerResponse;
         $scheduler = $schedulerResponse->manager;
      }

      Elluminate_Config_Settings::setElluminateSetting('elluminate_scheduler', $scheduler);

      $logger->debug("Scheduling Manager Type [" . $scheduler . "]");
      return $ELLUMINATE_CONTAINER[$scheduler . self::CONTAINER_LOOKUP_SUFFIX];
   }
}
