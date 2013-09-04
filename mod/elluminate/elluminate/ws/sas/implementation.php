<?php
/**
 * Description of sas_implementation
 *
 * @author matthewschmidt
 */
class Elluminate_WS_SAS_Implementation implements Elluminate_WS_SchedulingManager {

   protected $logger;
   private $soapHelper;
   private $responseFactory;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_SAS_Implementation");
   }
   
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   public function createSession($session) {
      $args = Elluminate_WS_SAS_SessionArgs::getAPIArgumentsFromSession($session,'create');      
      $apiSession = $this->executeCommand('SetSession', $args);

      //For this method, all we do is set the meeting id and nothing else
      if ($apiSession != null){ 
         $session->meetingid = $apiSession->meetingid;
      }
      
      return $session;
   }

   public function deleteRecording($recordingId) {
      $args['recordingId'] = $recordingId;
      return $this->executeBooleanCommand('RemoveRecording',$args);
   }

   public function deleteSession($sessionId) {
      $args['sessionId'] = $sessionId;
      return $this->executeBooleanCommand('RemoveSession',$args);
   }

   public function deleteSessionPresentation($presentationId, $sessionId) {
      $args = array();
      $args['presentationId'] = $presentationId;
      $args['sessionId'] = $sessionId;
      return $this->executeBooleanCommand('RemoveSessionPresentation',$args);
   }

   public function getGuestLink($sessionId) {
      $testDisplayName = 'XX01_Test_Display_10XX';
      $args = array ();
      $args['sessionId'] = $sessionId;
      $args['displayName'] = $testDisplayName;
      $url = $this->executeCommand('BuildSessionUrl', $args);
      $cleanUrl = str_replace($testDisplayName, '', $url);
      return $cleanUrl;
   }
   
   public function getRecordingsForSession($sessionId) {
      $recordings = array();
      $args = array('sessionId' => $sessionId);
      return $this->executeCommand('ListRecordings', $args);     
   }

   public function getRecordingsForTime($startTime, $endTime) {
      $recordings = array();
      $startTime = Elluminate_WS_Utils::convertPHPDateToSASDate($startTime);
      $endTime = Elluminate_WS_Utils::convertPHPDateToSASDate($endTime);
      $args = array('startTime' => $startTime, 'endTime' => $endTime);
      return $this->executeCommand('ListRecordings', $args);      
   }

   /**
    * In the case of SAS, we have a bit of a hacky workaround to retrieve all recording
    * playback information.  The listRecordingFiles API call returns a correct URL for MP3/MP4,
    * but it returns the download URL for the VCR format as opposed to the browser playback (JNLP).
    * 
    * For this reason, this method needs to actually make 2 calls to SAS to get the full playback details.
    * A buildRecordingURL call is made to retrieve the VCR URL.
    * 
    * This should be resolved when we switch the Moodle Module to use the kiwi SAS calls instead (see
    * MOOD-425)
    * 
    * @see Elluminate_WS_SchedulingManager::getRecordingPlaybackDetails()
    */
   public function getRecordingPlaybackDetails($recordingIdsToCheck){
      $recordingFiles = array();
      $filesResult = $this->executeCommand('ListRecordingFiles', $recordingIdsToCheck);

      foreach ($filesResult as $recordingFile){
         if ($recordingFile->format == Elluminate_Recordings_Constants::VCR_FORMAT){        
            $args = array();
            $args['recordingId'] = $recordingFile->recordingid;
            $vcrRecordingUrl = $this->executeCommand('buildRecordingURL', $args);
            $this->logger->debug("VCR Recording Format, updating URL: " . $vcrRecordingUrl);
            $recordingFile->url = $vcrRecordingUrl;
         }
      }
      return $filesResult;
   }

   public function getSession($sessionId) {
      $args = array ();
      $args['sessionId'] = $sessionId;
      return $this->executeCommand('ListSession', $args);
   }

   public function getSessionUrl($sessionId, $displayName, $userId = '') {
      $args = array ();
      $args['sessionId'] = $sessionId;
      $args['displayName'] = $displayName;
       
      if(!empty($userId)) {
         $args['userId'] = $userId;
      }
      return $this->executeCommand('BuildSessionUrl',$args);
   }

   public function setSessionPresentation($presentationId, $sessionId) {
      $args = array();
      $args['presentationId'] = $presentationId;
      $args['sessionId'] = $sessionId;
      return $this->executeBooleanCommand('SetSessionPresentation',$args);
   }

   public function testConnection() {
      $args = array();
      return $this->executeBooleanCommand('GetServerConfiguration',$args);
   }

   public function updateSession($Session) {
      $args = Elluminate_WS_SAS_SessionArgs::getAPIArgumentsFromSession($Session,'update');
      return $this->executeBooleanCommand('UpdateSession',$args);
   }

   public function updateUsers($Session) {
      //For a user update, we only pass the chairlist and nonchairlist fields
      $args = array();
      $args['sessionId'] = $Session->meetingid;
      $args['chairList'] = $Session->chairlist;
      $args['nonChairList'] = $Session->nonchairlist;
      return $this->executeBooleanCommand('UpdateSession',$args);
   }

   public function uploadPresentationContent($Preload) {
      $args = array();
      $args['creatorId'] = $Preload->creatorid;
      $args['filename'] = $Preload->formattedfilename;
      $args['description'] = $Preload->description;

      //IMPORTANT: The PHP SoapClient will automatically encode this data as base64
      //because of the WSDL definition.  No manipulation of the contents prior
      //to processing the the soap client is required.
      $args['content'] = $Preload->filecontents;
        
      return $this->executeCommand('UploadRepositoryPresentation', $args);
   }

   public function convertRecording($recordingid,$format){
      $args = array ();
      $args['recordingId'] = $recordingid;
      $args['format'] = $format;
      $this->logger->debug("convertRecording id [" . $recordingid . "] to format [" . $format . "]");
      
      $recordingFiles = $this->executeCommand('ConvertRecording', $args);
      $this->logger->debug("recordingFiles size = " . sizeof($recordingFiles));
      //Should only be one result for a conversion
      $recordingFileResult = null;
      if (sizeof($recordingFiles)){
         $recordingFileResult = $recordingFiles[0];
      }
      return $recordingFileResult;
   }
    
   /**
    * SAS returns licenses via 2 API calls: getOptionLicenses and getTelephonyLicenseInfo
    * 
    * This method will make both API calls and return a combined array of licenses that
    * can be used by the business logic layer of the moodle application.
    * 
    * 
    * @see Elluminate_WS_SchedulingManager::getLicenses()
    */
   public function getLicenses(){
      $args = array();
      $optionLicenses = $this->executeCommand('GetOptionLicenses', $args);

      $telephonyLicense =  $this->executeCommand('getTelephonyLicenseInfo',$args);
      
      if ($telephonyLicense != null){
         if ($optionLicenses != null){
            array_push($optionLicenses,$telephonyLicense);
         }else{
            $optionLicenses = array($telephonyLicense);
         }
      }
      
      return $optionLicenses;
   }
   
   public function setTelephony($sessionId, $status){
      $args = array();
      $args['sessionId'] = $sessionId;
      if ($status == true){
         $args['telephonyType'] = 'integrated';
      }else{
         $args['telephonyType'] = 'none';
      }
      return $this->executeCommand('setTelephony',$args);
   }
   
   public function getTelephony($sessionId){
      $args = array();
      $args['sessionId'] = $sessionId;
      return $this->executeCommand('getTelephony',$args);
   }

   public function getTelephonyLicense(){
      $args = array();
      return $this->executeCommand('getTelephonyLicenseInfo',$args);
   }
   
   /**
    * This is a common function used to send a command to the SOAP server and then
    * call the factory object to retrieve an appropriate class to process the response.
    * 
    * If soapResponse->empty is true, it means that the API response has come back with a
    * collection value that has no collection items.
    * 
    * @param String $command to execute
    * @param Array $args - arguments for SOAP call
    */
   protected function executeCommand($command, $args, $overrideClient = null){
      if ($overrideClient != null){
         $soapResponse = $this->soapHelper->send_command($command, $args,null, $overrideClient);
      }else{
         $soapResponse = $this->soapHelper->send_command($command, $args);
      }

      $responseContent = null;
      if (! $soapResponse->empty){
         $responseLoader = $this->responseFactory->getResponseHandler($soapResponse->responseType);
         $responseContent = $responseLoader->processResponse($soapResponse->apiResponse);
      }else{
         $this->logger->debug("executeCommand [" . $command . "], empty response");
      }
      return $responseContent;
   }
   
   protected function executeBooleanCommand($command,$args){
      $this->logger->debug("executeBooleanCommand [" . $command . "]");
      $booleanResult = false;
      $soapResponse = $this->soapHelper->send_command($command, $args);
      if (!$soapResponse->error){
         $booleanResult = true;
      }else{
         $booleanResult = false;
      }
      $this->logger->debug("executeBooleanCommand [" . $command . "] result [" . $booleanResult . "]");
      return $booleanResult;
   }
   
   public function getSchedulingManagerName() {
      return "SAS";
   }
}
