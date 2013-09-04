<?php
class Elluminate_Recordings_File{
   private $logger;
   
   private $id;
   private $recordingid;
   private $format;
   private $status;
   private $errorcode;
   private $errortext;
   private $updated;
   
   private $url;
   
   //The primary key for the parent recording
   private $parentRecordingID;
   
   //DAO to save recording file
   private $recordingDAO;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_Recordings_File");
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
   
   public function setRecordingDAO($recordingDAO){
      $this->recordingDAO = $recordingDAO;
   }
   
   public function getErrorMessage(){
      if ($this->errortext == NULL){
         $errorText = get_string('notapplicable','elluminate');
      }else{
         $errorText = $this->errortext;
      }
      return $errorText;
   }
   
   public function save(){
      $this->updated = time();
      if ($this->id){
         $this->recordingDAO->updateRecordingFile($this);
      }else{
         $this->id = $this->recordingDAO->saveRecordingFile($this);
      }
   }
   
   public function getDBInsertObject()
   {
      return get_object_vars($this);
   }
}