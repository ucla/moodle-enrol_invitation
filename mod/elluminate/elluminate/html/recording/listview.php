<?php

class Elluminate_HTML_Recording_ListView{
   const URL_AND = "&amp;";
   const NO_RECORDINGS = '';
   const NO_VALUE = "-";
   
   //Details for current user session
   private $courseModuleId; 
   private $permissions; 
   private $pageSession;
   
   //Helper for HTML moodle output
   private $output;  
   private $recordingLoader;
   private $recordingListTable;
   
   private $logger;
   
   public function __set($property, $value){
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_HTML_Recording_View");
   }
  
   public function getRecordingTable($recordingeditid=''){  
      if (!$this->permissions->doesUserHaveGeneralRecordingViewPermissions()){
         return self::NO_RECORDINGS;
      }      

      //MOOD-462 - Session with no ID cannot have recordings
      if ($this->pageSession->meetingid == null){
         return self::NO_RECORDINGS;
      }

      $recordingsExist = $this->recordingListTable->initTable($this->pageSession);
      $this->recordingListTable->courseModuleId = $this->courseModuleId;
      $this->recordingListTable->rootPageUrl = Elluminate_HTML_Session_View::getPageUrl($this->courseModuleId);
      
      if ($recordingsExist){
         return $this->recordingListTable->getTableOutput($recordingeditid);
      }else{
         return self::NO_RECORDINGS;
      }
   }
   
   /**
    * Get HTML for confirmation message for recording delete.
    *
    * This uses the moodle $OUTPUT object to build the page structure
    * @return HTML text for confirmation page.
    */
   public function getDeleteRecordingConfirmationHTML($recordingid){
      global $OUTPUT;

      $recording = $this->loadRecording($recordingid);

      $deleteRecordingConfirmationMessage = get_string('deleterecordingconfirm',
                'elluminate', userdate($recording->created));

      $deleteRecordingConfirmationHTML = $OUTPUT->confirm($deleteRecordingConfirmationMessage,
               $this->getDeleteConfirmationLink($recording),
               $this->getDeleteCancelLink());

      return $deleteRecordingConfirmationHTML;
   }

   /**
    * Get the URL link that is used to confirm deletion of a recording
    */
   public function getDeleteConfirmationLink($recording){
      global $USER;
      
      $deleteRecordingConfirmationLink = '';
      $deleteRecordingConfirmationLink = Elluminate_HTML_Session_View::getPageUrl($this->courseModuleId);
      $deleteRecordingConfirmationLink .= $this::URL_AND . 'delrecording=' . $recording->id;
      $deleteRecordingConfirmationLink .= $this::URL_AND . 'delconfirm=' . sesskey();
      return $deleteRecordingConfirmationLink;
   }

   /**
    * Users chooses to cancel deletion, use this link to redirect back to main view page.
    * @return string
    */
   public function getDeleteCancelLink(){
      return Elluminate_HTML_Session_View::getPageUrl($this->courseModuleId);
   }
   /**
    * Handle deletion of a recording and the associated UI functionality
    * @param unknown_type $recordingid
    */
   public function deleteRecordingAction($recordingid) {
      try {
         $recording = $this->loadRecording($recordingid);
         if ($recording != null){
            $recording->delete();
         }else{
            echo $this->output->notify(get_string('recordingidinvalid','elluminate',$recordingid));
         }
      } catch (Elluminate_Exception $e) {
         print_error(get_string($e->getUserMessage(),'elluminate'));
      } catch (Exception $e) {
         print_error(get_string('user_error_processing', 'elluminate'));
      }
   }

   /**
    * Load recording by id, set it to hidden and then update in DB
    * @param unknown_type $recordingid
    */
   public function hideRecordingAction($recordingid){
      try {
         $recording = $this->loadRecording($recordingid);
         $recording->hideRecording();
         $recording->updateRecording();
      } catch (Elluminate_Exception $e) {
         print_error(get_string($e->getUserMessage(),'elluminate'));
      } catch (Exception $e) {
         print_error(get_string('user_error_processing', 'elluminate'));
      }
   }

   /**
    * Load recording by id, set it to show and then update in DB
    * @param unknown_type $recordingid
    */
   public function showRecordingAction($recordingid){
      try {
         $recording = $this->loadRecording($recordingid);
         $recording->showRecording();
         $recording->updateRecording();
      } catch (Elluminate_Exception $e) {
         print_error(get_string($e->getUserMessage(),'elluminate'));
      } catch (Exception $e) {
         print_error(get_string('user_error_processing', 'elluminate'));
      }
   }

   /**
    * Action to handle editing of the recording description.
    *
    * @param unknown_type $recordingid
    * @param unknown_type $updatedDescription
    */
   public function editRecordingDescriptionAction($recordingid, $updatedDescription){
      try {
         $recording = $this->loadRecording($recordingid);
         $recording->description = $updatedDescription;
         $recording->updateRecording();
      } catch (Elluminate_Exception $e) {
         print_error(get_string($e->getUserMessage(),'elluminate'));
      } catch (Exception $e) {
         print_error(get_string('user_error_processing', 'elluminate'));
      }
   }
  
   /**
    * Helper Method to get a recording from DB and trap any errors
    * @param unknown_type $recordingID
    */
   private function loadRecording($id){
      try {
         $recording = $this->recordingLoader->getRecordingById($id);
      } catch (Elluminate_Exception $e) {
         print_error(get_string($e->getUserMessage(),'elluminate'));
      } catch (Exception $e) {
         print_error(get_string('user_error_processing', 'elluminate'));
      }
      return $recording;
   }
}