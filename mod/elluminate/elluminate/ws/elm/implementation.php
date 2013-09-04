<?php
class Elluminate_WS_ELM_Implementation extends Elluminate_WS_SAS_Implementation implements Elluminate_WS_SchedulingManager {
	const INVALID_COMMAND = null;
   
 	protected $logger;

	function __construct() {
		$this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_ELM_Implementation");
	}
	
   public function getRecordingPlaybackDetails($recordingIds) {
      $recordingFiles = array();   	
      foreach ($recordingIds as $recordingId) {
         $args['recordingId'] = $recordingId;
         $recordingUrl = $this->executeCommand('BuildRecordingUrl', $args);
         $recordingUrlDetails = new stdClass;
         $recordingUrlDetails->format = Elluminate_Recordings_Constants::VCR_FORMAT;
         $recordingUrlDetails->url = $recordingUrl;
         
         $recordingFiles[] = $recordingUrlDetails;
      }
      
      return $recordingFiles;
   }

   public function convertRecording($recordingid,$format){
	   $this->logger->error("ERROR: convertRecording api method is not supported!");
	   return self::INVALID_COMMAND;
   }

   public function setTelephony($sessionId, $status){
      $this->logger->error("ERROR: setTelephony api method is not supported!");
      return self::INVALID_COMMAND;
   }
   
   public function getRecordingsForSession($sessionId) {
      $recordings = array();
      $args = array('sessionId' => $sessionId);
      return $this->executeCommand('ListRecordingShort', $args);     
   }

   public function getRecordingsForTime($startTime, $endTime) {
      $recordings = array();
      $startTime = Elluminate_WS_Utils::convertPHPDateToSASDate($startTime);
      $endTime = Elluminate_WS_Utils::convertPHPDateToSASDate($endTime);
      $args['startTime'] = $startTime;
      $args['endTime'] = $endTime;
      
      return $this->executeCommand('listRecordingLong', $args);      
   }
 
   public function getGuestLink($sessionId) {
      $args = array ();
      $args['sessionList'] = $sessionId;
      $url = $this->executeCommand('GetEmailBody', $args);
      return $url;
   }
   
   public function uploadPresentationContent($Preload) {
      $args = array();
      $args['creatorId'] = $Preload->creatorid;
      $args['filename'] = $Preload->formattedfilename;
      $args['description'] = $Preload->description;

      $args['content'] = $Preload->filecontents;
       
      return $this->executeCommand('UploadRepositoryPresentation', $args,'Elluminate_WS_ELM_SOAPPreloadClient');
   }
    
   /**
    * We will always return a blank array - the licensed features of the application
    * are not available in ELM.
    * @see Elluminate_WS_SAS_Implementation::getLicenses()
    */
   public function getLicenses(){
      return array();
   }
   
   public function getSchedulingManagerName() {
   	return "ELM";
   }
}
