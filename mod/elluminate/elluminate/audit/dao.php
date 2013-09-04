<?php
class Elluminate_Audit_DAO{
   
   const TABLENAME = "log";
   
   public function getCourseLogEventsSinceTime($courseid,$eventtype,$timestart){
      global $DB;
      $select = "time > ? AND course = ? AND " . "module = 'elluminate' AND action = ?";
      $queryCriteria = array($timestart, $courseid, $eventtype);
      
      return $DB->get_records_select(self::TABLENAME, $select, $queryCriteria, 'time ASC');
   }
}