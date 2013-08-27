<?php
interface Elluminate_Group_Access{
	
   public function getGroupName($groupId);
   public function getAvailableGroups($moodleContextModule);
   public function getAllGroups($course);
   public function getAllGroupsForGrouping($course,$groupingid);
}