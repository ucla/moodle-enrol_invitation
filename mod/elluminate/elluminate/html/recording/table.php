<?php

class Elluminate_HTML_Recording_Table{
   //Regular User with no admin or conversion
   const DEFAULT_MODE_COLS = 3;
    
   //Admin User - Adds Option Column
   const ADMIN_MODE_COLS = 4;
    
   //Convert Mode - Add Convert Column
   const CONVERT_MODE_COLS = 5;
  
   private $logger;
   
   //External Class Dependencies 
   private $htmlTable;
   private $output;
   private $permissions;
   private $recordingLoader;
   
   private $conversionMode;
   private $adminMode;
   private $singleRecordingMode = false;
   private $numColumns;

   private $rootPageUrl;
   
   //Parent Session, Course Module ID for parent Session
   private $courseModuleId;
   private $parentSession;
    
   private $meetingid;
   private $recordingList;
   
   //Recording and Recording File Actions
   private $recordingActions;
   private $recordingFileActions;
   
   //Recording Description is being edited for ID
   private $recordingEditDescriptionID;
    
   //TABLE DETAILS
   private $names = array('play','created','description','options','convert');
   private $styles = array('elluminaterecordingplay','elluminaterecordingcreated','','elluminaterecordingoptions','');
   private $valueFunctions = array('getPlayOptions','getDate','getDescription','getOptions','getConvert');
   private $justification = array('center','left','left','center','center');
   private $columnHeaderKeys = array('recordingplaytitle','recordingdatetitle','description',
            'recordingoptionstitle',
            'conversiontitle');
    
   public function __construct (){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_HTML_Recording_Table");
      $this->adminMode = false;
      $this->conversionMode = false;
      $this->singleRecordingMode = false;
   }
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   public function setParentSession($parentSession){
      $this->parentSession = $parentSession;
   }
   
   public function setSingleRecording($recording){
      $this->recordingList = array();
      $this->recordingList[] = $recording;
      $this->singleRecordingMode = true;
   }
   
   public function setAdminMode($adminMode){
      $this->adminMode = $adminMode;
   }
    
   public function initTable($parentSession = null){
      $this->checkPermissions();
      if ($parentSession){  
         $this->parentSession = $parentSession;
         $this->meetingid = $parentSession->meetingid;
      }
      
      $this->processColumnCount();
      $this->tableTitle();
      $this->tableHeaderRow();
      
      if (!$this->singleRecordingMode){
         $this->loadRecordingList();
      }
      
      if (sizeof($this->recordingList) == 0){
         return false;
      }else{
         return true;
      }
   }
    
   public function getTableOutput($recordingeditid = null){ 
      $this->recordingEditDescriptionID = $recordingeditid;
      $this->processRecordingList();   
      return $this->htmlTable->getTableOutput();
   }
   
   private function checkPermissions(){
      if ($this->permissions->doesUserHaveRecordingAdminPermissions()){        
         $this->adminMode = true;
      }
      
      if ($this->permissions->doesUserHaveConvertPermissionsForRecording()){
         $this->conversionMode = true;
      }
   }
    
   private function tableTitle(){
      $this->htmlTable->init($this->getColumnAlignment());
      $this->htmlTable->addHeaderRow(get_string('recordings','elluminate'),$this->numColumns);
   }
   
   private function tableHeaderRow(){
      $cnt = 0;
      $headerKeys = array();
      while ($cnt < $this->numColumns){
         $headerKeys[] = $this->columnHeaderKeys[$cnt];
         $cnt++;
      }
      $this->htmlTable->addColumnHeaders($headerKeys);
   }
    
   private function processColumnCount(){
      $this->numColumns = self::DEFAULT_MODE_COLS;
      if ($this->adminMode){
         $this->numColumns = self::ADMIN_MODE_COLS;
      }
      if ($this->conversionMode){
         $this->numColumns = self::CONVERT_MODE_COLS;
      }
   }
    
   private function getColumnAlignment(){
      $cnt = 0;
      $colAlign = array();
      while ($cnt < $this->numColumns){
         $colAlign[] = $this->justification[$cnt];
         $cnt++;
      }
      return $colAlign;
   }
    
   private function loadRecordingList(){
      try {
         $this->recordingList = $this->recordingLoader->getRecordingsForMeetingId($this->meetingid);
      } catch (Elluminate_Exception $e) {
         notify(get_string($e->getUserMessage(), 'elluminate'));
      } catch (Exception $e) {
         notify(get_string('user_error_soaperror', 'elluminate'));
      }
      $this->logger->debug("loadRecordingList: Loaded [" . sizeof($this->recordingList) . "] recordings");
   }
    
   private function processRecordingList(){
      $rowsAdded = false;
      foreach($this->recordingList as $recording){
         //Build actions helper used to build links and icons for recording
         $recordingActions = $this->loadRecordingActions($recording);
         $this->permissions->setCurrentRecording($recording);
         if ($this->permissions->doesUserHaveViewPermissionsForRecording()){
            $this->recordingRow($recording,$recordingActions);
            $rowsAdded = true;
         }
      }
      
      //All recordings are hidden
      if (! $rowsAdded){
         $this->htmlTable->addSpanRow(get_string('norecordingsavailable','elluminate'),$this->numColumns);
      }
   } 
   
   private function loadRecordingActions($recording){
      $recordingActions = new Elluminate_HTML_Recording_Actions($recording);
      $recordingActions->rootUrl = $this->rootPageUrl;
      $recordingActions->output = $this->output;
      return $recordingActions;
   }
    
   private function recordingRow($recording,$recordingActions){
      $cellValues = array();
      $cellStyles = array();
      $cnt = 0;
      while ($cnt < $this->numColumns){
         $name = $this->names[$cnt];
         $cellStyles[$name] = $this->styles[$cnt];
          
         //Dynamically call function to get cell values
         $valueFunction = $this->valueFunctions[$cnt];
         $cellValues[$name] = $this->$valueFunction($recording,$recordingActions);
          
         $cnt ++;
      }
      $this->htmlTable->addRow($cellValues,$cellStyles);
   }

   private function getDate($recording,$recordingActions = null){
      return userdate($recording->created);
   }
    
   private function getDescription($recording,$recordingActions = null){
      $recordingDesc = '';
      if ($recording->id == $this->recordingEditDescriptionID){
         $recordingDesc = $recordingActions->getRecordingEditForm($this->courseModuleId);
      }else{
         $recordingDesc = $recording->description;
      }
      return $recordingDesc;
   }

   private function getOptions($recording, $recordingActions){      
      $recordingOptions = '';

      if ($this->permissions->doesUserHaveConvertPermissionsForRecording()){
         $recordingOptions .= $recordingActions->getViewRecordingIcon();
      }

      if ($this->permissions->doesUserHaveEditDescriptionPermissionsForRecording()){
         $recordingOptions .= $recordingActions->getEditRecordingIcon();
      }

      if ($this->permissions->doesUserHaveDeletePermissionsForRecording()){
         $recordingOptions .= $recordingActions->getDeleteRecordingIcon();
      }

      if ($this->permissions->doesUserHaveToggleVisibilityPermissionsForRecording()){
         $recordingOptions .= $recordingActions->getManageRecordingIcon();
      }

      if ($this->parentSession->isGroupSession() &&
            $this->permissions->doesUserHaveToggleGroupVisibilityPermissionsForRecording()){
               $recordingOptions .= $recordingActions->getGroupModeIcon();
      }

      if ($recordingOptions == ''){
         $recordingOptions = self::NO_VALUE;
      }

      return $recordingOptions;
   }
    
   private function getConvert($recording,$recordingActions){
      return $recordingActions->getConvertActions();
   }
    
   private function getPlayOptions($recording, $recordingActions){
      return $recordingActions->getPlayActions($this->permissions);
   }
}