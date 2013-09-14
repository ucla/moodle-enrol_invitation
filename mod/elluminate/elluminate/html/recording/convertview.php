<?php
class Elluminate_HTML_Recording_ConvertView{
   const PAGE_URL = "recording-convert.php";
   const RECORDING_ID_PARAM = "?rid=";
   const FORMAT_PARAM  = "&format=";
   const AJAX_PARAM = "&ajax=1";
   
   const NO_VALUE = "-";
   
   private $recordingTable;
   
   private $logger;
   private $output;
   
   public function __construct(){   
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_HTML_RecordingConversionView");
   }
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   public static function getPageUrl($recordingid, $format, $ajaxMode = false){
      $url = self::PAGE_URL . self::RECORDING_ID_PARAM . $recordingid . self::FORMAT_PARAM . $format;
      
      if ($ajaxMode){
         $url .= self::AJAX_PARAM;
      }
      return $url;
   }
   
   //For testing
   public function setRecordingDetailTable($recordingDetailTable){
      $this->recordingTable = $recordingDetailTable;
   }

   public function requestConversion($recording, $format, $ajaxMode = false){
      if (!$recording->isEligibleForConversion()){
         print_error('incorrectversion','elluminate');
         return;
      }
      
      $recordingFile = $recording->convertRecording($format);
      
      $fileActions = new Elluminate_HTML_Recording_FileActions($recording, $recordingFile, $this->output);
      
      $returnHTML = '';
      if ($ajaxMode){
         $detailPageUrl = $this->getRecordingDetailSessionUrl($recording->recordingid);
         $returnHTML = $fileActions->getConvertLink($ajaxMode);
      }else{
         $returnHTML = '';
      }
      
      return $returnHTML;
   }
  
   public function getRecordingDetailSessionUrl($recordingid){
      $url = $this->output->getMoodleUrl(Elluminate_HTML_Recording_DetailView::getPageUrl($recordingid));
      return $url;
   }
   
   private function getStaticFormatIcon($recordingFile){
      return "<img class='smallicon' src='" . 
         $this->output->getMoodleImage($recordingFile->format . '-icon') . "' title='Not Available - Conversion required'></img>";
   }
}