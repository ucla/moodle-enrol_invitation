<?php

class Elluminate_Moodle_GroupAccess implements Elluminate_Group_Access{
   
	const BLANK_USER_ID = 0;
	
   private $logger;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Moodle_GroupAccess");
   }
   
   public function getGroupName($groupid){
   	return groups_get_group_name($groupid);
   }
   
   public function getAvailableGroups($moodleContextModule){
   	return groups_get_activity_allowed_groups($moodleContextModule);
   }  
   
   public function getAllGroupsForGrouping($course,$groupingid){
		return groups_get_all_groups($course,$this::BLANK_USER_ID,$groupingid);
   }
   
   public function getAllGroups($course){
   	return groups_get_all_groups($course);
   }
   
   public function doesGroupExist($groupid){
      return groups_group_exists($groupid);
   }
}