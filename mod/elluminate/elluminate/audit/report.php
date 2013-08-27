<?php
class Elluminate_Audit_Report{
   
   const NO_RESULTS = '';
   
   private $logger;
   private $auditDAO;
   private $output;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Audit_Report");
   }
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }

   public function getCourseLastViewedSessions($courseid, $timestart){
      $this->logger->debug("getLastViewedSessions report");

      $logEntries = $this->auditDAO->getCourseLogEventsSinceTime($courseid,Elluminate_Audit_Constants::SESSION_VIEW,$timestart);
      
      if (is_array($logEntries)){
         return $this->getReportContentFromLog($logEntries);
      }else{
         return self::NO_RESULTS;
      }
   }
   
   private function getReportContentFromLog($logEntries){
      global $OUTPUT;
      $sessionInfoHTML = ''; 
      $reportHTML = '';
      
      //Check for duplicate sessions
      $cmids = array();
      
      foreach ($logEntries as $log) {
         //Get Course Module
         $cmid = $log->cmid;
         if ($cmid){
            if (! in_array($cmid,$cmids)){
               $sessionInfoHTML .= $this->createSessionReportEntry($cmid);
               array_push($cmids,$cmid);
            }
         } 
      }
      //If sessions found, add heading
      if ($sessionInfoHTML){
         $reportHTML = $this->output->getMoodleHeading('sessionsviewed',3) . $sessionInfoHTML;
      }
      return $reportHTML;
   }    
   
   private function createSessionReportEntry($courseModuleId){
      $sessionInfoHTML = '';
      $courseModule = get_coursemodule_from_id('elluminate', $courseModuleId);
      if ($courseModule != null){
         $sessionUrl = $this->output->getMoodleUrl(Elluminate_HTML_Session_View::getPageUrl($courseModuleId));
         if ($courseModule->visible){
            $sessionInfoHTML = '<p class="activity"><a href="' . $sessionUrl . '">' . $courseModule->name .'</a></p>';
         }
      }
      return $sessionInfoHTML;
   }
}