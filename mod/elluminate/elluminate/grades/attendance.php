<?php
class Elluminate_Grades_Attendance{
     
   private $id;
   private $elluminateid;
   private $userid;
   private $grade;
   private $timemodified;
   
   private $logger;
   
   private $existing = false;
   
   //Dependencies
   private $gradesDAO;
   private $gradesAPI;
   private $gradesFactory;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Grades_Attendance");
   }
   
   // ** GET/SET Magic Methods **
   public function __get($property)
   {
      if (property_exists($this, $property)) {
         return $this->$property;
      }
   }
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   public function loadForSessionAndUser($sessionid,$userid)
   {
      $this->logger->debug("Loading Attendance Entry for session: " . $sessionid . " user: " . $userid);
      $dbobject = $this->gradesDAO->getSessionUserGrade($sessionid, $userid);
      if (isset($dbobject->id)){
         $this->loadFromDBObject($dbobject); 
      }else{
         $this->elluminateid = $sessionid;
         $this->userid = $userid;
      }
   }
   
   public function loadForSession($sessionid)
   {
      $this->logger->debug("Loading Attendance Entry for session: " . $sessionid);
      $attendanceList = array();
      $attendanceListRaw = $this->gradesDAO->getAllSessionAttendance($sessionid);
      foreach ($attendanceListRaw as $attendanceRaw){
         $attendance = $this->gradesFactory->newAttendance();
         $attendance->loadFromDBObject($attendanceRaw);
         $attendanceList[] = $attendance;
      }
      $this->logger->debug("Loading Attendance Entry for session complete: " . sizeof($attendanceList) . " records");
      return $attendanceList;
   }
   
   public function loadFromDBObject($dbobject)
   {
         $this->id = $dbobject->id;
         $this->userid = $dbobject->userid;
         $this->grade = $dbobject->grade;
         $this->elluminateid = $dbobject->elluminateid;
         $this->timemodified = $dbobject->timemodified;
         $this->existing = true;
   }
   
   public function setAttendanceGrade($grade){
      $this->grade = $this->gradesAPI->getMaxGradeValue($grade);
   }
   
   public function save(){
      $this->timemodified = time();
      if ($this->existing){
         $this->gradesDAO->updateSessionAttendance($this);
      }else{
         $this->gradesDAO->saveNewSessionAttendance($this);
      }
      $this->logger->debug("Saved: " . $this);
      return true;
   }
   
   /**
    * This function will return a flag indicating if the current attendance record
    * already has a grade or not (value > 0).  This is used to determine if the record
    * needs to be updated when the main grade for the session is changed.
    * 
    * @return boolean
    */
   public function hasExistingGrade(){
      if ($this->grade > Elluminate_Session_Grading::DEFAULT_GRADE){
         return true;
      }else{
         return false;
      }
   }
   
   public function resetDefaultGrade(){
      $this->grade = $this->grade = Elluminate_Session_Grading::DEFAULT_GRADE;
      $this->save();
   }   
   
   public function getAttendeeCountForSession($sessionId){
      $count = $this->gradesDAO->getSessionAttendeeCount($sessionId);
      return $count;
   }
   
   /**
    * Helper function to return a StdClass version of this object
    * to allow public access on all member variables.
    *
    * This is required by the $DB moodle object to do DB operations
    * with the object
    *
    * @return StdClass
    */
   public function getDBInsertObject()
   {
      return get_object_vars($this);
   }
   
   public function __toString(){
      return "Attendance::" .
      "::id=" . $this->id .
      "::sessionid=" . $this->elluminateid .
      "::userid=" . $this->userid .
      "::timemodified=" . $this->timemodified;
   }
}   