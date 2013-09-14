<?php
class Elluminate_Audit_Log{
   const MOD_NAME = 'elluminate';
    
   public static function log($auditEvent, $url, $pageSession = null,$courseModule = null){
      global $USER;

      if ($pageSession == null){
         $course = 0;
         $info = '';
      }else{
         $course = $pageSession->course;
         $info = "Session ID: " . $pageSession->id . " Name: " . $pageSession->sessionname;
      }

      if ($courseModule == null){
         $cmid = 0;
      }else{
         $cmid = $courseModule->id;
      }

      $logger = Elluminate_Logger_Factory::getLogger("loadmeeting");
      $logger->info("Audit Log Event [" . $auditEvent . "], URL [" . $url . "]"); 
      
      add_to_log($course, self::MOD_NAME, $auditEvent, $url, $info, $cmid, $USER->id);
   }
}