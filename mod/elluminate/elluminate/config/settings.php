<?php
class Elluminate_Config_Settings{
	
	/**
	 * This tests for a valid connection to the configured Blackboard Collaborate server's
	 * web service interface.
	 * @param string $serverurl The URL pointing to the Blackboard Collaborate manager 
	 * @param string $username  The authentication username 
	 * @param string $password  The authentication password
	 * @return boolean True on successful test, False otherwise.
	 */
	public static function testConnection($serverurl, $username, $password){
		$schedulingManager = Elluminate_WS_SchedulingManagerFactory::getSchedulingManagerWithSettings($serverurl, $username, $password);
	}	
	
	public function areCollaborateSettingsValid(){
	   global $CFG;
	   $settingsValid = false;

      if (! empty($CFG->elluminate_auth_username) &&
          ! empty($CFG->elluminate_auth_password) &&
          ! empty($CFG->elluminate_server) ){
         $settingsValid = true;
      }
      return $settingsValid;
	}
	
	/**
	 * Get the current configuration settings for file size.
	 * TODO: test
	 * @return stdClass
	 */
	public static function getFileSizeConfig(){
	   $a = new stdClass;
	   $a->uploadmaxfilesize = ini_get('upload_max_filesize');
	   $a->postmaxsize = ini_get('post_max_size');
	   return $a;
	}
	
	public static function getElluminateSetting($settingName){
	   global $CFG;
	   $cfgValue = null;
	   if (isset($CFG->$settingName)){
	      $cfgValue = $CFG->$settingName;
	   }
	   return $cfgValue;
	}
	
	public static function setElluminateSetting($name, $value){
		set_config($name, $value);
	}
}