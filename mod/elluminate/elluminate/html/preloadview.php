<?php
class Elluminate_HTML_PreloadView{

   private $preloadSession;
   private $courseModule;
   private $wwwroot;
   private $sesskey;
   
   private $preloadFactory;
   
   private $logger;

   public function __construct()
   {
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_HTML_PreloadView");
   }

   public function __set($property, $value)
   {
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   /**
    * Build the HTML for the form to upload a new preload.
    */
   public function getPreloadFormHTML()
   {
      $preloadFormHTML = '';
      $preloadFormHTML .= '<p>'. $this->getChooseFileString() . '</p>';
      $preloadFormHTML .= '<form action="preload-form.php" method="post" enctype="multipart/form-data">';
      $preloadFormHTML .= '<input type="hidden" name="sesskey" value="' . $this->sesskey . '" />';
      $preloadFormHTML .= '<input type="hidden" name="id" value="' . $this->preloadSession->id . '" />';
      $preloadFormHTML .= '<input type="hidden" name="userfilename" id="userfilename">';
      $preloadFormHTML .= '<input type="file" name="whiteboard" alt="whiteboard" id="userfile" size="50" onchange="userfilename.value=userfile.value;"/><br />';
      $preloadFormHTML .= '<input type="submit" value="' . get_string('uploadthisfile') . '" /><br />';
      $preloadFormHTML .= '<input type="button" value="' . get_string('cancel') . '" onclick="document.location = \'';
      $preloadFormHTML .=  $this->wwwroot . '/mod/elluminate/view.php?id=' . $this->courseModule->id . '\'" />';
      $preloadFormHTML .= '</form>';

      return $preloadFormHTML;
   }

   private function getChooseFileString()
   {
      return get_string('preloadchoosewhiteboardfile', 'elluminate', Elluminate_Config_Settings::getFileSizeConfig());
   }
   /**
    * Preload has been uploaded, create the Elluminate_Preload object and create in DB and on Server.
    */
   public function processAddAction($userid){
      global $OUTPUT;
      $preload = $this->loadFile();
      
      //Set Creator
      $preload->creatorid = $userid;
       
      //Validate
      try {
         $preloadValidator = new Elluminate_Preloads_Validator();
         $validationState = $preloadValidator->validate($preload);
         $filedata = file_get_contents($preload->filepath);
         /// The file is valid, let's proceed with creating the preload.
         /// Read the file contents into memory.
         $preload->filecontents = $filedata;
         $preload->upload();
         $preload->linkToSession($this->preloadSession);
      } catch (Elluminate_Exception $e) {
         redirect($this->wwwroot . '/mod/elluminate/preload-form.php?id=' . $this->preloadSession->id,
            get_string($e->getUserMessage(),'elluminate'), 5);
      }

      redirect($this->wwwroot . '/mod/elluminate/view.php?id=' . $this->courseModule->id,
         get_string('preloaduploadsuccess', 'elluminate',$preload->realfilename), 5);
   }

   /**
    * Load the file details from the request submit object and build a new Elluminate_Preload Object
    * @return Elluminate_Preload
    */
   public function loadFile(){
      try {
          $preload = $this->preloadFactory->getPreload();
      } catch (Elluminate_Exception $e) {
          echo $OUTPUT->notification(get_string($e->getUserMessage(), 'elluminate'));           
      } catch (Exception $e) {
          echo $OUTPUT->notification(get_string('user_error_soaperror', 'elluminate'));
      }

      //File not selected
      if ($_FILES['whiteboard']['name'] == '') {
         redirect($this->wwwroot . '/mod/elluminate/preload-form.php?id=' . $this->preloadSession->id,
            get_string('preloadfileinvalidname', 'elluminate',$preload->realfilename), 5);
      }
      
      if (!empty($_FILES['whiteboard'])) {
         $preload->realfilename = $_FILES['whiteboard']['name'];
         $preload->filepath = $_FILES['whiteboard']['tmp_name'];
         $preload->size = $_FILES['whiteboard']['size'];
      }
      $preload->initFile();
      return $preload;
   }
   
   /**
    * Handle a request to delete a preload
    */
   public function processDeleteAction($deleteid){
      try {
          $preload = $this->preloadFactory->getPreload();
      } catch (Elluminate_Exception $e) {
          echo $OUTPUT->notification(get_string($e->getUserMessage(), 'elluminate'));           
      } catch (Exception $e) {
          echo $OUTPUT->notification(get_string('user_error_soaperror', 'elluminate'));
      }
      $preload->loadPreload($deleteid);
      
      //Could not load preload from DB
      if (!$preload->id){
         print_error(get_string('preloaddeleteerror', 'elluminate'));
      }
      
      try{
         $preload->deletePreload($this->preloadSession);
      }catch(Elluminate_Exception $e)
      {
         print_error(get_string('preloaddeleteerror', 'elluminate',
            $e->getMessage()));
      }
      
      redirect($this->wwwroot . '/mod/elluminate/view.php?id=' . $this->courseModule->id,
         get_string('preloaddeletesuccess', 'elluminate'), 5);
   }
}