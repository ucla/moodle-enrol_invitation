<?php

class Elluminate_HTML_Recording_DetailView{
   const PAGE_URL = "recording-detail.php";
   const RECORDING_ID_PARAM = "?rid=";
   const MANUAL_UPDATE_PARAM = "&manualupdate=1";
   
   private $recordingTable;
   private $output;
   private $statusUpdater;
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   public static function getPageUrl($recordingid,$manualUpdate = false){
      $url = self::PAGE_URL . self::RECORDING_ID_PARAM . $recordingid;
      if ($manualUpdate){
         $url .= self::MANUAL_UPDATE_PARAM;
      }
      return $url;
   }
   
   public function getRecordingDetailTable($headerText, $recording){
      $this->recordingTable->init(array('center','center','center','left','center'));
   
      $this->recordingTable->addHeaderRow($headerText, 5);
      $this->recordingTable->addColumnHeaders(array('recordingplaytitle','format','status','converror','lastupdate'));
   
      $conversionInProgress = false;
      foreach($recording->recordingFiles as $recordingFile){
         $fileRow = array();
         $rowStyles = array();
          
         $fileActions = new Elluminate_HTML_Recording_FileActions($recording, $recordingFile, $this->output);
         $fileRow[] = $fileActions->getActionIcon();
         $rowStyles[] = 'elluminaterecordingplay';
               
         //Format Description
         $fileRow[] = get_string($recordingFile->format,'elluminate');
         $rowStyles[] = '';
          
         //Status Text (and link if applicable)
         $fileRow[] = $fileActions->getStatusLink();
         $rowStyles[] = '';
          
         //Error Text if present
         $fileRow[] = $recordingFile->getErrorMessage();
         $rowStyles[] = '';
          
         //Last Updated
         $fileRow[] = userdate($recordingFile->updated);
         $rowStyles[] = '';
          
         $this->recordingTable->addRow($fileRow,$rowStyles);
         if ($recordingFile->status == Elluminate_Recordings_Constants::IN_PROGRESS_STATUS){
            $conversionInProgress = true;
         }
      }
   
      //Add row for manual status check, only if conversions are in progress.
      if ($conversionInProgress){
         $manualUpdate = $this->getManualUpdateLink($recording);
         $this->recordingTable->addSpanRow($manualUpdate, 5);
      }
   
      return $this->recordingTable->getTableOutput();
   }
   
   private function getManualUpdateLink($recording){
      $link = $this->output->getMoodleUrl(self::getPageUrl($recording->recordingid, true));
      return "<div class='elluminatelink'><a href='" . $link . "'>" . get_string('manualstatusupdatelink','elluminate') . "</a></div>";
   }
   
   public function doManualUpdate(){
      $idsForUpdate = $this->statusUpdater->getEligibleFilesForUpdate(Elluminate_Recordings_StatusUpdate::MANUAL_MODE);
      if (sizeof($idsForUpdate) > 0){
         $this->statusUpdater->doRecordingFileStatusUpdate($idsForUpdate);
      }
   }
}