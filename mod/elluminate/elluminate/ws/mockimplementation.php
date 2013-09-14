<?php
/**
 * Description of sas_implementation
 *
 * @author Danny Wieser
 */
class Elluminate_WS_MockImplementation implements Elluminate_WS_SchedulingManager {

   function __construct() {
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_MockImplementation");
   }

   public function createSession($Session) {
      $this->logger->debug("createSession");
      $Session->meetingid='1234';
      return $Session;
   }

   public function deleteRecording($recordingId) {
      $this->logger->debug("deleteRecording");
      return true;
   }

   public function deleteSession($sessionId) {
      $this->logger->debug("deleteSession");
      return true;
   }

   public function deleteSessionPresentation($presentationId, $sessionId) {
      $this->logger->debug("deleteSessionPresentation: " . $presentationId . " for session " . $sessionId);
      return true;
   }

   public function getGuestLink($sessionId) {
      $this->logger->debug("getGuestLink:  id = " . $sessionId);
      return "http://www.blackboard.com";
   }

   public function getRecordingsForSession($sessionId) {

   }

   public function getRecordingsForTime($startTime, $endTime) {
      $this->logger->debug("getRecordingsForTime");
      $mockRecordings = array();
      $recording1 = new Elluminate_Recording();
      $recording1->description = "test1";
      $recording1->recordingsize = "120000";
      $recording1->created = time();
      $recording1->visible = true;
      $recording1->groupvisible = true;
      $recording1->meetingid='1234';
      $recording1->recordingid='1';
      $mockRecordings[] = $recording1;
      
      $recording2 = new Elluminate_Recording();
      $recording2->description = "test2";
      $recording2->recordingsize = "160000";
      $recording2->created = time();
      $recording2->visible = true;
      $recording2->groupvisible = true;
      $recording2->meetingid='1234';
      $recording2->recordingid='2';
      $mockRecordings[] = $recording2;
      return $mockRecordings;
   }

   public function getSession($sessionId) {

   }

   public function getSessionUrl($sessionId, $displayName, $userId = '') {
      $this->logger->debug("getSessionUrl: displayname=" . $displayName . " id = " . $userId);
      return "http://www.blackboard.com";
   }

   public function setSessionPresentation($presentationId, $sessionId) {
      $this->logger->debug("setSessionPresentation : presentation =" . $presentationId . "session =" . $sessionId);
      return true;
   }

   public function testConnection() {
      $this->logger->debug("testConnection");
      return true;
   }

   public function updateSession($Session) {
      $this->logger->debug("updateSession:  id = " . $Session->id);
      return true;
   }

   public function updateUsers($Session) {
      $this->logger->debug("updateUsers for session " . $Session->id);
      return true;
   }

   public function uploadPresentationContent($Preload) {
      $this->logger->debug("uploadPresentationContent for session " . $Preload->id);
      return "5678";
   }
   
   public function convertRecording($recordingid, $format){
      
   }

   private function generateCreateUpdateSessionDetails($Session) {
   
   }

   private function retrieveRecordings($args) {
   
   }

   private function generateRecordingFromValues($values) {
   
   }
}

?>
