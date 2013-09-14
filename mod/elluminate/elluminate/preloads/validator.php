<?php
class Elluminate_Preloads_Validator implements Elluminate_Validation_Validator{
    
   private $validFileExtensions = array("wbd","wbp","elp","elpx");
    
   public function validate($preloadToCheck){

      $validateState = $this->validateFilePathExists($preloadToCheck);
      if ($validateState->validationSuccess == false){
         return $validateState;
      }

      $validateState = $this->validateFileExtensionExists($preloadToCheck);
      if ($validateState->validationSuccess == false){
         return $validateState;
      }

      $validateState = $this->validateFileExtensionIsValid($preloadToCheck);
      if ($validateState->validationSuccess == false){
         return $validateState;
      }

      $validateState = $this->validateFileSizeNonZero($preloadToCheck);
      if ($validateState->validationSuccess == false){
         return $validateState;
      }

      if ($preloadToCheck->fileext == "wbd" || $preloadToCheck->fileext == "wbp"){
         $validateState = $this->validateFileIsXML($preloadToCheck);
         if ($validateState->validationSuccess == false){
            return $validateState;
         }
      }

      return new Elluminate_Validation_State(true,'');
   }

   /**
    * Preload File must have a file extension with an alphanumeric value
    *
    * @param unknown_type $preloadToCheck
    * @return Elluminate_Validation_State
    */
   public function validateFileExtensionExists($preloadToCheck){
      /// Make sure the file uses a valid whiteboard preload file extension.
      if ($preloadToCheck->fileext == '') {
         throw new Elluminate_Exception(get_string('preloadnofileextension', 'elluminate'), 0, 'preloadnofileextension');
      } else {
         return new Elluminate_Validation_State(true,'');
      }
   }
    
   /**
    * Preload File must have non-blank file extension
    *
    * @param unknown_type $preloadToCheck
    * @return Elluminate_Validation_State
    */
   public function validateFileExtensionIsValid($preloadToCheck){
      /// Make sure the file uses a valid whiteboard preload file extension.
      if (in_array($preloadToCheck->fileext,$this->validFileExtensions)) {
         return new Elluminate_Validation_State(true,'');
      }else{
         throw new Elluminate_Exception(get_string('preloadinvalidfileextension', 'elluminate'), 0, 'preloadinvalidfileextension');
      }
   }
    
   /**
    * For WBP and WBD files, contents needs to be valid XML
    * @param unknown_type $preloadToCheck
    * @return Elluminate_Validation_State
    */
   public function validateFileIsXML($preloadToCheck){
      try{
         $result = @simplexml_load_file($preloadToCheck->filepath);
      }catch(Exception $e){
         throw new Elluminate_Exception(get_string('preloadinvalidnotxml', 'elluminate'), 0, 'preloadinvalidnotxml');  
      }
      
      if (!$result){
         throw new Elluminate_Exception(get_string('preloadinvalidnotxml', 'elluminate'), 0, 'preloadinvalidnotxml');
      }
      
      return new Elluminate_Validation_State(true,'');
   }
    
   /**
    * File Size cannot be 0
    * @param unknown_type $preloadToCheck
    * @return Elluminate_Validation_State
    */
   public function validateFileSizeNonZero($preloadToCheck){
      if ($preloadToCheck->size == 0){
         throw new Elluminate_Exception(get_string('preloadinvalidfileempty', 'elluminate'), 0, 'preloadinvalidfileempty');
      }else{
         return new Elluminate_Validation_State(true,'');
      }
   }
    
   /**
    * File Path is blank.  In most cases, this happens if the file is larger than the size limit
    * @param unknown_type $preloadToCheck
    * @return Elluminate_Validation_State
    */
   public function validateFilePathExists($preloadToCheck){
      if ($preloadToCheck->filepath == ''){
         throw new Elluminate_Exception(get_string('preloadfiletoolarge', 'elluminate'), 0, 'preloadfiletoolarge');
      }else{
         return new Elluminate_Validation_State(true,'');
      }
   }
}