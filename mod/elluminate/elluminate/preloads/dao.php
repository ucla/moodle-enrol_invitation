<?php
class Elluminate_Preloads_DAO{
   
   public function savePreload($preload){
      global $DB;
      $id = $DB->insert_record('elluminate_preloads', $preload->getDBInsertObject());
      if (!$id){
         throw new Elluminate_Exception('Could not save preload to DB', 0, 'user_error_database');
      }
      return $id;
   }
   
   public function getPreloadForMeeting($meetingid){
      global $DB;
      return $DB->get_record('elluminate_preloads', array('meetingid'=>$meetingid));
   }
   
   public function getPreloadByPresentationId($presentationid){
      global $DB;
      return $DB->get_record('elluminate_preloads', array('presentationid'=>$presentationid));
   }
   
   public function deletePreloadByPresentationId($presentationid){
      global $DB;
      try {
      	$DB->delete_records('elluminate_preloads', array('presentationid'=>$presentationid));
      } catch (Exception $e) {
      	throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
      return true;
   }
}