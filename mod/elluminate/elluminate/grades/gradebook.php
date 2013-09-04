<?php
class Elluminate_Grades_Gradebook{
    
   private $itemname;
   private $idnumber;
   private $gradetype;
   private $grademin;
   private $grademax;
   private $scaleid;
   private $sessionid;
   private $courseid;
   private $grades;
   private $sessiongrade;
   
   //External Dependencies
   private $gradesAPI;
   private $gradesFactory;
   
   private $logger;
   
   const RESET_GRADE = null;
   
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
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Grades_Gradebook");
   }
   
   public function init($sessionid, $courseid, $sessionname = null, $sessiongrade = null){
      $this->sessionid = $sessionid;
      $this->itemname = $sessionname;
      $this->courseid = $courseid;
      $this->sessiongrade = $sessiongrade;
      $this->processGradeValues($sessiongrade);
      $this->logger->debug("Initialized new gradebook: " . $this);
   }
    
   private function processGradeValues($grade){
      if ($grade > 0){
         $this->gradetype = GRADE_TYPE_VALUE;
         $this->grademax = $grade;
         $this->grademin = 0;
      } else if ($grade < 0) {
         $this->gradetype = GRADE_TYPE_SCALE;
         $this->scaleid = - $grade;
      } else {
         $this->gradetype = GRADE_TYPE_TEXT; 
         $this->grademax = 0;
         $this->grademin = 0;
      }
   }
   
   public function save(){
      $this->gradesAPI->saveToGradeBook($this);
   }
   
   public function delete(){
      $this->gradesAPI->deleteGradeBook($this);
   }
   
   /**
    * This function will add a gradebook entry with the maximum grade for the 
    * session
    * 
    * @param unknown_type $userid
    * @param unknown_type $maxgrade
    */
   public function addMaxValueEntry($userid){
      $maxGrade = $this->gradesAPI->getMaxGradeValue($this->sessiongrade);
      $gradeEntry = $this->gradesFactory->newGradeBookEntry();
      $gradeEntry->init($userid,$maxGrade);
      $this->grades = $gradeEntry;
   }
   
   /**
    * This function will add a gradebook entry with a specific grade
    *
    * @param unknown_type $userid
    * @param unknown_type $maxgrade
    */
   public function addEntry($userid, $grade){
      $gradeEntry = $this->gradesFactory->newGradeBookEntry();
      $gradeEntry->init($userid,$grade);
      $this->grades = $gradeEntry;
   }
   
   /**
    * This function will add a gradebook entry with a grade of null, which
    * causes the gradebook entry to be reset.
    * 
    * This is required specifically for scaled grades, which cannot be set to
    * "not attended" since the scale must be maintained.
    *
    * @param unknown_type $userid
    * @param unknown_type $maxgrade
    */
   public function resetEntry($userid){
      $gradeEntry = $this->gradesFactory->newGradeBookEntry();
      $gradeEntry->init($userid,$this::RESET_GRADE);
      $this->grades = $gradeEntry;
   }
   
   /**
    * Helper function to return a StdClass version of this object
    * to allow public access on all member variables.
    *
    * @return StdClass
    */
   public function getStdClassObject()
   {
      return get_object_vars($this);
   }
   
   public function __toString(){
      return "GradeBook::name=" . $this->itemname . 
         "::sessionid=" . $this->sessionid . 
         "::courseid=" . $this->courseid . 
         "::grademax=" . $this->grademax . 
         "::gradetype=" . $this->gradetype .
         "::scaleid=" . $this->scaleid;
   }
}