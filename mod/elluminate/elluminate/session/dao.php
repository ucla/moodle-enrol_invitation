<?php
class Elluminate_Session_DAO {

   private $logger;
    
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Session_DAO");
   }
   /**
    * Add a session object to the database
    * @param unknown_type $addSession
    * @return unknown
    */
   public function createSession($addSession){
      global $DB;
      $this->logger->info("Adding Session to DB, meetingid = " . $addSession->meetingid);
      try {
      	 $dbSessionId = $DB->insert_record('elluminate', $addSession->getDBInsertObject());
      } catch (Exception $e) {
      	 throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
      return $dbSessionId;
   }
    
   public function getSessionCreator($creatorUserId){
      global $DB;
      try {
      	 return $DB->get_record('user', array('id'=>$creatorUserId));
      } catch (Exception $e) {
      	 throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
   }

   /**
    *
    * @param unknown_type $sessionIdToLoad
    * @return Elluminate_Session $dbSession
    */
   public function loadSession($sessionIdToLoad){
      global $DB;
      try {
      	 $dbRecord = $DB->get_record('elluminate', array('id'=>$sessionIdToLoad));
      } catch (Exception $e) {
      	 throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
      return $dbRecord;
   }
    
   /**
    * Load a session from the database using a primary key of meeting (SAS) id.
    * @param string $sessionIdToLoad
    * @return StdClass Database Object
    */
   public function loadSessionByMeetingId($meetingId){
      global $DB;
      try {
      	 return $DB->get_record('elluminate', array('meetingid'=>$meetingId));
      } catch (Exception $e) {
      	 throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
   }
    
   /**
    * Update an existing session record in the DB.
    * @param unknown_type $updateSession
    */
   public function updateSession($updateSession){
      global $DB;
      try {
      	 $DB->update_record('elluminate', $updateSession->getDBInsertObject());
      } catch (Exception $e) {
      	 throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
      return true;
   }
    
   /**
    * Clear all child records related to a session from the DB and then
    * remove the parent record
    *
    * @param Elluminate_Session $deleteSession
    * @throws Exception
    */
   public function deleteSession($deleteSession){
      global $DB;
      $returnValue = true;
      try {
        $DB->delete_records('elluminate_recordings', array('meetingid'=>$deleteSession->meetingid));
        $DB->delete_records('elluminate_attendance', array('elluminateid'=>$deleteSession->id));
        $DB->delete_records('event', array('modulename'=>'elluminate', 'instance'=>$deleteSession->id));
        $DB->delete_records('elluminate', array('id'=>$deleteSession->id));
      } catch (Exception $e) {
      	throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
      return $returnValue;
   }
    
   public function loadParentSessionName($parentSessionId){
      global $DB;
      $parentName = '';
      try {
         $conditions = array('id'=>$parentSessionId);
         $parentName = $DB->get_field('elluminate', 'sessionname', $conditions);
      } catch (Exception $e) {
         throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
      return $parentName;
   }
   
   public function getSessionsWithRecentRecordings($courseid) {
      global $DB;
      
      $sql = "select e.id, e.sessionname, e.id, ".
             "e.groupmode, e.groupparentid, e.groupid, e.course ".
             "from {elluminate_recordings} er, {elluminate} e where " .
             "er.meetingid = e.meetingid and ".
             "e.sessiontype <> ".Elluminate_Session::PRIVATE_SESSION_TYPE;
      if ($courseid != 1) {
         $sql .= " and e.course = ".$courseid;
      }
      $sql .= " group by e.id, e.sessionname, e.groupmode, ".
              "e.groupparentid, e.groupid, e.course order by created DESC";
      
      $this->logger->debug("About to execute sql: ".$sql);
      
      try {
      	 $result = $DB->get_records_sql($sql);
      } catch (Exception $e) {
      	 throw new Elluminate_Exception("Could not execute sql: " . $sql .", error: " . $e->getMessage(), $e->getCode(), 'user_error_database');
      }
      
      return $result;
   }
}