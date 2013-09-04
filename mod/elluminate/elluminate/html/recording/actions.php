<?php

require_once($CFG->libdir.'/weblib.php');

/**
 * This class is used to generate HTML view layer actions related to a particular recording
 *    -Delete
 *    -Edit Description
 *    -Toggle Hide/Show Visibility
 *    -View Conversion Details
 *
 *
 * @author dwieser
 *
 */
class Elluminate_HTML_Recording_Actions{
   private $recording;
   private $output;

   private $logger;
   
   private $rootUrl;

   public function __construct($recording){
      $this->recording = $recording;
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_HTML_Recording_Actions");
   }
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }

   public function getPlayActions($permissions){
      $playOptions = '';
      foreach($this->recording->recordingFiles as $recordingFile){
         $fileActions = new Elluminate_HTML_Recording_FileActions($this->recording, $recordingFile, $this->output);

         if ($recordingFile->format != Elluminate_Recordings_Constants::VCR_FORMAT){
            if ($permissions->isMP4PlaybackEnabled()){
               $playOptions .= $fileActions->getActionIcon();
            }
         }else{
            $playOptions .= $fileActions->getActionIcon();
         }
      }
      return $playOptions;
   }
   
   public function getConvertActions(){
      $convertActions = '';
      $mp3RecordingFile = $this->recording->getRecordingFileByFormat(Elluminate_Recordings_Constants::MP3_FORMAT);
      if ($mp3RecordingFile != null){
         $fileActions = new Elluminate_HTML_Recording_FileActions($this->recording, $mp3RecordingFile, $this->output);
         $convertActions .= $fileActions->getConvertLink();
      }
      
      $mp4RecordingFile = $this->recording->getRecordingFileByFormat(Elluminate_Recordings_Constants::MP4_FORMAT);
      if ($mp4RecordingFile != null){
         $fileActions = new Elluminate_HTML_Recording_FileActions($this->recording, $mp4RecordingFile, $this->output);
         $convertActions .= $fileActions->getConvertLink();
      }
      return $convertActions;
   }

   /**
    * Build HTML for Viewing Recording and managing the detailss
    * @param unknown_type $recording
    * @return string
    */
   public function getViewRecordingIcon()
   {
      return $this->output->getActionIcon(Elluminate_HTML_Recording_DetailView::getPageUrl($this->recording->recordingid),
                'settings', 'viewrecordingdescription');
   }

   /**
    * Build HTML for Edit Recording Description Action Icon
    * @param unknown_type $recording
    * @return string
    */
   public function getEditRecordingIcon()
   {
      $url = $this->output->getMoodleUrl($this->rootUrl . '&editrecordingdesc=' . $this->recording->id);
      return $this->output->getActionIcon($url, 'edit', 'editrecordingdescription');
   }

   /**
    * Build HTML for Delete Action Icon
    * @param unknown_type $recording
    * @return string
    */
   public function getDeleteRecordingIcon()
   {
      $url = $this->output->getMoodleUrl($this->rootUrl . '&delrecording=' . $this->recording->id);
      return $this->output->getActionIcon($url, 'delete', 'deletethisrecording');
   }

   /**
    * Build HTML for either hide or show icon, depending on current state
    * @param unknown_type $recording
    * @return string
    */
   public function getManageRecordingIcon()
   {
      if ($this->recording->visible) {
         return $this->getHideRecordingIcon($this->recording);
      }else{
         return $this->getShowRecordingIcon($this->recording);
      }
   }

   /**
    * Build HTML for Show Recording Action Icon
    * @param unknown_type $recording
    * @return string
    */
   public function getShowRecordingIcon()
   {
      $url = $this->output->getMoodleUrl($this->rootUrl . '&showrecording=' . $this->recording->id);
      return $this->output->getActionIcon($url, 'show', 'showthisrecording');
   }


   /**
    * Build HTML for Hide Recording Action Icon
    * @param unknown_type $recording
    * @return string
    */
   public function getHideRecordingIcon()
   {
      $url = $this->output->getMoodleUrl($this->rootUrl . '&hiderecording=' . $this->recording->id);
      return $this->output->getActionIcon($url, 'hide', 'hidethisrecording');
   }

   /*
    * 1.) Session must be a visible group session (recording cannot override separate group mode)
    * 2.) User must have role permission to manage recording group visibility
    *
    * THEN:
    *
    * If recording group visible is ON (1):
    *   -user must be member of specific group to view recording (hidden to others)
    *
    * If recording group visible if OFF(0):
    *   -Visible group mode applies to recordings - anyone enrolled in course can view
    *   recording.
    *   
    *   TODO: complete implementation
    **/
   public function getGroupModeIcon(){
      return '';
   }

   /**
    * Build an HTML form to allow editing of the recording description
    * @param unknown_type $recording
    */
   public function getRecordingEditForm($courseModuleId)
   {
      global $USER, $CFG;
      $description = s($this->recording->description);
      $recordingDescEditForm = '';
       
      $recordingDescEditForm .= '<div class="elluminaterecordingdescriptionedit">';
      $recordingDescEditForm .= '<form action="view.php" method="post">';
      $recordingDescEditForm .= '<input type="hidden" name="id" value="' . $courseModuleId . '" />';
      $recordingDescEditForm .= '<input type="hidden" name="recordingid" value="' . $this->recording->id . '" />';
      $recordingDescEditForm .= '<input type="hidden" name="sesskey" value="' . $USER->sesskey . '" />';
      $recordingDescEditForm .= ' <input type="text" name="recordingdesc" size="50" maxlength="255" value="' . $description  .'" />';
      $recordingDescEditForm .= ' <br/><br/><input type="submit" name="descsave" value="' . get_string('savechanges') . '" />';
      $recordingDescEditForm .= ' <input type="submit" name="cancel" value="' . get_string('cancel') . '" />';
      $recordingDescEditForm .= '</form>';
      $recordingDescEditForm .= '</div>';
      return $recordingDescEditForm;
   }
   
   /**
    * Build HTML for either group visible or non-group visible icon, depending on current state
    * @param unknown_type $recording
    * @return string
    */
   private function getGroupRecordingIcon($recording)
   {
      if ($recording->groupvisible) {
         return $this->getSeparateGroupsRecordingIcon($recording);
      }else{
         return $this->getVisibleGroupsRecordingIcon($recording);
      }
   }
    
   private function getSeparateGroupsRecordingIcon($recording)
   {
      $url = $this->output->getMoodleUrl($this->getRootViewPageUrl() . '&showrecording=' . $recording->id);
      return $this->output->getActionIcon($url, 'groupn', 'separategroups');
   }
    
   /**
    * Build HTML for Hide Recording Action Icon
    * @param unknown_type $recording
    * @return string
    */
   private function getVisibleGroupsRecordingIcon($recording)
   {
      $url = $this->output->getMoodleUrl($this->getRootViewPageUrl() . '&hiderecording=' . $recording->id);
      return $this->output->getActionIcon($url, 'groups', 'visiblegroups');
   }
}