<?php
/**
 * This class encapsulates the view interactions with Recording Files:
 *   -Play
 *   -Convert
 *   -Status (Errors, In Progress)
 * 
 * @author dwieser
 *
 */
class Elluminate_HTML_Recording_FileActions{
   const PLAY_ICON = "-play";
   const PLAY_KEY = 'playrecording';
   const CONVERT_KEY = 'not_available';
   const WAIT_KEY = 'in_progress';
   const ERROR_KEY = 'not_applicable';
   
   const FORMAT_PARAM  = "format=";
   const RECORDING_ID_PARAM = "rid=";
   const SESSION_ID_PARAM = "sessionid";
   
   const URL_AND = "&";
   const URL_PARAM_START = "?";
   
   const LOAD_RECORDING_URL = '/mod/elluminate/recording-load.php';
   const CONV_RECORDING_URL = '/mod/elluminate/recording-conv.php';
   
   private $recordingFile;
   private $output;
   private $parentRecording;
   
   public function __construct($recording, $recordingfile, $output){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Recordings_FileActions");
      $this->recordingFile = $recordingfile;
      $this->output = $output;
      $this->parentRecording = $recording;
   }
   
   /**
    * 
    * Get a moodle format play action icon based on a given recording file
    * 
    * If recording is not available to play, nothing is returned
    * 
    * @param Elluminate_Recordings_File $recordingFile
    * @param Elluminate_Moodle_Output $htmloutput - output object used to generate moodle action icon
    */
   public function getActionIcon(){
      $returnHTML = '';
      $altTextInfo = '';
      if ($this->recordingFile->status == Elluminate_Recordings_Constants::AVAILABLE_STATUS){
         $url = Elluminate_HTML_Recording_PlayView::getPageUrl($this->recordingFile->recordingid, $this->recordingFile->format);
         $icon = $this->recordingFile->format . self::PLAY_ICON;
         $altTextKey = $this->recordingFile->format . self::PLAY_KEY;
         $attributes['target'] = 'new';
         $returnHTML = $this->output->getActionIcon($url, $icon, $altTextKey,$attributes,$altTextInfo);
      }
   
     return $returnHTML;
   }
   
   public function getStatusLink(){
      $statusLink = '';
      
      //If file is mp3/mp4 format and not eligible, then n/a text is displayed - there 
      //will never be a playback or convert link available.
      if ($this->recordingFile->format != Elluminate_Recordings_Constants::VCR_FORMAT && 
               !$this->parentRecording->isEligibleForConversion()){
         return get_string('notapplicable','elluminate');
      }
      
      if ($this->recordingFile->status == Elluminate_Recordings_Constants::AVAILABLE_STATUS){
         $url = Elluminate_HTML_Recording_PlayView::getPageUrl($this->recordingFile->recordingid, 
                  $this->recordingFile->format);
         
         $statusLink = get_string('available','elluminate');
         $statusLink .= '<div class="elluminatelink"><a href="' . $url . '" target="new">' .
                  get_string('playlink','elluminate') . '</a></div>';
      }
  
      if ($this->recordingFile->status == Elluminate_Recordings_Constants::NOT_AVAILABLE_STATUS){
         $url = Elluminate_HTML_Recording_ConvertView::getPageUrl($this->recordingFile->recordingid, 
                  $this->recordingFile->format);
            
         $statusLink = get_string('notavailable','elluminate');
         $statusLink .= '<div class="elluminatelink"><a href="' . $url . '">' .
                  get_string('convertlink','elluminate') . '</a></div>';
      }
       
      if ($this->recordingFile->status == Elluminate_Recordings_Constants::IN_PROGRESS_STATUS){
         $statusLink = get_string('inprogress','elluminate');
      }
       
      if ($this->recordingFile->status == Elluminate_Recordings_Constants::NOT_APPLICABLE_STATUS){
         $statusLink = get_string('converror','elluminate');
      }
      
      if ($this->recordingFile->status == Elluminate_Recordings_Constants::COMM_ERROR_STATUS){
         $url = Elluminate_HTML_Recording_ConvertView::getPageUrl($this->recordingFile->recordingid, 
                  $this->recordingFile->format);
         
         $statusLink = get_string('conversioncommerror','elluminate');
         $statusLink .= '<div class="elluminatelink"><a href="' . $url . '">' .
                  get_string('convertlink','elluminate') . '</a></div>';
      }
      return $statusLink;
   }
   
   public function getConvertLink($ajaxResponse = false){
      $convertUrl = '';
      $anchor = '';    

      if ($this->recordingFile->status == Elluminate_Recordings_Constants::NOT_AVAILABLE_STATUS){
         $url = Elluminate_HTML_Recording_ConvertView::getPageUrl($this->recordingFile->recordingid, 
                  $this->recordingFile->format, false);
         $convertKey = $this->recordingFile->format . self::CONVERT_KEY;
         $anchor = '<a id = "' . $convertKey .  '" href="' . $url . '">' . 
            get_string($convertKey,'elluminate') . "</a>";
      }
      if ($this->recordingFile->status == Elluminate_Recordings_Constants::IN_PROGRESS_STATUS){
         $url = Elluminate_HTML_Recording_DetailView::getPageUrl($this->recordingFile->recordingid);
        
         $waitKey = $this->recordingFile->format . self::WAIT_KEY;
         $anchor = '<a href="' . $url . '">' . get_string($waitKey,'elluminate') . "</a>";
      }
      
      if ($this->recordingFile->status == Elluminate_Recordings_Constants::NOT_APPLICABLE_STATUS){
         $url = Elluminate_HTML_Recording_DetailView::getPageUrl($this->recordingFile->recordingid);
         
         $errorKey = $this->recordingFile->format . self::ERROR_KEY;
         $anchor = '<a href="' . $url . '">' . get_string($errorKey,'elluminate') . "</a>";
      }
      
      if (!$ajaxResponse && $anchor != ''){
         $convertUrl = '<div class="elluminateconvert">' . $anchor . "</div>";
      }else{
         $convertUrl = $anchor;
      }
      
      return $convertUrl;
   }
   
   private function getConvertAltText(){
      $altTextKey = $this->recordingFile->format . self::CONVERT_KEY;
      return $altTextKey;
   }
}