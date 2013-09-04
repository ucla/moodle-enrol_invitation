<?php

class Elluminate_Moodle_GradeHelper{
   
   const MOD_NAME = "mod/elluminate";
   
   const GRADE_SELECT_NAME = "attendance[]";
   const BLANK_SELECT_OPTION = false;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Moodle_GradeHelper"); 
   }
   
   /**
    * Take an array of entries to be updated in the Moodle Gradebook
    * 
    * @param array $gradeBookEntry
    */
   public function saveToGradeBook($gradeBook)
   {
      $this->logger->debug("saveToGradeBook, session id = " .  $gradeBook->sessionid . 
            " course id = " . $gradeBook->courseid . " grademax = " . $gradeBook->grademax);;
      
      if ($gradeBook->grades){
         $grades = $gradeBook->grades->getStdClassObject();
      }else{
         $grades = null;
      }
      $result = grade_update($this::MOD_NAME, $gradeBook->courseid,
            'mod', 'elluminate', $gradeBook->sessionid, 0, $grades, $gradeBook->getStdClassObject());
      $this->logger->debug("saveToGradeBook for session ID: " . $gradeBook->sessionid . " result = " . $result);
      return $result;
   }
   /**
    * Reset all grades for a particular course and session
    * 
    * @param unknown_type $courseid
    * @param unknown_type $instanceid
    */
   public function resetAllGrades($courseid,$instanceid)
   {
      $this->logger->debug("resetAllGrades for session ID: " . $instanceid);
      $params['reset'] = true;
      $grades = NULL;
      return grade_update($this::MOD_NAME, $courseID,
            'mod', 'elluminate', $instanceID, 0, $grades, null);
   }
   
   /**
    * 
    * 
    * @param unknown_type $gradeBook
    */
   public function deleteGradeBook($gradeBook)
   { 
      $sessionid = $gradeBook->sessionid;
      $courseid = $gradeBook->courseid;
      $this->logger->debug("deleteGradeBook for session ID: " . $sessionid . " course id ". $courseid);
      
      return grade_update($this::MOD_NAME, $courseid, 'mod',
            'elluminate', $sessionid, 0, NULL, array ('deleted' => 1));
   }
   
   /**
    * When updating grades, we set it to the max value that can possibly by acheived.
    * (sessions are graded as all or nothing)
    *
    * This function returns either the max numeric or scaled value
    *
    * @param String $grade
    * @return string
    */
   public function getMaxGradeValue($grade)
   {
      $this->logger->debug("getMaxGradeValue: " . $grade);
      if ($grade < 0) {
         $maxGradeValue = $this->getScaledGradeMaxValue($grade);
      } else {
         $maxGradeValue = $grade;
      }
      $this->logger->debug("getMaxGradeValue return: " . $maxGradeValue);
      return $maxGradeValue;
   }
   
   /**
    * For scaled grades, use the moodle function make_grades_menu to get an array
    * of all the different items in the selected scale (ID passed in $grade).
    * 
    * Then use the php key() function to return the first element in the returned
    * array, which is effectively the "best" grade possible in the scale.
    *
    * @param string $grade
    * @return string
    */
   private function getScaledGradeMaxValue($grade){
      $grades = make_grades_menu($grade);
      $maxScale =  key($grades);
      $this->logger->debug("getScaledGradeMaxValue : " . $maxScale);
      return $maxScale;
   }

   /**
    * Based on a scaled grade ID ($grade), return an array of items in that
    * scale used to create an HTML select element
    * 
    * @see attendance.php
    * 
    * @param unknown_type $grade
    * @return string
    */
   public function getScaledGradeMenu($grade)
   {
      $moodleMenu = make_grades_menu($grade);
      $moodleMenu[0] = get_string('notattendedyet','elluminate');
      return $moodleMenu;
   }
   
   public function getGradeSelectMenu($options,$selected){
      return html_writer::select($options,
         self::GRADE_SELECT_NAME,
         $selected,
         self::BLANK_SELECT_OPTION);
   }
}