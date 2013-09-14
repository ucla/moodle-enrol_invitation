<?php

class Elluminate_HTML_Recording_PlayView{
   const PAGE_URL = "recording-play.php";
   const RECORDING_ID_PARAM = "?rid=";
   const FORMAT_PARAM = "&format=";
   
   const INVALID_LAUNCH_URL = null;

   private $output;
   private $permissions;
   private $cacheManager;
   private $recordingStatusUpdater;

   private $logger;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_HTML_Recording_PlayView");
   }
   
   public static function getPageUrl($recordingid,$format){
      return self::PAGE_URL . self::RECORDING_ID_PARAM . $recordingid . 
         self::FORMAT_PARAM . $format;
   }
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
    
   public function getRecordingLaunchErrorMessage($recording,$format){
      $recordingFile = $recording->getRecordingFileByFormat($format);
       
      $errorMessage = '';
      if ($recordingFile == null || $recordingFile->status != Elluminate_Recordings_Constants::AVAILABLE_STATUS){
         $errorMessage = '<div class="elluminatetitle">' . get_string('playrecordingnotavailable','elluminate') . "</div>";
         if ($this->permissions->doesUserHaveConvertPermissionsForRecording()){
            $detailsUrl = Elluminate_HTML_Recording_DetailView::getPageUrl($recordingFile->recordingid);
            $errorMessage .= '<div class="elluminatelink"><a href="' . $detailsUrl . '">' . get_string('viewrecordingdescription','elluminate') . '</a></div>';
         }
      }else{
         $errorMessage = get_string('recordinglauncherror','elluminate');
      }
      return $errorMessage;
   }
    
   /**
    * Given a recording, attempt to retrieve the launch URL for that recording.
    * 
    * The URL should always be in cache at this point:
    *   1.) VCR Format recordings will be set on initial load and should not change
    *   2.) MP3/MP4 formats will just have had a status update on the load recording
    *   page which will reset the URL and potentially the status.
    *   
    * For MP3/MP4 recordings, we may end up in a scenario where a recording that was 
    * originally requested with an available status may no longer be available after the
    * page status update.  In this case we return an invalid value.
    * 
    * @param Elluminate_Recording $recording
    * @return $string - launch URL, null if invalid
    */
   public function getLaunchUrl($recording,$format){
      $recordingFile = $recording->getRecordingFileByFormat($format);
      
      if($recordingFile != null && $recordingFile->status == Elluminate_Recordings_Constants::AVAILABLE_STATUS){
         $launchURL = $this->getRecordingCacheUrl($recordingFile);
      }else{
         //We should not be attempting to retrieve URL for a session that is not available
         $this->logger->error("Invalid recording launch attempt for Recording ID " . $recording->id);
         $launchURL = self::INVALID_LAUNCH_URL;
      }
      return $launchURL;
   }
    
   /**
    * Returns a link back to the main view session page
    * @param unknown_type $courseModuleID
    * @return string
    */
   public function getViewSessionLink($courseModuleID){
      $url = $this->output->getMoodleUrl(Elluminate_HTML_Session_View::getPageUrl($courseModuleID));
      $link = "<div class='elluminatelink'><a href='" . $url . "'/>" . get_string('backtosession','elluminate') . "</a></div>";
      return $link;
   }
   
   /**
    * Attempt to retrieve the url from the url cache table.
    * @return launch url
    */
   private function getRecordingCacheUrl($recordingFile){      
      $cacheValue = self::INVALID_LAUNCH_URL;
      //Attempt to Load Cache
      $this->logger->debug("getRecordingCacheUrl [" . $recordingFile->recordingid . "]");
      
      $cacheValue = $this->cacheManager->getCacheContent(Elluminate_Cache_Constants::RECORDING_URL_CACHE,
               $recordingFile->format,$recordingFile->recordingid);
      
      if ($cacheValue == self::INVALID_LAUNCH_URL){
         $this->logger->error("No URL retrieved from Cache for Recording File [" . $recordingFile->recordingid . "]");
      }
      
      return $cacheValue;
   }
}