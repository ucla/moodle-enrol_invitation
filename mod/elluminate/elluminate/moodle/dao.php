<?php

/**
 * This class encapsulates some of the DB-related functions needed
 * by the elluminate module
 * @author dwieser
 *
 */
class Elluminate_Moodle_DAO{
   
   private $logger;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Moodle_DAO");
   }
   
   /**
    * Given a course ID, get a list of all users in that course
    * @param unknown_type $courseId
    */
   public function getAllCourseUsers($courseId){
      global $DB;
      
      try {
      	return $DB->get_records_sql("select u.id, u.firstname, u.lastname,
            u.username from {role_assignments} ra, {context} con, {course} c,
            {user} u where ra.userid=u.id and ra.contextid=con.id and
            con.instanceid=c.id and c.id=" . $courseId);
      } catch (Exception $e) {
      	throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
   }
   
   /**
    * Give a list of moodle user IDs, return an array populated with user objects
    * 
    * @param array $userIdList
    */
   public function loadUserList($useridArray){
      global $DB;
      $users = Array();
      foreach ($useridArray as $userid) {
         $sql = "SELECT mu.* FROM {user} mu
      			WHERE mu.id = :participant";
         $sql_params = array('participant'=>$userid);
         try {
         	$user = $DB->get_record_sql($sql, $sql_params);
         } catch (Exception $e) {
      	    throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
         }
         $users[] = $user;
      }
      return $users;
   }
   
   public function addConfigRecord($configRecord){
      global $DB;
      try {
      	$DB->insert_record('config', $configRecord);
      } catch (Exception $e) {
      	throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
   }
   
   public function updateConfigRecord($configRecord){
      global $DB;
      try {
      	$DB->update_record('config', $configRecord);
      } catch (Exception $e) {
      	throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
   }

   public function getConfigRecord($configKey = null){
      global $DB;
      try {
      	return $DB->get_record('config', array('name'=>$configKey));
      } catch (Exception $e) {
      	throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
   }
   
   public function deleteConfigRecord($configKey = null){
      global $DB;
      try {
         return $DB->delete_records('config', array('name'=>$configKey));
      } catch (Exception $e) {
         throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
   }
    
   
   public function getMoodleUserRecord($userid){
      global $DB;
      try {
      	$dbuser = $DB->get_record('user', array('id'=>$userid));
      } catch (Exception $e) {
      	throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
      
      $user = new StdClass;
      $user->id = $userid;
      $user->firstname = trim($dbuser->firstname);
      $user->lastname = trim($dbuser->firstname);
      $user->displayname = trim($dbuser->firstname) . ' ' . trim($dbuser->lastname);
      return $user;
   }
   
   public function getScaledGradeValue($scaleid){
      global $DB;
      try {
      	return $DB->get_record('scale', array('id'=>$scaleid));
      } catch (Exception $e) {
      	throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
   }
   
   public function getCourseModerators($context){
      try {
      	return get_users_by_capability($context, 'mod/elluminate:moderatemeeting',
            'u.id, u.firstname, u.lastname, u.username', 'u.lastname, u.firstname',
            '', '', '', '', false);
      } catch (Exception $e) {
      	throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
   }
   
   public function switchCourseModuleToGroupMode($sessionid,$groupmode){
      global $DB;
      try {
          $this->logger->debug("switchCourseModuleToGroupMode for Session ID: " . $sessionid);
   	  	$courseModule = get_coursemodule_from_instance('elluminate', $sessionid);
         $courseModule->groupmode = $groupmode;
         $DB->update_record('course_modules', $courseModule);
      } catch (Exception $e) {
      	throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
   }
   
   public function getCourseObject($courseid){
      global $DB;
      return $DB->get_record('course', array('id' => $courseid));
   }
}