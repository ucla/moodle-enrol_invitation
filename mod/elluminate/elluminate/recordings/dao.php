<?php

class Elluminate_Recordings_DAO{

   private $logger;

   public function __construct()
   {
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Recordings_DAO");
   }

   public function loadRecording($id)
   {
      global $DB;
      try {
         $dbRecord = $DB->get_record('elluminate_recordings', array('id'=>$id));
      } catch (Exception $e) {
         throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
      return $dbRecord;
   }
   
   public function loadRecordingByRecordingId($recordingid)
   {
      global $DB;
      try {
         $dbRecord = $DB->get_record('elluminate_recordings', array('recordingid'=>$recordingid));
      } catch (Exception $e) {
         throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
      return $dbRecord;
   }

   /**
    * Save a NEW recording object to the database.
    *
    * Validation:
    *
    * -Parent Session must exist
    * -Not added if recording ID already present in DB.
    *
    * If the recording already exists, we issue a warning only.   The cron
    * add action is the only class that calls this.  While it shouldn't happen
    * that the same recording is added more than once, it is possible and shouldn't
    * cause the cron to fail.
    *
    * @param Elluminate_Recording $recording
    */
   public function saveRecording($recording)
   {
      global $DB;
      $id = null;
      //Don't insert if already exists
      if (!$this->doesRecordingExist($recording)) {
         $id = $DB->insert_record('elluminate_recordings', $recording->getDBInsertObject());
         $this->logger->info("Saved Recording with SAS ID: " . $recording->recordingid);
      } else {
         $this->logger->warn("Could not update recording with ID: " . $recording->recordingid .
                  ". Already exists or parent session invalid.");
      }
      return $id;
   }

   /**
    * UPDATE an existing recording object to the database.
    *
    * Validation:
    *
    * -Parent Session must exist
    *
    * @param Elluminate_Recording $recording
    */
   public function updateRecording($recording)
   {
      global $DB;
      try {
         $DB->update_record('elluminate_recordings', $recording->getDBInsertObject());
      } catch (Exception $e) {
         throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
      return true;
   }

   /**
    * Check if the given recording already exists in the DB
    *
    * @param Elluminate_Recording $recording
    */
   private function doesRecordingExist($recording)
   {
      global $DB;
      try {
         return $DB->record_exists('elluminate_recordings', array('recordingid'=>$recording->recordingid));
      } catch (Exception $e) {
         throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
   }

   /**
    * Get a list of all child recordings for a session
    * @param unknown_type $parentSession
    */
   public function getRecordingList($meetingid)
   {
      global $DB;
      try {
         return $DB->get_records('elluminate_recordings',
                  array('meetingid'=>$meetingid), 'created ASC');
      } catch (Exception $e) {
         throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
   }
   
   /**
    * Get a list of all recordings
    * @param unknown_type $parentSession
    */
   public function getAllRecordings()
   {
      global $DB;
      try {
         return $DB->get_records('elluminate_recordings');
      } catch (Exception $e) {
         throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
   }

   /**
    * Delete a recording from the database
    *
    * @param string $recordingid - SAS recording ID
    */
   public function deleteRecording($id, $recordingid)
   {
      global $DB;
      try {
         $DB->delete_records('elluminate_recording_files', array('recordingid'=>$recordingid));
         $DB->delete_records('elluminate_recordings', array('id'=>$id));
      } catch (Exception $e) {
      	  throw new Elluminate_Exception("Could not delete recording " . $this->recordingid .", error: " . $e->getMessage(), $e->getCode(), 'user_error_database');
      }
      return true;
   }

   public function getRecordingFileIdsByStatusAndTime($statusArray, $filterTime){
      global $DB;

      $sort = '';
      $fields = 'id,recordingid';
      list($insql, $inparams) = $DB->get_in_or_equal($statusArray);
      array_push($inparams, $filterTime);
      $select = "status " . $insql . " and updated < ?";
      $this->logger->debug("getRecordingFileIdsByStatusAndTime query ["  . $select . "]");
      $this->logger->debug("getRecordingFileIdsByStatusAndTime values ['" . implode("','", $inparams) . "']");
      $result = null;
      try {
         $result = $DB->get_records_select('elluminate_recording_files',$select,$inparams,$sort,$fields);    
      }catch (Exception $e) {
         throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }

      return $result;
   }
    
   public function getRecordingFiles($recordingid){
      global $DB;
      return $DB->get_records('elluminate_recording_files',
               array('recordingid'=>$recordingid), 'format DESC');
   }

   public function saveRecordingFile($recordingFile){
      global $DB;
      $id = null;
      try {
         $id = $DB->insert_record('elluminate_recording_files', $recordingFile->getDBInsertObject());
      } catch (Exception $e) {
         throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
      return $id;
   }
    
   public function updateRecordingFile($recordingFile){
      global $DB;
      try {
         $DB->update_record('elluminate_recording_files', $recordingFile->getDBInsertObject());
      } catch (Exception $e) {
         throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
   }
    
   public function loadRecordingFileByIDFormat($recordingid, $format){
      global $DB;
      $result = null;
      try {
         $conditions = array('recordingid'=>$recordingid, 'format'=>$format);
         $result = $DB->get_record('elluminate_recording_files',$conditions);
      } catch (Exception $e) {
         throw new Elluminate_Exception($e->getMessage(), $e->getCode(), 'user_error_database');
      }
      return $result;
   }
}