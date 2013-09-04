<?php
/**
 * Moodle Grade Reports:
 *   Outline Report & Complete Report
 *   
 *   See MOOD-350 for some details on output and testing of these reports.
 *   
 * @author dwieser
 *
 */
class Elluminate_Grades_Reports{
   
   const GRADE_DELIMITER = ": ";
   const SESSION_NOT_ATTENDED = "-";
   
   private $gradesAPI;
   private $gradesFactory;
   
   private $logger;
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Grades_Reports");
      $this->gradesAPI = new Elluminate_Moodle_GradeHelper();
   }
   
   public function completeReport($sessionid, $userid){
      global $OUTPUT;
      
      $attendance = $this->gradesFactory->newAttendance();
      $attendance->loadForSessionAndUser($sessionid, $userid);
      
      /// Print a detailed representation of what a  user has done with
      /// a given particular instance of this module, for user activity reports.
      if ($attendance->id) {
         $OUTPUT->box('_start');
         echo get_string('attended', 'elluminate') . self::GRADE_DELIMITER;
         echo userdate($attendance->timemodified);
      
         $OUTPUT->box('_end');
      
      } else {
         print_string('notattendedyet', 'elluminate');
      }
   }
   
   /**
    * Load an attendance record and create an associated stdclass object for the moodle
    * outline report
    * 
    * @param unknown_type $session
    * @param unknown_type $userid
    * @return NULL
    */
   public function buildUserOutlineReportObject($session, $userid){
      $attendanceResult = NULL;
      $attendance = $this->gradesFactory->newAttendance();
      $attendance->loadForSessionAndUser($session->id, $userid);
      if ($attendance->id){
         $attendanceResult = $this->getUserOutlineObject($attendance, $session->grade, $session->gradesession);
      }
      return $attendanceResult;
   }
   
   /**
    * Build a StdClass object that is used by moodle to create the user outline report
    * @param unknown_type $attendance
    * @param unknown_type $grade
    * @param unknown_type $gradesession
    * @return stdClass
    */
   public function getUserOutlineObject($attendance, $grade, $gradesession){
      $attendanceResult = new stdClass;
      $attendanceResult->info = $this->getGradeString($attendance,
            $grade,
            $gradesession);
      $attendanceResult->time = $attendance->timemodified;
      return $attendanceResult;
   }
   
   private function getGradeString($attendance, $maxgrade, $gradesession){
      $gradeString = '';
      $gradeString .= $this->getAttendanceDisplay($attendance,$maxgrade,$gradesession);
      return $gradeString;
   }
   
   private function getAttendanceDisplay($attendance, $maxgrade, $gradesession){
      $this->logger->debug("getAttendanceDisplay: maxgrade = " . $maxgrade . 
            " gradesession = " . $gradesession);
      $gradeString = '';
      //Grading Disabled - display "-"
      if ($gradesession == Elluminate_Session::GRADING_DISABLED) {
         return self::SESSION_NOT_ATTENDED;
      }
       
      //Numeric or No Grade.  Will be displayed as N/N
      if ($maxgrade > 0 || $maxgrade == 0) {
         $gradeString .= get_string('grade');
         $gradeString .= self::GRADE_DELIMITER;
         $gradeString .= $this->getFormattedNumericGrade($attendance->grade, $maxgrade);
         return $gradeString;
      }
       
      //Scaled Grade - display grade scale name
      if ($maxgrade < 0) {
         $gradeString .= get_string('grade');
         $gradeString .= self::GRADE_DELIMITER;
         $gradeString .= $this->getScaledGradeValueName($attendance->grade,$maxgrade);
         return $gradeString;
      }
   }
   
   private function getFormattedNumericGrade($usergrade, $maxgrade){
      return $usergrade . ' / ' . $maxgrade;
   }
   
   /**
    * If grade is a scaled grade, we want to display the name value for the students
    * grade as opposed to the numerical value.
    * 
    * This function gets the list of scale names for the scale and then users the
    * user grade to do a lookup for the correct value
    * 
    * @param string $usergrade
    * @param string $scaleid
    * @return string
    */
   private function getScaledGradeValueName($usergrade, $scaleid){
      $this->logger->debug("getScaledGradeValueName start: " . $usergrade . " / " . $scaleid);
      $scaledArray = $this->gradesAPI->getScaledGradeMenu($scaleid);
      return $scaledArray[$usergrade];
   }
}