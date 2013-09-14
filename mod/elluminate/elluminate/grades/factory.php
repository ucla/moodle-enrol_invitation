<?php
class Elluminate_Grades_Factory{
   
   public function newAttendance(){
      global $ELLUMINATE_CONTAINER;
      return $ELLUMINATE_CONTAINER['gradesAttendance'];
   }
   
   public function newGradeBook(){
      global $ELLUMINATE_CONTAINER;
      return $ELLUMINATE_CONTAINER['gradesGradeBook'];
   }
   
   public function newGradeBookEntry(){
      global $ELLUMINATE_CONTAINER;
      return $ELLUMINATE_CONTAINER['gradesGradeBookEntry'];
   }
}
