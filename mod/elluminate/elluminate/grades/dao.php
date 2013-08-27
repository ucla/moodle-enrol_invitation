<?php
class Elluminate_Grades_DAO{
	
	public function getSessionUserGrade($sessionID, $userID)
	{
		global $DB;
		try {
			return $DB->get_record('elluminate_attendance', array('elluminateid'=>$sessionID,'userid'=>$userID));
		} catch (Exception $e) {
			throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
		}
	}
	
	public function updateSessionAttendance($attendance)
	{
		global $DB;
		try {
			$DB->update_record('elluminate_attendance', $attendance->getDBInsertObject());
		} catch (Exception $e) {
			throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
		}
		return true;
	}
	
	public function saveNewSessionAttendance($attendance)
	{
		global $DB;
		try {
			$DB->insert_record('elluminate_attendance', $attendance->getDBInsertObject());
		} catch (Exception $e) {
			throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
		}
		return true;
	}
	
	public function getAllSessionAttendance($sessionid)
	{
		global $DB;
		try {
			return $DB->get_records('elluminate_attendance', array('elluminateid'=>$sessionid));
		} catch (Exception $e) {
			throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
		}
	}
	
	public function getScaleUsedAnywhere($scaleid) 
	{
		global $DB;
		try {
			return $DB->record_exists('elluminate', array('grade'=>-$scaleid));
		} catch (Exception $e) {
			throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
		} 
	}
	
	function getSessionAttendeeCount($sessionId)
	{
	   global $DB;
	   $select = "elluminateid = ? and grade > ?";
	   $params = array('sessionId'=>$sessionId,'grade'=>0);
	   return $DB->count_records_select('elluminate_attendance', $select,$params,'count(id)'); 
	}	
}