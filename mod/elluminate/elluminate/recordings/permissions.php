<?php
/**
 * This class manages permissions for session recordings.  This class is meant to
 * work in conjunction with the:
 * @see Elluminate_Recordings_Capabilities
 * class, which provides a light wrapper to the moodle role capabilities object.  
 * 
 * This class is meant to provide the following:
 * 1.) Provide a set of clearly named and easily used methods to determine permissions
 * 2.) Add a layer of business logic specific to the Collaborate Module.
 * 
 * The most common example of this is due to the fact that the recordings permissions
 * model is setup to have permissions sets for "all recordings" and "own recordings".  
 * In the case of own recordings, that role permission only applies if the session
 * was originally created by the current user.
 * 
 * @author dwieser
 *
 */
class Elluminate_Recordings_Permissions{
   
   const PERMISSION_DENIED = false;
   const PERMISSION_GRANTED = true;
   
   const RECORDING_PREFIX = "recording_";
   //API Interfaces
   private $licenseManager;
   
   //Page Specific Values
   private $checkRecording;
   private $pageSession;
   private $recordingCapabilities;
   private $cm;
   private $userid;
  
   //Permission Failed Details
   private $permissionFailureKey;
   
   private $logger;
   
   // ** GET/SET Magic Methods **
   public function __get($property)
   {
      if (property_exists($this, $property)) {
         return $this->$property;
      }
   }
    
   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   // ** GET/SET Magic Methods **
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Recordings_Permissions");
   }
   
   //This value doesn't get set until runtime, but this object
   //is created by the pimple container, so we need an explicit
   //setter.
   public function setContext($context){
      $this->recordingCapabilities->setContext($context);
   }
   
   //Recording Permissions need to be checked on a recording-by-recording basis
   public function setCurrentRecording($recording){
      $this->checkRecording = $recording;
   }
   
   /**
    * "admin" rights for a recording is true if current user has at least one of the following:
    *    -edit description
    *    -delete
    *    -manage visibility
    */
   public function doesUserHaveRecordingAdminPermissions(){
      if ($this->doesUserHaveDeletePermissionsForRecording() ||
            $this->doesUserHaveToggleVisibilityPermissionsForRecording() ||
               $this->doesUserHaveEditDescriptionPermissionsForRecording()  ){
         return self::PERMISSION_GRANTED;
      }
      return self::PERMISSION_DENIED;
   }
   
   public function doesUserHaveGeneralRecordingViewPermissions(){
      //1. Check Role Level Permissions to View Recordings.
      if (!$this->recordingCapabilities->canViewRecordings()){
         $this->permissionFailureKey = 'recording_generalpermissionserror';
         return self::PERMISSION_DENIED;
      }
      
      //If we make it here, permissions are good.
      return self::PERMISSION_GRANTED;
   }
   
   public function doesUserHaveViewPermissionsForRecording(){
      //1. Check Role Level Permissions to View Recordings.  
      if (!$this->recordingCapabilities->canViewRecordings()){
         $this->permissionFailureKey = 'recording_generalpermissionserror';
         return self::PERMISSION_DENIED;
      }
         
      //2. Check if recording is hidden 
      if (! $this->checkForHiddenRecordingPermission()){
         return self::PERMISSION_DENIED;
      }
      
      //If we make it here, permissions are good.
      return self::PERMISSION_GRANTED;
   }
   
   public function doesUserHaveDeletePermissionsForRecording(){
      if ($this->recordingCapabilities->canUserDeleteAnyRecording()){
         return self::PERMISSION_GRANTED;
      }else if ($this->recordingCapabilities->canUserDeleteOwnRecordings() &&
               $this->doesUserOwnRecording()){
         return self::PERMISSION_GRANTED;
      }else{
         $this->permissionFailureKey = 'recordingdeletepermissionserror';   
         return self::PERMISSION_DENIED;
      }
   }
   
   public function doesUserHaveToggleVisibilityPermissionsForRecording(){
      if ($this->recordingCapabilities->canUserManageAnyRecording()){
         return self::PERMISSION_GRANTED;
      }else if ($this->recordingCapabilities->canUserManageOwnRecordings() &&
               $this->doesUserOwnRecording()){
         return self::PERMISSION_GRANTED;
      }else{
         $this->permissionFailureKey = 'recordingmanagepermissionserror';
         return self::PERMISSION_DENIED;
      }
   }
   
   public function doesUserHaveEditDescriptionPermissionsForRecording(){
      if ($this->recordingCapabilities->canUserEditAnyRecording()){
         return self::PERMISSION_GRANTED;
      }else if ($this->recordingCapabilities->canUserEditOwnRecordings() &&
               $this->doesUserOwnRecording()){
         return self::PERMISSION_GRANTED;
      }else{
         $this->permissionFailureKey = 'recordingeditpermissionserror';
         return self::PERMISSION_DENIED;
      }
   }
   
   public function doesUserHaveToggleGroupVisibilityPermissionsForRecording(){
      if ($this->recordingCapabilities->canManageGroupSettings()){
         return self::PERMISSION_GRANTED;
      }else{
         $this->permissionFailureKey = 'recordingeditpermissionserror';
         return self::PERMISSION_DENIED;
      }
   }
   
   public function doesUserHaveConvertPermissionsForRecording(){
      if (!$this->licenseManager->isConversionLicensed()){
         $this->permissionFailureKey = 'recordingconvertpermissionserror';
         return self::PERMISSION_DENIED;
      }

      
      if ($this->recordingCapabilities->canConvertAnyRecording()){
         return self::PERMISSION_GRANTED;
      }
      
      if ($this->checkForConvertOwnRecordings()){
         return self::PERMISSION_GRANTED;
      }
      
      $this->permissionFailureKey = 'recordingconvertpermissionserror';
      return self::PERMISSION_DENIED;
   }
   
   /**
    * Don't allow MP3/MP4 formats to be played/displayed if license isn't set.
    */
   public function isMP4PlaybackEnabled(){
      if ($this->licenseManager->isConversionLicensed()){
         return self::PERMISSION_GRANTED;
      }
      
      $this->permissionFailureKey = 'recordingmp4notlicensed';
      return self::PERMISSION_DENIED;
   }
   
   private function checkForHiddenRecordingPermission(){
      if (! $this->checkRecording->visible){
         if ($this->recordingCapabilities->canUserManageAnyRecording()){
            return self::PERMISSION_GRANTED;
         }else if($this->checkForManageOwnRecordings()){
            return self::PERMISSION_GRANTED;
         }else{
            $this->permissionFailureKey = 'recording_hiddenpermissionserror';
            return self::PERMISSION_DENIED;
         }
      }
      return self::PERMISSION_GRANTED;
   }
   
   private function checkForManageOwnRecordings(){
      if ($this->recordingCapabilities->canUserManageOwnRecordings()){
         if ($this->doesUserOwnRecording()){
            return self::PERMISSION_GRANTED;
         }else{
            return self::PERMISSION_DENIED;
         }
      }
      return self::PERMISSION_DENIED;
   }
   
   private function checkForConvertOwnRecordings(){
      if ($this->recordingCapabilities->canConvertOwnRecordings()){
         if ($this->doesUserOwnRecording()){
            return self::PERMISSION_GRANTED;
         }else{
            return self::PERMISSION_DENIED;
         }
      }
      return self::PERMISSION_DENIED;
   }
   
   private function doesUserOwnRecording(){
      if ($this->pageSession->creator == $this->userid){
         return true;
      }else{
         return false;
      }
   }
}