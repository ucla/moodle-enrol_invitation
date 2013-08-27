<?php
class Elluminate_Preload{
   
   private $id;
   private $realfilename;
   private $formattedfilename;
   private $fileext;
   private $filepath;
   private $size;
   private $filecontents;
   private $presentationid;
   private $meetingid;
   private $creatorid;
   private $description;
   
   private $removeChars = array("<",">","&","#","%","\"","\\","|", "'");
   
   private $preloadDAO;
   private $serverSchedulingManager;
   
   private $logger;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Preload");
   }
   
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
   
   /**
    * Populate the preload object based on a StdClass object loaded from DB
    * 
    * @param StdClass $dbRecord
    */
   public function populateFromDBObject($dbRecord)
   {
      $this->id = $dbRecord->id;
      $this->meetingid = $dbRecord->meetingid;
      $this->presentationid = $dbRecord->presentationid;
      $this->description = $dbRecord->description;
      $this->size = $dbRecord->size;
      $this->creatorid = $dbRecord->creatorid;
   }
   
   /**
    * 1.) Remove Special Characters from the file name (For SAS processing) 
    * 2.) Get the file extension for use in validation
    * 3.) *** Since there is no way for user to set description
    * currently, set the description for the preload as the actual
    * file **
    * 
    */
   public function initFile()
   {
      $this->removeFileNameSpecialChars();
      $this->getFileExtension();
      $this->description = $this->realfilename;
   }
   
   /**
    * Remove special characters from name.  This is the value that will be used to
    * save the file and upload to the server.  The real file name will be used
    * only during the upload process.
    * 
    */
   public function removeFileNameSpecialChars()
   {
      $replace = '';
      $this->formattedfilename = str_replace($this->removeChars, $replace, $this->realfilename);
   }
   
   /**
    * Retrieve the file extension from the filename and store in a member variable
    */
   public function getFileExtension()
   {
      $this->fileext = pathinfo($this->realfilename, PATHINFO_EXTENSION);
   }
   
   /**
    * Handle uploading of the preload to the server
    * 
    * Adding to DB doesn't happen until the second service call to officially link the
    * preload to the meeting.  Otherwise, we end up with an orphaned record in the moodle DB.
    * 
    * @throws Elluminate_Exception
    */
   public function upload()
   {   
      $this->uploadFileToServer();
   }
   
   /**
    * Wrap call to create a preload and trap errors that may occur
    *
    * @param Elluminate_Session $createSession
    * @return unknown
    */
   function uploadFileToServer()
   {
      $this->increasePreloadMemory();
      $serverid =  $this->serverSchedulingManager->uploadPresentationContent($this);
      if (!empty($serverid)){
         $this->presentationid = $serverid;
         $this->logger->info("Preload Created on server successfully, presentation id = " . $this->presentationid);
      }
   }
   
   /**
    * Now that the preload has been created on SAS, we need to link it to
    * the session.
    * 
    * @param unknown_type $linkSession
    * @throws Elluminate_Exception
    */
   public function linkToSession($linkSession)
   {
      $this->linkPreloadToSession($linkSession);
      $this->meetingid = $linkSession->meetingid;
      $this->savePreloadToDB();
      $this->logger->info("Preload " . $this->presentationid . " saved to DB");
   }
   
   /**
    * Wrap call to link preload to a session and trap errors that may occur
    *
    * @param Elluminate_Session $createSession
    * @throws Elluminate_Exception
    */
   function linkPreloadToSession($linkSession)
   {
     $this->serverSchedulingManager->setSessionPresentation($this->presentationid,$linkSession->meetingid);
     $this->logger->info("Preload " . $this->presentationid . " linked to server session " . $linkSession->meetingid);
   }
   
   /**
    * Save current session to DB as a new session
    * @param unknown_type $sessionid
    * @throws Elluminate_Exception
    */
   private function savePreloadToDB()
   {
      $this->id = $this->preloadDAO->savePreload($this);
   }
   
   /**
    * Return the preload object for the given session 
    * 
    * only one preload is allowed per session
    * 
    * @param Elluminate_Recording $parentSession
    */
   public function loadPreloadForSession($parentSession)
   {
      $loadSuccess = false;
      $this->logger->info("Loading Preloads for " . $parentSession->meetingid);
      $dbObject = $this->preloadDAO->getPreloadForMeeting($parentSession->meetingid);
      if (!empty($dbObject)){
         $this->logger->info("Located Preload with meeting ID of " . $parentSession->meetingid);
         $this->populateFromDBObject($dbObject);
         $loadSuccess = true;
      }
      return $loadSuccess;
   }
   
   /**
    * Load a preload, given a presentation ID
    * 
    * @param string $presentationid
    */
   public function loadPreload($presentationid)
   {
      $loadSuccess = false;
      $this->logger->info("Loading Preload ID " . $presentationid);
      $dbObject = $this->preloadDAO->getPreloadByPresentationId($presentationid);
      if (!empty($dbObject)){
         $this->logger->info("Located Preload with presentation ID of " . $presentationid);
         $this->populateFromDBObject($dbObject);
         $loadSuccess = true;
      }
      return $loadSuccess;
   }
   
   /**
    * Delete a preload file from the server and the DB
    * 
    * @throws Elluminate_Exception
    */
   public function deletePreload($parentSession)
   {
      $this->deleteServerPreload($parentSession);
      $this->deleteDBPreload($this);   
   }
   
   /**
    * Delete the preload from the server
    * 
    * @throws Elluminate_Exception
    */
   public function deleteServerPreload($parentSession)
   {
      $this->serverSchedulingManager->deleteSessionPresentation($this->presentationid,
                 $parentSession->meetingid);
      $this->logger->info("Preload " . $this->presentationid .
             " link to session " . $parentSession->meetingid . " has been deleted on server");
   }
   
   public function deleteDBPreload()
   {
      $this->preloadDAO->deletePreloadByPresentationId($this->presentationid);
      $this->logger->info("Preload " . $this->presentationid . " has been deleted from DB");
   }

   /**
    * Helper function to return a StdClass version of this object
    * to allow public access on all member variables.
    *
    * This is required by the $DB moodle object to do DB operations
    * with the object
    *
    * @return StdClass
    */
   public function getDBInsertObject()
   {
      return get_object_vars($this);
   }
   
   /**
    * This function increases the memory limit of the php session to avoid issues
    * with memory during upload of large preload files.
    * 
    * This uses the moodle function raise_memory_limit to increase the value
    * to a value configured in the moodle site admin -> server -> performance page
    * 
    * This setting is only temporary for this execution of php.
    * 
    * TODO: abstract out this moodle function to improve testability.  At the moment, the
    * raise_memory_limit() function is mocked in the tests to allow the parent method to be tested.
    */
   private function increasePreloadMemory(){
      raise_memory_limit(MEMORY_EXTRA);
      $this->logger->debug("preload mem limit = " . ini_get('memory_limit'));
      $this->logger->debug("preload mem usage = " . memory_get_usage());
   }
}