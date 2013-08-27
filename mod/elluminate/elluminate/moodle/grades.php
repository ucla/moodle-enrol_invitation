<?php
class Elluminate_Moodle_Grades{
   
   public static function get_scaled_grade_menu($grade){
      $moodleMenu = make_grades_menu($grade);
      $moodleMenu[0] = get_string('notattendedyet','elluminate');
      return $moodleMenu;
   }
}