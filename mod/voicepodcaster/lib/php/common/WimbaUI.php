<?php
class WimbaUI {
    
    var $session;
    var $api;
    var $xml;
    var $currentObject;
    var $isArchive = "false";
 	var $resourceType = "room";
    var $lectureRoom;
    var $discussionRoom;
    var $isLectureRoom;
    var $disabledSetting;
    var $currentTab;
    //for vt
    var $currentObjectInformations = NULL;
    var $currentObjectOptions = NULL;
    var $currentObjectAudioFormat = NULL;
    var $startSelect = false;
    var $endSelect = false;
    var $prefix;
    var $id;
    
    function WimbaUI($session_params, $api = NULL, $currentIdtab = "")
    {
        $this->session = new WimbaMoodleSession($session_params);
        $this->api = $api;
        $this->xml = new WimbaXml();
        $this->currentTab = $currentIdtab;
        
        if($api!=null)
        {
            $this->prefix=$api->getPrefix();
        }
    }
    
    /**
    * Configure different parmaters used to generate the xml according to the current product 
    * @param product : the product which will be display (liveclassroom or voicetools)
    * @param $serverInformations : contains the object informations get from the server
    * @param $databaseInformations : contains the informations get from the database
    */
    function setCurrentProduct($product, $objectInformation = NULL,  $databaseInformations= NULL)
    {
        $this->product = $product;
        $this->currentObject = $objectInformation;
       
        if($this->currentObject == null) 
        {
            $this->id = $this->session->getCourseId().rand();//generate the id for the resource
        }
        
        if ($product == "liveclassroom") 
        {
            if( isset($this->currentObject) )
            {
                $roomId=$this->currentObject->getRoomId();
                $isStudentAdmin= $this->api->isStudentAdmin($roomId, $this->session->getCourseId()."_S");
            }
            
            if ($this->currentObject != null && $this->currentObject->isArchive()) 
            {
                $this->isArchive = "true";
				$this->resourceType =  "archive";
            }  
  
            if ($this->currentObject == null 
                || $this->currentObject != null && isset($isStudentAdmin) && $isStudentAdmin == "false")
            {     
                $this->lectureRoom = "activeSetting";
                $this->discussionRoom = "hiddenSetting";
                $this->disabledSetting = "activeSetting";
                $this->isLectureRoom = true;
            }
            else 
            {
                $this->lectureRoom = "hiddenSetting";
                $this->discussionRoom = "activeSetting";
                $this->disabledSetting = "disabledSetting";  
                $this->isLectureRoom = false;
            }
        }
        else 
        {
            //data of the database
            $this->currentObjectInformations = $databaseInformations;
            if ($this->currentObjectInformations != null 
                && $this->currentObjectInformations->start_date != -1) 
            {
                $this->startSelect = true;
            }
            
            if ( $this->currentObjectInformations != null 
                && $this->currentObjectInformations->end_date != -1 ) 
            {
                $this->endSelect = true;
            }
            
            if (isset($this->currentObject) 
                && isset($this->currentObjectInformations)) {
                //for php 4-> object->object doesn't work
                $this->currentObjectOptions = $this->currentObject->getOptions();
                $this->currentObjectAudioFormat = $this->currentObjectOptions->getAudioFormat();
            }
        }
    }
    
     /**
    * Add the necessary elements to the current xml object to render the principal view of the lc component
    * @param message : eventual information message displayed at the bottom of the component
    */
    function getLCPrincipalView($message) {
        /********
         SESSION
         *********/
        $this->xml->CreateInformationElement(
                        $this->session->timeOfLoad,
                        $this->session->hparams["firstname"],
                        $this->session->hparams["lastname"],
                        $this->session->hparams["email"],
                        $this->session->hparams["role"],
                        $this->session->hparams["course_id"],
                        $this->session->signature,
                        "");
        
        /********
         HEADER
         *********/
        if($this->session->isInstructor())
        {
            $this->xml->addHeaderElement("lib/web/pictures/items/headerbar-logo.png", "false", "true");
        }
        else
        {
            $this->xml->addHeaderElement("lib/web/pictures/items/headerbar-logo.png", "false", "false");
        }
        /********
         MENU
         *********/
        $this->xml->addButtonElement(
                        "all",
                        "all",
                        "disabled",
                        "launch",
                        get_string('toolbar_launch', 'liveclassroom'),
                        "javascript:LaunchElement('manageAction.php','liveclassroom');");

        if($this->session->isInstructor())
        {
            $this->xml->addButtonElement(
                            "instructor",
                            "all",
                            "disabled",
                            'activities',
                            get_string('toolbar_activity', 'liveclassroom'),
                            "doOpenAddActivity('../../course/mod.php','section=0&sesskey=" . sesskey() . "&add=liveclassroom')");     
            
            $this->xml->addButtonElement(
                            "instructor",
                            "all", 
                            "enabled",
                            "new",
                            get_string('toolbar_new', 'liveclassroom'),
                            "javascript:loadNewSettings('generateXmlSettingsPanel.php','create','liveclassroom' ,'liveclassroom','all')");
            
            $this->xml->addSpaceElement("20px", "instructor");
            
            $this->xml->addButtonElement(
                            "instructor",
                            "all",
                            "disabled",
                            "content",
                            get_string('toolbar_content', 'liveclassroom'),
                            "javascript:openContent('manageAction.php');");
            
            $this->xml->addButtonElement(
                            "instructor",
                            "all",
                            "disabled",
                            "report",
                            get_string('toolbar_reports', 'liveclassroom'),
                            "javascript:openReport('manageAction.php');");
             
            $this->xml->addSpaceElement("10px", "instructor");
            
            $this->xml->addButtonElement(
                            "instructor",
                            "all",
                            "disabled",
                            "settings",
                            get_string('toolbar_settings', 'liveclassroom'),
                            "javascript:editSettings('generateXmlSettingsPanel.php','all');");
             
            $this->xml->addButtonElement(
                            "instructor",
                            "all",
                            "disabled",
                            "delete",
                            get_string('toolbar_delete', 'liveclassroom'),
                            "javascript:deleteResource('manageAction.php');");
            
            $this->xml->addSpaceElement("50px", "instructor");
        }
        else
        {
            $this->xml->addSpaceElement("300px", "instructor");
        }
        
        
        //MESSAGE BAR
        if (isset ($message) && $message!="") 
        {
            $this->xml->addMessage($message);
        }
        
        $rooms=$this->getListLiveClassroom();
        
        // https://wimba.agilebuddy.com/bugs/3558
        // When $rooms is set to the boolean "false", $rooms == null will evaluate to true.
        // We need need to use the === operator as this checks type as well.
        if ($rooms != false || $rooms === null || empty($rooms))
        {
            $this->xml->addProduct(
                            "liveclassroom",
                            "productType",
                            "Live classroom", 
                            "liveclassroom",
                            $rooms ,
                            get_string('list_no_liveclassrooms', 'liveclassroom'),
                            array("&#160;","Title","Access","Download","Room Details"));
        }
        else 
        {
            //problem to get the vt resource
            if (isset($this->api->errormsg)) {
                $this->xml->setError($this->api->errormsg.' '.get_string('contactadmin','liveclassroom'));
            } else {
                $this->xml->setError(get_string('error_connection_lc','liveclassroom'));
            }
        }
    }
    
    
     /**
    * Add the necessary elements to the current xml object to render the principal view of the vt component
    * @param message : eventual information message displayed at the bottom of the component
    */    
    function getVTPrincipalView($message,$type=null) {
        /********
         SESSION
         *********/
        $this->xml->CreateInformationElement(
                        $this->session->timeOfLoad,
                        $this->session->hparams["firstname"],
                        $this->session->hparams["lastname"],
                        $this->session->hparams["email"],
                        $this->session->hparams["role"],
                        $this->session->hparams["course_id"],
                        $this->session->signature,
                        "");
        /********
         HEADER
         *********/
        ( $this->session->isInstructor() )
            ? $this->xml->addHeaderElement("lib/web/pictures/items/headerbar-logo.png", "false", "true")
            : $this->xml->addHeaderElement("lib/web/pictures/items/headerbar-logo.png", "false", "false")
            ;
        
        /********
         MENU
         *********/
        $this->xml->addButtonElement(
                        "all",
                        "all",
                        "disabled",
                        "launch",
                        get_string('toolbar_launch', 'voicepodcaster'),
                        "javascript:LaunchElement('manageAction.php','voicepodcaster');");
                                     
        if($this->session->isInstructor())
        {
            $this->xml->addButtonElement(
                            "instructor",
                            "all",
                            "disabled",
                            'activities',
                            get_string('toolbar_activity', 'voicepodcaster'),
                            "doOpenAddActivity('../../course/mod.php','section=0&sesskey=" . sesskey() . "&add=voicepodcaster')");
                                         
            $this->xml->addButtonElement(
                            "instructor",
                            "all",
                            "enabled",
                            "new",
                            get_string('toolbar_new', 'voicepodcaster'),
                            "javascript:loadNewSettings('generateXmlSettingsPanel.php','create','voicetools' ,'pc','all','false')");
                                         
            $this->xml->addSpaceElement("150px", "instructor");
            if(isGradebookAvailable() && isset($type) && $type == "board"){
              $this->xml->addButtonElement(
                              "instructor", 
                              "all",
                              "disabled",
                              "grade",
                              get_string('toolbar_grade', 'voicepodcaster'),
                              "javascript:showGrades('grades.php');");
            }                                 
            $this->xml->addButtonElement(
                            "instructor", 
                            "all",
                            "disabled",
                            "settings",
                            get_string('toolbar_settings', 'voicepodcaster'),
                            "javascript:editSettings('generateXmlSettingsPanel.php','all');");
                                         
            $this->xml->addButtonElement(
                            "instructor",
                            "all",
                            "disabled",
                            "delete",
                            get_string('toolbar_delete', 'voicepodcaster'),
                            "javascript:deleteResource('manageAction.php');");
                                         
            $this->xml->addSpaceElement("50px", "instructor");
        }
        else
        {
            $this->xml->addSpaceElement("300px", "instructor");
        }

        /********
         MESSAGE BAR
         *********/
        $resources = $this->getListVoiceTools();
     
        if (is_array($resources)) 
        {
            $this->xml->addProduct(
                            "voicetools",
                            "productType",
                            "Voice Board",
                            "board",
                            $resources,
                            get_string('list_no_voicepodcasters', 'voicepodcaster'));
        }
        else 
        {//problem to get the vt resource
            wimba_add_log(WIMBA_INFO,voicepodcaster_LOGS,"No resource have been created yet");  
            $this->xml->setError(get_string("error_connection_vt", "voicepodcaster"));
        }
        
        if (isset ($message) && $message!="") 
        {
            $this->xml->addMessage($message);
        }
    
    }
    
    function getLCSettingsView($update) {
        /********
         SESSION
         *********/

        $this->xml->CreateInformationElement(
                        $this->session->timeOfLoad,
                        $this->session->hparams["firstname"],
                        $this->session->hparams["lastname"],
                        $this->session->hparams["email"],
                        $this->session->hparams["role"],
                        $this->session->hparams["course_id"],
                        $this->session->signature,
                        "");
        /********
         HEADER
         *********/
        $this->xml->addHeaderElement("lib/web/pictures/items/headerbar-logo.png", "true", "true");
    
        if($update=="update")
        {
            $this->xml->addContextBarElement(
                            get_string("contextbar_settings", "liveclassroom"),
                            get_string("general_" . $this->product, "liveclassroom"),
                            $this->currentObject->getLongname(), "");
        }
        else
        {   
            $this->xml->addContextBarElement(
                            get_string("contextbar_settings", "liveclassroom"),
                            get_string("general_" . $this->product, "liveclassroom"),
                            get_string("contextbar_new_" . $this->product, "liveclassroom"), "");
        }
        /********
         * Settings tabs
         */
        $this->config = $this->api->getSystemConfig();
        $this->createLcInfoPanel();
        $this->createLcArchivesPanel();
        $this->createLcMediaPanel();
        $this->createLcFeaturesPanel();
        $this->createLcChatPanel();
        $this->createLcAccessPanel();
        if( $this->currentObject == null || !$this->currentObject->isArchive() || $this->currentObject->getArchiveVersion() == VALUE_50_ARCHIVE){
          $this->createLcAudioSettingsPanel();
        }
        
        if ($update == "update" && $this->currentObject != null && !$this->currentObject->isArchive()) 
        {
            $this->createLcAdvancedPanel();
            $this->xml->createValidationButtonElement(
                        get_string("validationElement_ok", "liveclassroom"),
                        "submit",
                        "javascript:launchAjaxRequest('generateXmlMainPanel.php','',true,'all')", 
                        "advanced_Ok",
                        "hideElement");
        }
        
  
        
       
        $this->xml->createValidationButtonElement(
                        get_string("validationElement_cancel", "liveclassroom"),
                        "link",
                        "javascript:launchAjaxRequest('generateXmlMainPanel.php','',true,'all')",
                        "setting_Cancel");
   
        if ($update != "update") 
        {
            $this->xml->createValidationButtonElement(
                            get_string("validationElement_create", "liveclassroom"),
                            "submit",
                            "javascript:submitForm('manageAction.php','create','".$this->id."')",
                            "setting_Create");
        }
        else 
        {
            $this->xml->createValidationButtonElement(
                            get_string("validationElement_saveAll", "liveclassroom"),
                            "submit",
                            "javascript:submitForm('manageAction.php','update','".$this->currentObject->getRoomId()."')",
                            "setting_Save");
        }
    }
    
    function getVTSettingsView($update) {
   
        /********
         SESSION
         *********/
         $this->xml->createInformationElement(
                        $this->session->timeOfLoad,
                        $this->session->hparams["firstname"],
                        $this->session->hparams["lastname"],
                        $this->session->hparams["email"],
                        $this->session->hparams["role"],
                        $this->session->hparams["course_id"],
                        $this->session->signature,
                        "");
         /********
          HEADER
          *********/
         $this->xml->addHeaderElement("lib/web/pictures/items/headerbar-logo.png", "true", "true");
         if($update=="update")
         {
            $this->xml->addContextBarElement(
                            get_string("contextbar_settings", 'voicepodcaster'),
                            get_string("general_" . $this->product, 'voicepodcaster'),
                            $this->currentObject->getTitle(),
                            "");
         }
         else
         {
            $this->xml->addContextBarElement(
                            get_string("contextbar_settings", 'voicepodcaster'),
                            get_string("general_" . $this->product, 'voicepodcaster'),
                            get_string("contextbar_new_" . $this->product, 'voicepodcaster'),
                            "");
         }
        
         /********
          * Settings tabs
          */
         if ($this->product == "board") 
         {
             $this->createGeneralInfoPanelStart();
             $this->createVBInfoPanel();
             //Media settings
             $this->createVBVPMediaPanel();
             $this->createVBFeaturesPanel();
             $this->createVTAccessPanel();
         }
         elseif ($this->product == "presentation") 
         {
             $this->createGeneralInfoPanelStart();
             $this->createVPInfoPanel();
             //Media settings
             $this->createVBVPMediaPanel();
             $this->createVTAccessPanel();
         }
         elseif ($this->product == "pc") 
         {
             $this->createGeneralInfoPanelStart();
             
             $this->createPCInfoPanel();
             //Media settings
             $this->createPCMediaPanel();
             $this->createPCFeaturesPanel();
             $this->createVTAccessPanel();
         }
        
        $this->xml->createValidationButtonElement(
                        get_string("validationElement_cancel", 'voicepodcaster'),
                        "link",
                        "javascript:launchAjaxRequest('generateXmlMainPanel.php','',true,'all')",
                        "setting_Cancel");

        if ($update != "update") 
        {
            $this->xml->createValidationButtonElement(
                            get_string("validationElement_create", 'voicepodcaster'),
                            "submit",
                            "javascript:submitForm('manageAction.php','create','')",
                            "setting_Create");
        }
        else 
        {
            $this->xml->createValidationButtonElement(
                            get_string("validationElement_saveAll", 'voicepodcaster'),
                            "submit",
                            "javascript:submitForm('manageAction.php','update','" . $this->currentObject->getRid() . "')",
                            "setting_Save");
        }
    }
    
     /**
    * Add the necessary elements to the current xml object to render the panel information of the lc settings
    */   
    function createLcInfoPanel() {
        
        $this->xml->addCustomLineElement( "*",
                                          "required",  
                                          get_string("settings_title", 'liveclassroom'),
                                          array("class"=>"LargeLabel_Width TextRegular_Right"));
        
        $parameters = array (
            "type" => "input",
            "name" => "longname",
            "id" => "longname",
            "style" => "input",
            "maxlength" => "50"
        );
        if ($this->currentObject != null) 
        { 
            $parameters["value"] = $this->currentObject->getLongName();
        }
        $this->xml->addInputElement($parameters);
  
        $this->xml->addCustomLineElement( "*",
                                          "required",  
                                          get_string("settings_required", 'liveclassroom')
                                          );

        $this->xml->createLine();
        
        $this->xml->addSimpleLineElement( "label", 
                                            get_string("settings_description", 'liveclassroom'),
                                            array("class"=>"LargeLabel_Width TextRegular_Right"));
        
        $parameters = array (
            "name" => "description",
            "id" => "description",
            "rows" => "4",
            "cols" => "30"
        ); 
        $display = "";
        if (isset ($this->currentObject)) 
        {
            $display = $this->currentObject->getDescription();
        }
        $this->xml->addTextAreaElement($parameters,$display);
        
        $this->xml->createLine();
        
        $this->xml->addSimpleLineElement( "label", 
                                            get_string("settings_type", 'liveclassroom'),
                                            array("class"=>"LargeLabel_Width TextRegular_Right"));
        
        $parameters = array (
            "type" => "radio",
            "name" => "led",
            "value" => "instructor",
            "id" => "led_instructor",
            "onclick" => "toggleTypeOfRoom(\"lectureRoom\")"
        );
        if($this->isArchive == "true") 
        {
            $parameters["disabled"] = "true";
        }

        $roomId="";
        if(isset($this->currentObject)){
            $roomId=$this->currentObject->getRoomId();
            $courseId=$this->session->getCourseId() . "_S";
            $isStudentAdmin= $this->api->isStudentAdmin($roomId, $courseId);
        }
        if ($this->currentObject == null || $isStudentAdmin == "false") {
            $parameters["checked"] = "true";
        }
        $this->xml->addInputElement($parameters);
        
        $parameters = array (
            "class" => "top",
            "for" => "led_instructor",   
        );
        if($this->isArchive=="true") 
        {
            $parameters["disabled"] ="true";
        }
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string("settings_mainLecture_comment", 'liveclassroom'),
                         $parameters);
        
        $this->xml->createLine();
        
        $parameters = array (
            "type" => "radio",
            "name" => "led",
            "value" => "student",
            "id" => "led_student",    
            "onclick" => "toggleTypeOfRoom(\"discussionRoom\")",
            "class" => "AlignRight"
        );
        if($this->isArchive == "true") 
        {
            $parameters["disabled"] = "true";
        }
        if ($this->currentObject != null && $isStudentAdmin == "true") 
        {
            $parameters["checked"] = "true";
        }
        $this->xml->addInputElement($parameters);
        
        $parameters = array (
            "class" => "top",
            "for" => "led_student",   
        );
        if($this->isArchive=="true") 
        {
            $parameters["disabled"] = "true";
        }
        
        $this->xml->addSimpleLineElement(
                        "label",
                        get_string("settings_discussion_comment", 'liveclassroom'),
                         $parameters);
        
        $this->xml->createLine();
        if ($this->currentTab == "Info" || $this->currentTab == "") {
            $this->xml->createPanelSettings(
                            get_string("tab_title_roomInfo", 'liveclassroom'),
                            "block",
                            "Info",
                            "current",
                            "all",
                            "editSettings(\"generateXmlSettingsPanel.php\",\"all\")");
        }
        else
        {
            $this->xml->createPanelSettings(
                            get_string("tab_title_roomInfo", 'liveclassroom'),
                            "none",
                            "Info",
                            "",
                            "all",
                            "editSettings(\"generateXmlSettingsPanel.php\",\"all\")");
        }
    }
    
    /**
    * Add the necessary elements to the current xml object to render the panel archives of the lc settings
    */
    function createLcArchivesPanel() {

        if ($this->currentObject == null || !$this->currentObject->isArchive()) {

            $this->xml->addSimpleLineElement("label",
                                             get_string("settings_enable_archives", 'liveclassroom'),
                                             array("class"=>"LargeLabel_Width TextRegular_Right"));
                           
            $parameters = array('type' => 'checkbox',
                                'name' => 'enable_archives',
                                'value' => 'true',
                                'id' => 'enable_archives');
            if ($this->currentObject == null || $this->currentObject->isArchiveEnabled()) {
              $parameters["checked"] = "true";
            }
            $this->xml->addInputElement($parameters);
            $this->xml->createLine();

            $this->xml->addSimpleLineElement("label",
                                             get_string("settings_archive_access", 'liveclassroom'),
                                             array("class"=>"LargeLabel_Width TextRegular_Right"));
                                             
            $parameters = array('type' => 'checkbox',
                                'name' => 'auto_open_archive',
                                'value' => 'true',
                                'id' => 'auto_open_archive');
            if ($this->currentObject == null || $this->currentObject->isAutoOpenArchive()) {
              $parameters["checked"] = "true";
            }
            $this->xml->addInputElement($parameters);
            
            $this->xml->addSimpleLineElement("label",
                                             get_string("settings_auto_open_archive", 'liveclassroom'));
            $this->xml->createLine();


            $this->xml->addSimpleLineElement("label",
                                             get_string("settings_display_archive_reminder", 'liveclassroom'),
                                             array("class"=>"LargeLabel_Width TextRegular_Right"));
                                
            $parameters = array('type' => 'checkbox',
                                              'name' => 'display_archive_reminder',
                                              'value' => 'true',
                                              'id' => 'display_archive_reminder');
            if ($this->currentObject == null || $this->currentObject->isArchiveReminderEnabled()) {
              $parameters["checked"] = "true";
            }                 
            $this->xml->addInputElement($parameters);
            $this->xml->createLine();

            if ($this->currentTab == "Archives") {
                $this->xml->createPanelSettings(get_string("tab_title_archives", 'liveclassroom'),
                                                "block",
                                                "Archives",
                                                "current",
                                                "mainLecture",
                                                "editSettings(\"generateXmlSettingsPanel.php\",\"all\")");
            } else {
                $this->xml->createPanelSettings(get_string("tab_title_archives", 'liveclassroom'),
                                                "none",
                                                "Archives",
                                                "",
                                                "mainLecture",
                                                "editSettings(\"generateXmlSettingsPanel.php\",\"all\")");

            }

        }

    }

    /**
    * Add the necessary elements to the current xml object to render the panel medua of the lc settings
    */  
    function createLcMediaPanel() {
        $options = array ();
        //this tab is not available for archives
        if ($this->currentObject == null || !$this->currentObject->isArchive()) 
        {
            $this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_student_privileges", 'liveclassroom'),
                            array("class"=>"LargeLabel_Width TextRegular_Right"));
                               
            $parameters = array (
                "type" => "checkbox",
                "name" => "hms_two_way_enabled",
                "value" => "true",
                "id" => "hms_two_way_enabled",
                "class" => "discussionRoomDisabled"
            );
            if ($this->lectureRoom == "hiddenSetting") 
            {
              $parameters["disabled"]= "disabled";
            }
            if ($this->currentObject == null || $this->currentObject->isHmsTwoWayEnabled()) 
            {
              $parameters["checked"] = "true";
            }
            $this->xml->addInputElement($parameters);   
      
            $this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_hms_two_way_enabled", 'liveclassroom'));
      
            $this->xml->createLine();

            
            $parameters = array (
                "type" => "checkbox",
                "name" => "enable_student_video_on_startup",
                "value" => "true",
                "id" => "enable_student_video_on_startup",
                "class" => "AlignRight discussionRoomDisabled"
            );
            if ($this->lectureRoom == "hiddenSetting") 
            {
                $parameters["disabled"]= "disabled";
            }
            if ($this->currentObject == null || $this->currentObject->isStudentVideoOnStartupEnabled()) 
            {
                $parameters["checked"] = "true";
            }
            $this->xml->addInputElement($parameters);   
        
    
            $this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_enable_student_video_on_startup", 'liveclassroom'));
        
            $this->xml->createLine();
            
            $parameters = array (
                "type" => "checkbox",
                "name" => "hms_simulcast_restricted",
                "value" => "25",
                "id" => "hms_simulcast_restricted",
                "class" => "AlignRight discussionRoomDisabled"
            );
            if ($this->lectureRoom == "hiddenSetting") 
            {
                $parameters["disabled"]= "disabled";
            }
    
            if ($this->currentObject == null || !$this->currentObject->isHmsSimulcastRestricted()) 
            {
                $parameters["checked"] = "true";
            }
            $this->xml->addInputElement($parameters);
                   
            $this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_hms_simulcast_restricted", 'liveclassroom'));
         
            $this->xml->createLine();
              
            $this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_video_bandwidth", 'liveclassroom'),
                            array("class"=>"LargeLabel_Width TextRegular_Right"));
                            
  
            //select parameter
            $dfltBandwidth = $this->config['dflt-bandwidth'];
            $bandwidthCap = $this->config['bandwidth_cap'];

            // Default when creating a room to fastest alowed
            $currentBandwidth = array("fastest" => 512, "fast" => 256, "medium" => 128, "slow" => 32);
            $currentPreset = isset($this->currentObject) ? $currentBandwidth[$this->currentObject->getVideoBandwidth()] : $dfltBandwidth;
            $optionsParamaters = array ("value" => "fastest",
                                        "display" => get_string("settings_video_bandwidth_fastest",'liveclassroom'),
                                        "selected" => ($currentPreset == 512) ? 'true' : null);

            if ($optionsParamaters['selected'] || $bandwidthCap >= $currentBandwidth[$optionsParamaters['value']]) {
                $options[] = $optionsParamaters;
            }

            $optionsParamaters = array ("value" => "fast",
                                        "display" => get_string("settings_video_bandwidth_fast",'liveclassroom'),
                                        "selected" => ($currentPreset == 256) ? 'true' : null);

            if ($optionsParamaters['selected'] || $bandwidthCap >= $currentBandwidth[$optionsParamaters['value']]) {
                $options[] = $optionsParamaters;
            }

            $optionsParamaters = array ("value" => "medium",
                                        "display" => get_string("settings_video_bandwidth_medium",'liveclassroom'),
                                        "selected" => ($currentPreset == 128) ? 'true' : null);

            if ($optionsParamaters['selected'] || $bandwidthCap >= $currentBandwidth[$optionsParamaters['value']]) {
                $options[] = $optionsParamaters;
            }

            $optionsParamaters = array ("value" => "slow",
                                        "display" => get_string("settings_video_bandwidth_slow",'liveclassroom'),
                                        "selected" => ($currentPreset == 32) ? 'true' : null);

            $options[] = $optionsParamaters;
                
            $optionsParamaters = array (
                "value" => "custom",
                "display" => get_string("settings_video_bandwidth_custom",'liveclassroom')
            );
    
            if ($this->currentObject != null && $this->currentObject->getVideoBandwidth() == "custom") 
            {
                $optionsParamaters["selected"] = "true";
                $options[] = $optionsParamaters;
            }
            $this->xml->createOptionElement("video_bandwidth", "video_bandwidth", $options);
            $this->xml->createLine("subPart");
            unset($options);

            $this->xml->createLine();
            if(isset($bandwidthCap) && $currentPreset > $bandwidthCap) {
                $this->xml->addSimpleLineElement('span',get_string('settings_video_bandwidth_cap_set','liveclassroom',$bandwidthCap),array('style' => 'color:#f00;'));
            }
            $this->xml->createLine();

            if ($this->currentObject != null && $this->currentObject->getVideoBandwidth() == "custom") {

                $this->xml->addSimpleLineElement(
                                "label",
                                get_string("settings_video_popup_size", 'liveclassroom'),
                                array("class"=>"LargeLabel_Width TextRegular_Right"));
                $options[] = array ("value" => "640x480",
                                    "display" => "640x480",
                                    "selected" => ($this->currentObject != null && $this->currentObject->getVideoWindowSizeOnStartup() == "640x480" ? "true" : null));
                $options[] = array ("value" => "320x240",
                                    "display" => "320x240",
                                    "selected" => ($this->currentObject != null && $this->currentObject->getVideoWindowSizeOnStartup() == "320x240" ? "true" : null));
                $options[] = array ("value" => "160x120",
                                    "display" => "160x120",
                                    "selected" => ($this->currentObject != null && $this->currentObject->getVideoWindowSizeOnStartup() == "160x120" ? "true" : null));
                $options[] = array ("value" => "80x60",
                                    "display" => "80x60",
                                    "selected" => ($this->currentObject != null && $this->currentObject->getVideoWindowSizeOnStartup() == "80x60" ? "true" : null));
                $this->xml->createOptionElement("video_window_size_on_startup", "video_window_size_on_startup", $options);
                $this->xml->createLine();
                unset($options);

                $this->xml->addSimpleLineElement(
                                "label",
                                get_string("settings_video_resolution", 'liveclassroom'),
                                array("class"=>"LargeLabel_Width TextRegular_Right"));
                $options[] = array ("value" => "640x480",
                                    "display" => "640x480",
                                    "selected" => ($this->currentObject != null && $this->currentObject->getVideoWindowEncodingSize() == "640x480" ? "true" : null));
                $options[] = array ("value" => "320x240",
                                    "display" => "320x240",
                                    "selected" => ($this->currentObject != null && $this->currentObject->getVideoWindowEncodingSize() == "320x240" ? "true" : null));
                $options[] = array ("value" => "160x120",
                                    "display" => "160x120",
                                    "selected" => ($this->currentObject != null && $this->currentObject->getVideoWindowEncodingSize() == "160x120" ? "true" : null));
                $options[] = array ("value" => "80x60",
                                    "display" => "80x60",
                                    "selected" => ($this->currentObject != null && $this->currentObject->getVideoWindowEncodingSize() == "80x60" ? "true" : null));
                $this->xml->createOptionElement("video_window_encoding_size", "video_window_encoding_size", $options);
                $this->xml->createLine();
                unset($options);

                $this->xml->addSimpleLineElement(
                                "label",
                                get_string("settings_video_bitrate", 'liveclassroom'),
                                array("class"=>"LargeLabel_Width TextRegular_Right"));
                $options[] = array ("value" => "512kb",
                                    "display" => "512kb",
                                    "selected" => ($this->currentObject != null && $this->currentObject->getVideoDefaultBitRate() == "512kb" ? "true" : null));
                $options[] = array ("value" => "256kb",
                                    "display" => "256kb",
                                    "selected" => ($this->currentObject != null && $this->currentObject->getVideoDefaultBitRate() == "256kb" ? "true" : null));
                $options[] = array ("value" => "128kb",
                                    "display" => "128kb",
                                    "selected" => ($this->currentObject != null && $this->currentObject->getVideoDefaultBitRate() == "128kb" ? "true" : null));
                $options[] = array ("value" => "32kb",
                                    "display" => "32kb",
                                    "selected" => ($this->currentObject != null && $this->currentObject->getVideoDefaultBitRate() == "32kb" ? "true" : null));
                $this->xml->createOptionElement("video_default_bit_rate", "video_default_bit_rate", $options);
                $this->xml->createLine();
                unset($options);
            }
        }
        
        if ($this->currentTab == "Media")
        {
            $this->xml->createPanelSettings(
                            get_string("tab_title_media", 'liveclassroom'),
                            "block",
                            "Media", 
                            "current", 
                            "mainLecture", 
                            "editSettings(\"generateXmlSettingsPanel.php\",\"all\")");
        }
        else
        {
            if ($this->currentObject != null && $this->currentObject->isArchive()) 
            {
                $this->xml->createPanelSettings(
                                get_string("tab_title_media", 'liveclassroom'),
                                "none",
                                "Media",
                                "disabled",
                                "mainLecture",
                                "editSettings(\"generateXmlSettingsPanel.php\",\"all\")");
            }
            else 
            {
                $this->xml->createPanelSettings(
                                get_string("tab_title_media", 'liveclassroom'),
                                "none",
                                "Media",
                                "",
                                "mainLecture",
                                "editSettings(\"generateXmlSettingsPanel.php\",\"all\")");
            }
        }
    }
    
    /**
    * Add the necessary elements to the current xml object to render the panel features of the lc settings
    */  
    function createLcFeaturesPanel() {
    
        if ($this->currentObject == null || !$this->currentObject->isArchive()) {
            $this->xml->addSimpleLineElement(
                            "label", 
                            get_string("settings_status_indicators", 'liveclassroom'),
                            array("class"=>"LargeLabel_Width TextRegular_Right"));
                            
            $parameters = array (
                "type" => "checkbox",
                "name" => "enabled_status",
                "value" => "true",
                "id" => "enabled_status",
                "onclick" => "doStatusEnabled()"
            );
            if ($this->currentObject == null || $this->currentObject->isUserstatusEnabled()) {
                $parameters["checked"] = "true";
            }
            $this->xml->addInputElement($parameters);
            $this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_enabled_status", 'liveclassroom'));
            
            $this->xml->createLine();
              
            $parameters = array (
                "type" => "checkbox",
                "name" => "status_appear",
                "value" => "true",
                "id" => "status_appear",
                "class" => "SubOption"
            );
            
            if ($this->currentObject != null && !$this->currentObject->isUserstatusEnabled()) 
            {
                $parameters["disabled"] = "true";
            }
            else{
                if ($this->currentObject == null || $this->currentObject->isSendUserstatusUpdates()) 
                {
                    $parameters["checked"] = "true";
                }
            }
            $this->xml->addInputElement($parameters);
            $this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_status_appear", 'liveclassroom'));
            
            $this->xml->createLine();   
            
            $this->xml->addSimpleLineElement("label", 
                                             get_string("settings_eboard", 'liveclassroom'),
                                              array("class"=>"LargeLabel_Width TextRegular_Right"));
            
            $parameters = array (
                "type" => "checkbox",
                "name" => "enabled_student_eboard",
                "value" => "true",
                "id" => "enabled_student_eboard",
            );
            if ($this->currentObject != null && $this->currentObject->isStudentWhiteboardEnabled()) 
            {
                $parameters["checked"] = "true";
            }
            $this->xml->addInputElement($parameters);
            $this->xml->addSimpleLineElement("label",
                                             get_string("settings_enabled_student_eboard", 'liveclassroom'));

            if($this->isLectureRoom)
            {                                 
                $this->xml->createLine("subPart","lectureRoom");
            }
            else   
            {
                 $this->xml->createLine("subPart hideElement","lectureRoom");
            }
            
            $this->xml->addSimpleLineElement("label", 
                                             get_string("settings_breakout", 'liveclassroom'), 
                                             array("class"=>"LargeLabel_Width TextRegular_Right"));
            if ($this->currentObject != null) {
                $isStudentAdmin = $this->api->isStudentAdmin($this->currentObject->getRoomId(), $this->session->getCourseId()."_S");
            }

            $parameters = array (
                "type" => "checkbox",
                "name" => "enabled_breakoutrooms",
                "value" => "true",
                "id" => "enabled_breakoutrooms",
                "onclick" => "doBreakoutEnabled()"
            );
            if ($this->currentObject == null || $this->currentObject->isBOREnabled() || $isStudentAdmin == "true") 
            {
                $parameters["checked"] = "true";
            }
            $this->xml->addInputElement($parameters);
            $this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_enabled_breakoutrooms", 'liveclassroom'));
                            
            if($this->isLectureRoom)
            {                                 
                $this->xml->createLine("subPart","lectureRoom");
            }
            else   
            {
                 $this->xml->createLine("subPart hideElement","lectureRoom");
            }
            
            $parameters = array (
                "type" => "checkbox",
                "name" => "enabled_students_breakoutrooms",
                "value" => "true",
                "id" => "enabled_students_breakoutrooms",
                "class"=>"SubOption"
            );
            if ($this->currentObject != null && !$this->currentObject->isBOREnabled() && $isStudentAdmin == "false") 
            {
                $parameters["disabled"] = "true";
            }
            else
            {
                if ($this->currentObject != null && 
                        $this->currentObject->isBOREnabled() && 
                        $this->currentObject->isBORCarouselsPublic()) 
                {
                    $parameters["checked"] = "true";
                }
            }
            $this->xml->addInputElement($parameters);
            $this->xml->addSimpleLineElement(
                            "label", 
                            get_string("settings_enabled_students_breakoutrooms", 'liveclassroom'));

            if($this->isLectureRoom)
            {                                 
                $this->xml->createLine("","lectureRoom");
            }
            else   
            {
                 $this->xml->createLine("hideElement","lectureRoom");
            }
            
            $parameters = array (
                "type" => "checkbox",
                "name" => "enabled_students_mainrooms",
                "value" => "true",
                "id" => "enabled_students_mainrooms",
                "class"=>"SubOption"
            );
            if ($this->currentObject != null && !$this->currentObject->isBOREnabled() && $isStudentAdmin == "false") 
            {
                $parameters["disabled"] = "true";
            }
            else 
            {
                if ($this->currentObject != null && 
                        $this->currentObject->isBOREnabled() && 
                        $this->currentObject->isBORShowRoomCarousels())
                {
                    $parameters["checked"] = "true";
                }
            }
            $this->xml->addInputElement($parameters);
            $this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_enabled_students_mainrooms", 'liveclassroom'));
       
            
            if($this->isLectureRoom)
            {                                 
                $this->xml->createLine("","lectureRoom");
            }
            else   
            {
                 $this->xml->createLine("hideElement","lectureRoom");
            }
                            
            $this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_presenter_console", 'liveclassroom'),
                            array("class"=>"LargeLabel_Width TextRegular_Right"));

            
            $parameters = array (
                "type" => "checkbox",
                "name" => "archiveEnabled",
                "value" => "true",
                "id" => "archiveEnabled"
            );

            if ($this->currentObject == null || $this->currentObject->isArchiveEnabled()) {
                $parameters["checked"] = "true";
            }
            $this->xml->addInputElement($parameters);
            $this->xml->addSimpleLineElement(
                            "label", 
                            get_string("settings_enabled_archiving", 'liveclassroom'));
            
            
            if($this->isLectureRoom)
            {                                 
               $this->xml->createLine("hideElement subPart","discussionRoom");
            }
            else   
            {
                 $this->xml->createLine("subPart","discussionRoom");
            }
            
            $parameters = array (
                "type" => "checkbox",
                "name" => "appshareEnabled",
                "value" => "true",
                "id" => "enable_appshare",
                "class" => "AlignRight"
            );
            if ($this->currentObject == null || $this->currentObject->isLiveShareEnabled()) {
                $parameters["checked"] = "true";
            }
            $this->xml->addInputElement($parameters);
            $this->xml->addSimpleLineElement(
                            "label", 
                            get_string("settings_enabled_appshare", 'liveclassroom'));
            
            if($this->isLectureRoom)
            {                                 
               $this->xml->createLine("hideElement","discussionRoom");
            }
            else   
            {
                 $this->xml->createLine("","discussionRoom");
            }
            
            $parameters = array (
                "type" => "checkbox",
                "name" => "pptEnabled",
                "value" => "true",
                "id" => "enable_ppt",
                "class" => "AlignRight"
            );
            if ($this->currentObject == null || $this->currentObject->isPptImportEnabled()) {
                $parameters["checked"] = "true";
            }
            $this->xml->addInputElement($parameters);
            $this->xml->addSimpleLineElement(
                                "label", 
                                get_string("settings_enabled_onfly_ppt", 'liveclassroom'));
            
            if($this->isLectureRoom)
            {                                 
               $this->xml->createLine("hideElement","discussionRoom");
            }
            else   
            {
                 $this->xml->createLine("","discussionRoom");
            }
        }
        
        if ($this->currentObject != null && $this->currentObject->isArchive()) 
        {
            $this->xml->createPanelSettings(
                            get_string("tab_title_features", 'liveclassroom'),
                            "none",
                            "Features",
                            "disabled",
                            "mainLecture-discussion",
                            "editSettings(\"generateXmlSettingsPanel.php\",\"all\")");
        }
        else
        {
            if ($this->currentTab == "Features") 
            {
                $this->xml->createPanelSettings(
                                get_string("tab_title_features", 'liveclassroom'),
                                "block",
                                "Features",
                                "current",
                                "mainLecture-discussion",
                                "editSettings(\"generateXmlSettingsPanel.php\",\"all\")");
            }
            else 
            {
                $this->xml->createPanelSettings(
                                get_string("tab_title_features", 'liveclassroom'),
                                "none",
                                "Features",
                                "",
                                "mainLecture-discussion",
                                "editSettings(\"generateXmlSettingsPanel.php\",\"all\")");
            }
        }
    }
    
    /**
    * Add the necessary elements to the current xml object to render the panel chat of the lc settings
    */  
    function createLcChatPanel() {
        if ($this->currentObject == null || !$this->currentObject->isArchive()) 
        {
            //Panel Chat
            $parameters = array (
                "type" => "checkbox",
                "name" => "chatEnabled",
                "value" => "true",
                "id" => "chatEnabled",
                "onclick" => "doChangeChat()",
                "class" => "Padding_50px"
            );
            if ($this->currentObject == null || $this->currentObject->isChatEnabled()) 
            {
               $parameters["checked"] = "true";
            }
            $this->xml->addInputElement($parameters);
            $this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_chat_enabled", 'liveclassroom'));
            
            $this->xml->createLine();
            $parameters = array (
                "type" => "checkbox",
                "name" => "privateChatEnabled",
                "value" => "true",
                "id" => "privateChatEnabled",
                "class" => "smallSubOption"
            );
            if ($this->currentObject != null && !$this->currentObject->isChatEnabled()) 
            {
                $parameters["disabled"] = "true";
                $parameters["checked"] = "true";
            }
            else {
               if ($this->currentObject == null || 
                       (($this->currentObject->isChatEnabled() && $this->currentObject->isPrivateChatEnabled()))) 
               {
                   $parameters["checked"] = "true";
               }
            }
            $this->xml->addInputElement($parameters);
            $this->xml->addSimpleLineElement(
                            "label", 
                            get_string("settings_private_chat_enabled", 'liveclassroom'));
            
            $this->xml->createLine();
            
            $this->xml->addSimpleLineElement(
                            "label", 
                            get_string("settings_private_chat_enabled_comment", 'liveclassroom'),
                            array("class" => "TextComment Padding_50px"));

            $this->xml->createLine();
        }
        
        if ($this->currentTab == "Chat")
        {
            $this->xml->createPanelSettings(
                            get_string("tab_title_chat", 'liveclassroom'),
                            "block",
                            "Chat",
                            "current",
                            "mainLecture",
                            "editSettings(\"generateXmlSettingsPanel.php\",\"all\")");
        }
        else
        {
            if ($this->isLectureRoom === false || isset( $this->currentObject ) && $this->currentObject->isArchive())
            {
                $this->xml->createPanelSettings(
                                get_string("tab_title_chat", 'liveclassroom'),
                                "none",
                                "Chat",
                                "disabled",
                                "mainLecture",
                                "editSettings(\"generateXmlSettingsPanel.php\",\"all\")");
            }
            else
            {
                $this->xml->createPanelSettings(
                                get_string("tab_title_chat", 'liveclassroom'),
                                "none",
                                "Chat",
                                "",
                                "mainLecture",
                                "editSettings(\"generateXmlSettingsPanel.php\",\"all\")");
            }
        }
    } 
    
    /**
    * Add the necessary elements to the current xml object to render the panel acess of the lc settings
    */  
    function createLcAccessPanel() {
        $parameters = array (
            "type" => "checkbox",
            "name" => "accessAvailable",
            "value" => "true",
            "id" => "accessAvailable_true",
            "class" => "Padding_50px"
        );
        if ($this->currentObject == null || !$this->currentObject->isPreview()) 
        {
           $parameters["checked"] = "true";
        }
        $this->xml->addInputElement($parameters);
        
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string("settings_available", 'liveclassroom'), 
                        array ("for" => "accessAvailable_true"));
        
        $this->xml->createLine();
         
        $parameters = array (
            "type" => "button",
            "value" => get_string("settings_dial_in_informations",'liveclassroom'),
            "class" => "Padding_50px"
        );
        
        if ($this->currentObject==null || $this->currentObject->isArchive()) 
        {
           $parameters["disabled"] = "true";
        }
        else
        {
            $parameters["onclick"] = "showInformation('manageAction.php','".$this->prefix.$this->currentObject->getRoomId()."','liveclassroom');";
        }
        $this->xml->addInputElement($parameters);
        
        $this->xml->createLine();     
        
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string("settings_max_user", 'liveclassroom'),
                        array("class" => "Padding_50px"));
 
        $this->xml->createLine("subPart");
        
        $parameters = array (
            "type" => "radio",
            "name" => "userlimit",
            "value" => "false",
            "class" => "Padding_MaxUser",
            "onclick" => "toggleUserlimit(false)",
            "id" => "userlimit_false",
        );

        if ($this->isArchive == "true") {
           $parameters["disabled"] = "true";
        }
        
        if (($this->currentObject == null || $this->currentObject->getUserLimit() == -1) && $this->isArchive != "true") {
           $parameters["checked"] = "true";
        }
        
        $this->xml->addInputElement($parameters);
        
        $parameters = array ("for" => "userlimit_false");
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string("settings_max_user_unlimited", 'liveclassroom'), 
                        $parameters);
        
        $this->xml->createLine();
      
        $parameters = array (
            "type" => "radio",
            "name" => "userlimit",
            "value" => "true",
            "class" => "Padding_MaxUser",
            "onclick" => "toggleUserlimit(true)",
            "id" => "userlimit_true",   
        );
        if ($this->isArchive == "true") {
            $parameters["disabled"] = "true";
        }
        
        if (($this->currentObject != null && $this->currentObject->getUserLimit() != -1) && $this->isArchive != "true") {
            $parameters["checked"] = "true";
        }
        
        $this->xml->addInputElement($parameters);
        $parameters = array ("for" => "userlimit_true");
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string("settings_max_user_limited", 'liveclassroom'), 
                        $parameters);
        
        $parameters = array (
            "type" => "text",
            "name" => "userlimitValue",
            "id" => "userlimittext",
            "style" => "style"
        );
        
        if ($this->currentObject == null || $this->currentObject->getUserLimit() == -1 || $this->isArchive == "true") {
            $parameters["disabled"] = "true";
        }
        
        if (($this->currentObject != null && $this->currentObject->getUserLimit() != -1) && $this->isArchive != "true") {
            $parameters["value"] = $this->currentObject->getUserLimit();
        }
        
        $this->xml->addInputElement($parameters);
        
        $this->xml->createLine();
        
        $parameters = array (
            "type" => "checkbox",
            "name" => "guests",
            "id" => "guestAcess_value",
            "value" => "true",
            "class" => "Padding_50px",
            "onclick" => "$(\"launcher_link_row\").removeClassName(\"hideElement\")"
        );
        
        // If this is a new room the default value comes from the server setting
        if ($this->currentObject == null) {
            if ($this->config['guest_access']) {
                $parameters["checked"] = "true";
            }
        } else { //existing room, get value from room attribute
            if ($this->api->isGuestAuthorized($this->currentObject->getRoomId())) {
                $parameters["checked"] = "true";
            }
        }

        $this->xml->addInputElement($parameters);
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string("settings_enabled_guest", 'liveclassroom'));

        $this->xml->createLine();
         
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string("settings_roomId_guest", 'liveclassroom'),
                        array( "class" => "Padding_50px") );
                        
        $parameters = array (
            "type" => "text",
            "name" => "launcher_link",
            "id" => "launcher_link",
            "style" => "width:400px"
        );
        if ($this->currentObject != null) {
            $parameters["value"] = $this->currentObject->getGuestURL();
        } else {
            $parameters["value"] = '';
        }
        $this->xml->addInputElement($parameters);
         
        $parameters = array (
            "colspan" => 3,
            "id" => "launcher_link_row"
        );
        if ($this->currentObject == null || 
               ($this->currentObject != null && !$this->api->isGuestAuthorized($this->currentObject->getRoomId()))) {
            $style="hideElement";
        }
        
        $this->xml->createLine($style,"","launcher_link_row");
        
        $this->xml->addSimpleLineElement(
                       "span", 
                       get_string("settings_guest_access_comment1", 'liveclassroom'),
                       array("class"=>"TextComment"));

                       
        $this->xml->createLine("Padding_50px");
        
        $this->xml->addSimpleLineElement(
                       "span", 
                       get_string("settings_guest_access_comment2", 'liveclassroom'),
                       array("class"=>"TextComment"));

                       
        $this->xml->createLine("Padding_50px");
        
        if ($this->currentTab == "Access") {
           $this->xml->createPanelSettings(
                        get_string("tab_title_access", 'liveclassroom'), 
                        "block",
                        "Access",
                        "current",
                        "mainLecture",
                        "editSettings(\"generateXmlSettingsPanel.php\",\"all\")");
        } else {
           $this->xml->createPanelSettings(
                           get_string("tab_title_access", 'liveclassroom'), 
                           "none", 
                           "Access", 
                           "", 
                           "mainLecture", 
                           "editSettings(\"generateXmlSettingsPanel.php\",\"all\")");
        }
    }
    

 	/**
    * Add the necessary elements to the current xml object to render the panel features of the lc settings
    */  
    function createLcAudioSettingsPanel() {
    
            if ($this->currentObject != null && $this->currentObject->isArchive()) {
           
                $this->xml->addSimpleLineElement(
                            "label", 
                            get_string("settings_download_media", 'liveclassroom'),
                            array("class"=>"LargeLabel_Width TextRegular_Right"));
                $this->xml->addSimpleLineElement(
                            "img", 
                            "",
                            array("src"=>"lib/web/pictures/items/listitem-mp3.png",
                                  "border"=>"0",
                                  "width"=>"27px", 
                                  "height"=>"13px",
                                  "alt"=>"Download MP3",
                                  "title"=>"Download MP3",
                               	  "style"=>"vertical-align:middle"
                            ));
                $this->xml->addSimpleLineElement(
                            "link", 
                            "Download Archive in MP3 Format",
                            array("href"=>"javascript:downloadAudioFile('manageAction.php','getMp3Status','".$this->currentObject->getRoomId()."')",
                               	  "style"=>"padding-left:5px"
                            ));                            
                $this->xml->createLine();             
              
                $this->xml->addSimpleLineElement(
                            "img", 
                            "",
                            array("src"=>"lib/web/pictures/items/listitem-mp4.png",
                                  "border"=>"0",
                                  "width"=>"27px", 
                                  "height"=>"13px",
                                  "alt"=>"Download MP4",
                                  "title"=>"Download MP4",
                               	  "style"=>"vertical-align:middle",
                               	  "class"=>"AlignRight"
                            ));
               $this->xml->addSimpleLineElement(
                            "link", 
                            "Download Archive in MP4 Format",
                            array("href"=>"javascript:downloadAudioFile('manageAction.php','getMp4Status','".$this->currentObject->getRoomId()."')",
                               	  "style"=>"padding-left:5px"
                            ));                            
               $this->xml->createLine();   
  
            }
           
            
            $this->xml->addSimpleLineElement(
                            "label", 
                            get_string("settings_archive_availaibility", 'liveclassroom'),
                            array("class"=>"LargeLabel_Width TextRegular_Right"));
                            
            $parameters = array (
                "type" => "checkbox",
                "name" => "can_download_mp3",
                "value" => "true",
                "id" => "can_download_mp3"
            );
            if ($this->currentObject == null || $this->currentObject->isDownloadMP3Enabled()) {
                $parameters["checked"] = "true";
            }
            $this->xml->addInputElement($parameters);
			  
			$parameters = array ("for"=>"can_download_mp3");
            $this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_allow_download_mp3_".$this->resourceType, 'liveclassroom'),
                            $parameters);
            
            $this->xml->createLine();
 			
			$parameters = array (
                "type" => "checkbox",
                "name" => "can_download_mp4",
                "value" => "true",
                "id" => "can_download_mp4",
				"class" => "AlignRight"
            );
            if ($this->currentObject == null || $this->currentObject->isDownloadMP4Enabled()) {
                $parameters["checked"] = "true";
            }
            $this->xml->addInputElement($parameters);

			$parameters = array ("for"=>"can_download_mp4");
            $this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_allow_download_mp4_".$this->resourceType, 'liveclassroom'),
                            $parameters);
            
            $this->xml->createLine();            
			
			$parameters = array ("class" => "LargeLabel_Width TextRegular_Right Bold");
 			$this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_mp4Settings", 'liveclassroom'),
                            $parameters);
            
            $this->xml->createLine(); 
   			
			$parameters = array ("style" => "width:450px;display:block");
			$this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_mp4SettingsSetence_".$this->resourceType, 'liveclassroom'),
                            $parameters);
            
            $this->xml->createLine("CommentLabel"); 

			$parameters = array ("class" => "Bold");
			$this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_mp4SettingsComment", 'liveclassroom'),
                            $parameters);
            
            $this->xml->createLine("Padding_200px"); 
  
			$parameters = array (
            	"type" => "radio",
           		"value" => "content_focus_with_video",
            	"id" => "mp4_media_priority_content_focus_with_video",
           		"name" => "mp4_media_priority",
           		"onclick" => "doChangeMediaPriority()",
            	"class" => "AlignRight"
        	);
        	if ($this->currentObject == null || ($this->currentObject->getMp4MediaPriority() == "content_focus_no_video" 
											|| $this->currentObject->getMp4MediaPriority() == "content_focus_with_video")) 
        	{
           		$parameters["checked"] = "true";
        	}
        	$this->xml->addInputElement($parameters);
        	$parameters = array ("for"=>"mp4_media_priority_content_focus_with_video", "class" => "userlimit_true");
			$this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_mp4Settings_content_focus_no_video", 'liveclassroom'),$parameters);
            
            $this->xml->createLine(); 

		 	$parameters = array (
                "type" => "checkbox",
                "name" => "mp4_media_priority_content_include_video",
                "value" => "true",
                "id" => "mp4_media_priority_content_include_video",
                "class" => "SubOption grey"
            );
            
            if ($this->currentObject != null && $this->currentObject->getMp4MediaPriority() == "content_focus_no_video" ) 
            {
                $parameters["checked"] = "true";
                $parameters["class"] = "SubOption black";
            }
            if ($this->currentObject != null && $this->currentObject->getMp4MediaPriority() != "content_focus_no_video"  && $this->currentObject->getMp4MediaPriority() != "content_focus_with_video" ) 
            {
                $parameters["disabled"] = "true";
            }
			
			$this->xml->addInputElement($parameters);
			
			$parameters = array ("id"=>"mp4_media_priority_content_include_video_label", "for"=>"mp4_media_priority_video_focus", "class" => "userlimit_true");
			$this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_mp4Settings_doNotIncludeVideo", 'liveclassroom'),$parameters);
            
            $this->xml->createLine("Reduce_space");
			
			
			
			$parameters = array (
            	"type" => "radio",
           		"value" => "video_focus",
            	"id" => "mp4_media_priority_video_focus",
           		"name" => "mp4_media_priority",
           		"onclick" => "doChangeMediaPriority()",
            	"class" => "AlignRight"
        	);
        	if ($this->currentObject != null && $this->currentObject->getMp4MediaPriority() == "video_focus" ) 
        	{
           		$parameters["checked"] = "true";
        	}
        	$this->xml->addInputElement($parameters);
        	$parameters = array ("for"=>"mp4_media_priority_video_focus", "class" => "userlimit_true");
			$this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_mp4Settings_video_focus", 'liveclassroom'));
            
            $this->xml->createLine();

			$parameters = array("class"=>"Bold");
			$this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_mp4encodingOptions", 'liveclassroom'),
                            $parameters);
            
            $this->xml->createLine("Padding_200px"); 

			$parameters = array (
                "type" => "radio",
                "name" => "mp4_encoding_type",
                "value" => "streaming",
                "id" => "mp4_encoding_type_streaming",
			    "class"=>"AlignRight"
            );
          
            if ($this->currentObject != null && $this->currentObject->getMp4EncodingType() == "streaming" ) 
            {
                $parameters["checked"] = "true";
            }
			$this->xml->addInputElement($parameters);

 			$parameters = array ("class" => "userlimit_true");
			$this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_mp4encodingOptions_streaming", 'liveclassroom'));
            
            $this->xml->createLine(""); 

			$parameters = array (
                "type" => "radio",
                "name" => "mp4_encoding_type",
                "value" => "standard",
                "id" => "mp4_encoding_type_standard",
			    "class"=>"AlignRight"
            );
            
            if ($this->currentObject == null || $this->currentObject->getMp4EncodingType() == "standard" ) 
            {
                $parameters["checked"] = "true";
            }
			$this->xml->addInputElement($parameters);

 			$parameters = array ("class" => "userlimit_true");
			$this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_mp4encodingOptions_standard", 'liveclassroom'));
            
            $this->xml->createLine("ReduceSpace") 	;			
			
			$parameters = array (
                "type" => "radio",
                "name" => "mp4_encoding_type",
                "value" => "high_quality",
                "id" => "mp4_encoding_type_high",
			    "class"=>"AlignRight"
            );
            
            if ($this->currentObject != null && $this->currentObject->getMp4EncodingType() == "high_quality" ) 
            {
                $parameters["checked"] = "true";
            }
			$this->xml->addInputElement($parameters);

 			$parameters = array ("class" => "userlimit_true");
			$this->xml->addSimpleLineElement(
                            "label",
                            get_string("settings_mp4encodingOptions_highQuality", 'liveclassroom'));
            
            $this->xml->createLine("ReduceSpace");



		
        
 
            if ($this->currentTab == "MP3/MP4") 
            {
                $this->xml->createPanelSettings(
                                get_string("tab_title_archive_settings", 'liveclassroom'),
                                "block",
                                "MP3/MP4",
                                "current",
                                "mainLecture-discussion",
                                "editSettings(\"generateXmlSettingsPanel.php\",\"all\")");
            }
            else 
            {
                $this->xml->createPanelSettings(
                                get_string("tab_title_archive_settings", 'liveclassroom'),
                                "none",
                                "MP3/MP4",
                                "",
                                "mainLecture-discussion",
                                "editSettings(\"generateXmlSettingsPanel.php\",\"all\")");
            }
        
    }
    

    /**
    * Add the necessary elements to the current xml object to render the panel Advanced of the lc settings
    */  
    function createLcAdvancedPanel() {
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string("settings_advanced_comment_1", 'liveclassroom'));
       
        $this->xml->createLine();
        
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string("settings_advanced_comment_2", 'liveclassroom'));
       
        $this->xml->createLine();
        
        $this->xml->addSimpleLineElement("space");
        $this->xml->createLine();
        
        $parameters = array (
            "type" => "button",
            "value" => get_string("settings_advanced_room_settings_button",'liveclassroom'),
            "onclick" => "openRoomSettings('manageAction.php')"
        );
        $this->xml->addInputElement($parameters);
    
        $this->xml->createLine();
        
        $parameters = array (
            "type" => "button",
            "value" => get_string("settings_advanced_media_settings_button",'liveclassroom'),
            "onclick" => "openMediaSettings('manageAction.php')"
        );
        $this->xml->addInputElement($parameters);
       
        $this->xml->createLine();
        
        $this->xml->createPanelSettings(
                        get_string("tab_title_advanced", 'liveclassroom'),
                        "none", 
                        "Advanced", 
                        "", 
                        "all", 
                        "saveSettings(\"manageAction.php\",\"".$this->currentObject->getRoomId()."\")");
                        
        $this->xml->createAdvancedPopup(
                        get_string("advancedPopup_title", 'liveclassroom'), 
                        get_string("advancedPopup_sentence", 'liveclassroom'));
    }
    
    
    
/***********************************
*
*           Voice Tools
* 
* ***********************************/
    
    /**
    * Add the necessary elements to the current xml object to render the first part of the panel Info
    */ 
    function createGeneralInfoPanelStart() {
    
        $this->xml->addCustomLineElement(
                        "*",
                        "required", 
                        get_string('title', 'voicepodcaster'),
                        array ("class" => "LargeLabel_Width TextRegular_Right"));
        
        $parameters = array (
            "id" => "longname",
            "maxlength" => "50",
            "name" => "longname",
            "type" => "text"
        );
        if (isset ($this->currentObject)) 
        {
            $parameters["value"] = $this->currentObject->getTitle();
        }
        else 
        {
            $parameters["value"] = "";
        }
        $this->xml->addInputElement($parameters);
        
        $this->xml->addSimpleLineElement(
                        "label", 
                        "*", 
                        array ("class" => "required"));
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string("settings_required", 'voicepodcaster'));

        $this->xml->createLine();
        
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string('description', 'voicepodcaster'),
                         array ("class" => "LargeLabel_Width TextRegular_Right"));
        
        $parameters = array (
            "name" => "description",
            "id" => "description",
            "rows" => "4",
            "cols" => "30"
        );
        
        $display = "";
        if (isset ($this->currentObject)) 
        {
            $display = $this->currentObject->getDescription();
        }
        $this->xml->addTextAreaElement($parameters,$display);
        
        $this->xml->createLine();
    }
     

    /**
    * Add the necessary elements to the current xml object to render the second part of the panel Info (voice board)
    */ 
    function createVBInfoPanel() {
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string('type', 'voicepodcaster'),
                        array("class"=>"LargeLabel_Width TextRegular_Right"));
        
        $parameters = array (
            "type" => "radio",
            "value" => "student",
            "id" => "led_student",
            "name" => "led",
            "onclick" => "managePublicState(\"show_compose\",\"public\")"
        );
        if (!isset ($this->currentObject) || $this->currentObjectOptions->getFilter() == "false") 
        {
           $parameters['checked'] = "true";
        }
        $this->xml->addInputElement($parameters);
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string('public', 'voicepodcaster'), 
                        array ("for" => "led_student","class" => "top"));
        $this->xml->addSimpleLineElement("br", NULL);
        $this->xml->addSimpleLineElement(
                        "span", 
                        get_string('public_comment', 'voicepodcaster'), 
                        array ("class" => "TextComment AlignRight"));
        
        $this->xml->createLine();
             
        $parameters = array (
            "type" => "checkbox",
            "value" => "true",
            "id" => "show_compose",
            "name" => "show_compose",
            "class" =>"AlignRight"
        );
        if (!isset ($this->currentObject)) 
        {
            $parameters['checked'] = "true";
        }
        else 
        {
           if ($this->currentObjectOptions->getFilter() == "false") 
           {
               if ($this->currentObjectOptions->getShowcompose() == "true") 
               {
                   $parameters['checked'] = "true";
               }
            }
            else 
            {   
               $parameters['disabled'] = "true";
            }
        }
        $this->xml->addInputElement($parameters);
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string('start_thread', 'voicepodcaster'));
        
        $this->xml->createLine("subOption");
       
        $parameters = array (
            "type" => "radio",
            "value" => "instructor",
            "id" => "led_instructor",
            "name" => "led",
            "onclick" => "managePublicState(\"show_compose\",\"private\")",
            "class" => "AlignRight"
        );
        if (isset ($this->currentObject) && $this->currentObjectOptions->getFilter() == "true") 
        {
           $parameters["checked"] = "true";
        }
        $this->xml->addInputElement($parameters);
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string('private', 'voicepodcaster'), 
                        array ("for" => "led_instructor","valign" => "top"));
        $this->xml->addSimpleLineElement("br", NULL);
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string('private_comment', 'voicepodcaster'), 
                        array ("class" => "TextComment AlignRight"));
        
        $this->xml->createLine();
        
        $this->xml->createPanelSettings(
                        get_string('tab_title_Info', 'voicepodcaster'), 
                        "block", 
                        "Info", 
                        "current", 
                        "all");
    }
    
    /**
    * Add the necessary elements to the current xml object to render the second part of the panel Info (voice presentation)
    */ 
    function createVPInfoPanel() {
        $this->xml->addSimpleLineElement("space");
        
        $parameters = array (
            "type" => "checkbox",
            "value" => "true",
            "id" => "show_reply",
            "name" => "show_reply",
            "class" => "AlignRight"
        );
        if (!isset ($this->currentObjectOptions) || $this->currentObjectOptions->getShowReply() == "true") 
        {
            $parameters['checked'] = "true";
        }
        $this->xml->addInputElement($parameters);
        $this->xml->addSimpleLineElement("label", get_string('comment_slide', 'voicepodcaster'));
       
        $this->xml->createLine();
        
        $this->xml->addSimpleLineElement("space");
       
        $parameters = array (
            "type" => "checkbox",
            "value" => "true",
            "id" => "filter",
            "name" => "filter",
            "class" => "AlignRight"
        );
        if (!isset ($this->currentObject) || $this->currentObjectOptions->getFilter() == "true") 
        {
            $parameters['checked'] = "true";
        }
        $this->xml->addInputElement($parameters);
        $this->xml->addSimpleLineElement("label", get_string('private_slide', 'voicepodcaster'));
        $this->xml->createLine();

        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string('private_slide_comment', 'voicepodcaster'), 
                        array ("class" => "AlignRight TextComment"));
        $this->xml->createLine();
  
        
        $this->xml->createPanelSettings(
                        get_string("tab_title_Info", 'voicepodcaster'), 
                        "block", 
                        "Info", 
                        "current", 
                        "all");
    }

     /**
    * Add the necessary elements to the current xml object to render the second part of the panel Info (podcaster)
    */    
    function createPCInfoPanel() {
        $this->xml->addSimpleLineElement("space");
       
        $parameters = array (
            "type" => "checkbox",
            "value" => "true",
            "id" => "show_compose",
            "name" => "show_compose",
            "class" => "AlignRight"
        );
        if (!isset ($this->currentObject) || $this->currentObjectOptions->getShowCompose() == "true") 
        {
            $parameters['checked'] = "true";
        }
        $this->xml->addInputElement($parameters);
        $this->xml->addSimpleLineElement("label", 
                                         get_string('post_podcast', 'voicepodcaster'));
        
        $this->xml->createLine();
        
        $this->xml->createPanelSettings(
                        get_string("tab_title_Info", 'voicepodcaster'), 
                        "block", 
                        "Info", 
                        "current", 
                        "all");
    }
    
    /**
    * Add the necessary elements to the current xml object to render the panel Media (voice presentation and voice board)
    */ 
    function createVBVPMediaPanel() {
        $this->xml->addSimpleLineElement("label",
                                         get_string('audioquality', 'voicepodcaster'),
                                         array("class"=>"LargeLabel_Width TextRegular_Right"));
        $this->xml->createOptionElement(
                        "audio_format", 
                        "audio_format", 
                        $this->createOptionAudioSettings());
     
        $this->xml->createLine();
        
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string("settings_max_message", 'voicepodcaster'),
                        array("class"=>"LargeLabel_Width TextRegular_Right"));

        $this->xml->createOptionElement(
                        "max_length", 
                        "max_length", 
                        $this->createOptionMaxLength());
       
        $this->xml->createLine();
        
        $this->xml->createPanelSettings(
                        get_string('tab_title_media', 'voicepodcaster'), 
                        "none", 
                        "Media", 
                        "", 
                        "all");
    }
    
    /**
    * Add the necessary elements to the current xml object to render the panel Media (podcaster)
    */ 
    function createPCMediaPanel() {
        $this->xml->addSimpleLineElement(
                        "label",
                        get_string("settings_audio", 'voicepodcaster'),
                        array("class"=>"LargeLabel_Width TextRegular_Right"));

        $this->xml->createOptionElement(
                        "audio_format", 
                        "audio_format", 
                        $this->createOptionAudioSettings());
       
        $this->xml->createLine();
        
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string("settings_auto_publish_podcast", 'voicepodcaster'),
                        array("class"=>"LargeLabel_Width TextRegular_Right"));
      
        $this->xml->createOptionElement(
                        "delay", 
                        "delay", 
                        $this->createOptionDelay());
        
        $this->xml->createLine();
        
        $this->xml->createPanelSettings(
                        get_string("tab_title_media", 'voicepodcaster'), 
                        "none", 
                        "Media", 
                        "", 
                        "all");
    }    
      /**
    * Add the necessary elements to the current xml object to render the panel Features (voice board)
    */     
    function createVBFeaturesPanel() {
        global $DB;
        $parameters = array (
            "type" => "checkbox",
            "value" => "true",
            "id" => "short_title",
            "name" => "short_title",
            "class" => "Padding_50px"
        );
        if (!isset ($this->currentObject) || $this->currentObjectOptions->getShortTitle() == 'true') 
        {
            $parameters['checked'] = "true";
        }
        $this->xml->addInputElement($parameters);
        $this->xml->addSimpleLineElement("label", get_string('short_message', 'voicepodcaster'));
        
        $this->xml->createLine();
        
        $parameters = array (
            "type" => "checkbox",
            "value" => "true",
            "id" => "chrono_order",
            "name" => "chrono_order",
            "class" => "Padding_50px"
        );
        if (isset ($this->currentObject) && $this->currentObjectOptions->getChronoOrder() == 'true') 
        {
            $parameters['checked'] = "true";
        }
        $this->xml->addInputElement($parameters);
        $this->xml->addSimpleLineElement("label", get_string('chrono_order', 'voicepodcaster'));
        
        $this->xml->createLine();
        
        $parameters = array (
            "type" => "checkbox",
            "value" => "true",
            "id" => "show_forward",
            "name" => "show_forward",
            "class" => "Padding_50px"
        );
        if (isset ($this->currentObject) && $this->currentObjectOptions->getShowForward() == 'true') 
        {
            $parameters['checked'] = "true";
        }
        $this->xml->addInputElement($parameters);
        $this->xml->addSimpleLineElement("label", get_string('show_forward', 'voicepodcaster'));
        
        $this->xml->createLine("Padding_top_25px");
        
        $parameters = array (
            "type" => "checkbox",
            "value" => "true",
            "id" => "show_reply",
            "name" => "show_reply",
            "class" => "Padding_50px"
        );
        if (!isset ($this->currentObject) || $this->currentObjectOptions->getShowReply() == 'true') 
        {
            $parameters['checked'] = "true";
        }
        $this->xml->addInputElement($parameters);
        $this->xml->addSimpleLineElement("label", get_string('settings_show_reply', 'voicepodcaster'));
        
        $this->xml->createLine();
        if(isGradebookAvailable()){
          $disabledPointsPossible = false;
          $parameters = array (
              "type" => "checkbox",
              "value" => "true",
              "id" => "grade",
              "name" => "grade",
              "onclick" => "managePointsPossible()",
              "class" => "Padding_50px"
          );
          if (isset ($this->currentObject) && $this->currentObjectOptions->getGrade() == 'true') 
          {
              $parameters['checked'] = "true";
          }else
          {
              
              $disabledPointsPossible=true;
          }
          if(isset ($this->currentObject)){
            $activities = $DB->get_records("voicepodcaster", array("rid" => $this->currentObject->getRid()));
            if(empty($activities)){
             $parameters['disabled'] = "true";
            }
          }else{
             $parameters['disabled'] = "true";
          }
          $this->xml->addInputElement($parameters);
          $this->xml->addSimpleLineElement("label", get_string('grade_settings', 'voicepodcaster'));
          
          $this->xml->createLine("Padding_top_25px");
          
          $this->xml->addSimpleLineElement(
                          "label", 
                          get_string('points_possible', 'voicepodcaster'), 
                          array ("for" => "points_possible","class"=>"Padding_50px"));
                          
           $parameters = array (
              "type" => "text",
              "id" => "points_possible",
              "name" => "points_possible",
          );
          
          if (isset ($this->currentObject)) 
          {
              $parameters['value'] = $this->currentObjectOptions->getPointsPossible();
          }
          if($disabledPointsPossible === true){
              $parameters['disabled'] = true;
          }
          
          $this->xml->addInputElement($parameters);
          
          $this->xml->createLine();
        }
        $this->xml->createPanelSettings(
                        get_string('tab_title_features', 'voicepodcaster'),
                        "none", 
                        "Features", 
                        "",       
                        "all");
    }    

      /**
    * Add the necessary elements to the current xml object to render the panel Features (podcaster)
    */     
    function createPCFeaturesPanel() {
        $parameters = array (
            "type" => "checkbox",
            "value" => "true",
            "id" => "short_title",
            "name" => "short_title",
            "class" => "Padding_50px"
        );
        if (!isset ($this->currentObject) || $this->currentObjectOptions->getShortTitle() == 'true') 
        {
           $parameters['checked'] = "true";
        }
        $this->xml->addInputElement($parameters);
        $this->xml->addSimpleLineElement("label", get_string('short_message', 'voicepodcaster'));
        
        $this->xml->createLine();
        
        $this->xml->createPanelSettings(
                        get_string("tab_title_features", 'voicepodcaster'),
                        "none",
                        "Features",
                        "",
                        "all");
    }
    
    /**
    * Add the necessary elements to the current xml object to render the panel Access
    */
    function createVTAccessPanel() {
        $parameters = array (
            "type" => "checkbox",
            "value" => "true",
            "id" => "accessAvailable",
            "name" => "accessAvailable",
            "onclick" => "javascript:manageAvailibility()",
            "class" => "Padding_50px"
        );
        
        if (!isset ($this->currentObjectInformations) || $this->currentObjectInformations->availability == "1")
        {
            $parameters['checked'] = "true";
        }
        $this->xml->addInputElement($parameters);
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string('available', 'voicepodcaster'), 
                        array ("for" => "acessAvailable"));
        
        $this->xml->createLine();
        
        $this->xml->addSimpleLineElement("space");
        $this->xml->createLine();
        
        $parameters = array (
            "type" => "checkbox",
            "value" => "true",
            "id" => "start_date",
            "name" => "start_date",
            "onclick" => "javascript:manageAvailibilityDate(\"start\")",
            "class" => "Padding_50px"
        );
        if (isset ($this->currentObjectInformations) && $this->currentObjectInformations->availability == "0") 
        {
            $parameters['disabled'] = "true";
        }
        else
        {
            if (isset ($this->currentObjectInformations) && $this->currentObjectInformations->start_date != "-1") 
            {
                $parameters['checked'] = "true";
            }
        }
        $this->xml->addInputElement($parameters);
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string('start_date', 'voicepodcaster'), 
                        array ("for" => "start_date"));
        
        $this->xml->createLine();
       
        if ($this->currentObjectInformations != null) 
        {
            $optionsMonth = $this->createSelectMonth(
                                        $this->startSelect, 
                                        date('m', $this->currentObjectInformations->start_date));
        }
        else
        {
            $optionsMonth = $this->createSelectMonth();
        }
        if ($this->startSelect === false || $this->currentObject == null) 
        {
            $this->xml->createOptionElement(
                            "start_month", 
                            "start_month_field", 
                            $optionsMonth, 
                            "true",
                            "Padding_50px");
        }
        else
        {
            $this->xml->createOptionElement(
                            "start_month", 
                            "start_month_field", 
                            $optionsMonth);
        }
       
        if ($this->currentObjectInformations != null)
        {
            $optionsDay = $this->createSelectDay(
                                    $this->startSelect, 
                                    date('d', $this->currentObjectInformations->start_date));
        }
        else
        {
            $optionsDay = $this->createSelectDay();
        }
        if ($this->startSelect === false || $this->currentObject == null)
        {
            $this->xml->createOptionElement(
                            "start_day", 
                            "start_day_field", 
                            $optionsDay, 
                            "true");
        }
        else
        {
            $this->xml->createOptionElement(
                            "start_day", 
                            "start_day_field",  
                            $optionsDay);
        }
    
        if ($this->currentObjectInformations != null)
        {
            $optionsYear = $this->createSelectYear(
                                    $this->startSelect, 
                                    date('Y', $this->currentObjectInformations->start_date));
        }
        else
        {
            $optionsYear = $this->createSelectYear();
        }
        if ($this->startSelect === false || $this->currentObject == null)
        {
            $this->xml->createOptionElement(
                            "start_year", 
                            "start_year_field", 
                            $optionsYear, 
                            "true");
        }
        else
        {
            $this->xml->createOptionElement(
                            "start_year", 
                            "start_year_field", 
                            $optionsYear);
        }

        if ($this->currentObjectInformations != null)
        {
            $optionsHour = $this->createSelectHour(
                                    $this->startSelect, 
                                    date('G', $this->currentObjectInformations->start_date));
        }
        else
        {
            $optionsHour = $this->createSelectHour();
        }
        if ($this->startSelect === false || $this->currentObject == null)
        {
            $this->xml->createOptionElement(
                            "start_hr", 
                            "start_hr_field", 
                            $optionsHour, 
                            "true",
                            "Padding_50px");
        }
        else
        {
            $this->xml->createOptionElement(
                            "start_hr", 
                            "start_hr_field", 
                            $optionsHour);
        }
      
        if ($this->currentObjectInformations != null)
        {
            $optionsMinute = $this->createSelectMin(
                                        $this->startSelect, 
                                        date('i', $this->currentObjectInformations->start_date));
        }
        else
        {
            $optionsMinute = $this->createSelectMin();
        }
        if ($this->startSelect === false || $this->currentObject == null)
        {   
            $this->xml->createOptionElement(
                            "start_min", 
                            "start_min_field", 
                            $optionsMinute, 
                            "true");
        }
        else
        {
            $this->xml->createOptionElement(
                            "start_min", 
                            "start_min_field", 
                            $optionsMinute);
        }
        
        $this->xml->createLine();

        $parameters = array (
            "type" => "checkbox",
            "value" => "true",
            "id" => "end_date",
            "name" => "end_date",
            "onclick" => "javascript:manageAvailibilityDate(\"end\")",
            "class" => "Padding_50px"
        );
        
        if (isset ($this->currentObjectInformations) && $this->currentObjectInformations->availability == "0") 
        {
            $parameters['disabled'] = "true";
        }
        else 
        {
            if (isset ($this->currentObjectInformations) && $this->currentObjectInformations->end_date != "-1") {
                $parameters['checked'] = true;
            }
        }
        $this->xml->addInputElement($parameters);
        $this->xml->addSimpleLineElement(
                        "label", 
                        get_string('end_date', 'voicepodcaster'), 
                        array ("for" => "end_date"));     
     
        $this->xml->createLine();
        
        if ($this->currentObjectInformations != null) 
        {
            $optionsMonth = $this->createSelectMonth(
                                        $this->endSelect, 
                                        date('m', $this->currentObjectInformations->end_date));
        }
        else
        {
            $optionsMonth = $this->createSelectMonth();
        }
        if ($this->endSelect === false || $this->currentObject == null) 
        {
            $this->xml->createOptionElement(
                            "end_month", 
                            "end_month_field", 
                            $optionsMonth, 
                            "true",
                            "Padding_50px");
        }
        else
        {
            $this->xml->createOptionElement(
                            "end_month", 
                            "end_month_field", 
                            $optionsMonth,"false",$style);
        }
       
        if ($this->currentObjectInformations != null)
        {
            $optionsDay = $this->createSelectDay(
                                    $this->endSelect, 
                                    date('d', $this->currentObjectInformations->end_date));
        }
        else
        {
            $optionsDay = $this->createSelectDay();
        }
        if ($this->endSelect === false || $this->currentObject == null)
        {
            $this->xml->createOptionElement(
                            "end_day", 
                            "end_day_field", 
                            $optionsDay, 
                            "true");
        }
        else
        {
            $this->xml->createOptionElement(
                            "end_day", 
                            "end_day_field", 
                            $optionsDay);
                                        
        }
        
         if ($this->currentObjectInformations != null)
        {
            $optionsYear = $this->createSelectYear(
                                    $this->endSelect, 
                                    date('Y', $this->currentObjectInformations->end_date));
        }
        else
        {
            $optionsYear = $this->createSelectYear();
        }
        if ($this->endSelect === false || $this->currentObject == null)
        {
            $this->xml->createOptionElement(
                            "end_year", 
                            "end_year_field", 
                            $optionsYear, 
                            "true");
        }
        else
        {   
            $this->xml->createOptionElement(
                            "end_year", 
                            "end_year_field", 
                            $optionsYear);
                                
        }
      
         if ($this->currentObjectInformations != null)
        {
            $optionsHour = $this->createSelectHour(
                                    $this->endSelect, 
                                    date('G', $this->currentObjectInformations->end_date));
        }
        else
        {
            $optionsHour = $this->createSelectHour();
        }
        if ($this->endSelect === false || $this->currentObject == null) 
        {
            $this->xml->createOptionElement(
                            "end_hr", 
                            "end_hr_field", 
                            $optionsHour, 
                            "true",
                            "Padding_50px");
        }
        else
        {
            $this->xml->createOptionElement(
                            "end_hr", 
                            "end_hr_field", 
                             $optionsHour);
        }
        
        if ($this->currentObjectInformations != null)
        {
            $optionsMinute = $this->createSelectMin(
                                        $this->endSelect, 
                                        date('i', $this->currentObjectInformations->end_date));
        }
        else
        {
            $optionsMinute = $this->createSelectMin();
        }
        
        if ($this->endSelect === false || $this->currentObject == null)
        {
            $this->xml->createOptionElement(
                            "end_min", 
                            "end_min_field", 
                            $optionsMinute, 
                            "true");
        }
        else
        {
            $this->xml->createOptionElement(
                            "end_min", 
                            "end_min_field", 
                           $optionsMinute);
        }
        
        $this->xml->createLine();
        
        $this->xml->createPanelSettings(
                        get_string('access', 'voicepodcaster'), 
                        "none", 
                        "Access", 
                        "", 
                        "all");
    }

    /*
    * Fill an array with the different audio options
    */    
    function createOptionAudioSettings() {
        $option = array (
            "value" => "spx_8_q3",
            "display" => get_string('basicquality','voicepodcaster')
        );
        if (isset ($this->currentObject) && $this->currentObjectAudioFormat->getName() == "spx_8_q3") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        $option = array (
            "value" => "spx_16_q4",
            "display" => get_string('standardquality','voicepodcaster')
        );
        if (!isset ($this->currentObject) || $this->currentObjectAudioFormat->getName() == "spx_16_q4") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        $option = array (
            "value" => "spx_16_q6",
            "display" => get_string('goodquality','voicepodcaster')
        );
        if (isset ($this->currentObject) && $this->currentObjectAudioFormat->getName() == "spx_16_q6") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        $option = array (
            "value" => "spx_32_q8",
            "display" => get_string('superiorquality','voicepodcaster')
        );
        if (isset ($this->currentObject) && $this->currentObjectAudioFormat->getName() == "spx_32_q8") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        return $options;
    }
    
    /*
     * Fill an array with the different delay options
     */
    function createOptionDelay() {
        $options = array ();
        $option = array (
            "value" => "-1",
            "display" => "0 s"
        );
        if (isset ($this->currentObject) && $this->currentObjectOptions->getDelay() == "-1") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        $option = array (
            "value" => "60000",
            "display" => "1 min"
        );
        if (isset ($this->currentObject) && $this->currentObjectOptions->getDelay() == "60000") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        $option = array (
            "value" => "120000",
            "display" => "2 min"
        );
        if (isset ($this->currentObject) && $this->currentObjectOptions->getDelay() == "120000") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        $option = array (
            "value" => "180000",
            "display" => " 3 min"
        );
        if (isset ($this->currentObject) && $this->currentObjectOptions->getDelay() == "180000") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        $option = array (
            "value" => "300000",
            "display" => "5 min"
        );
        if (!isset ($this->currentObject) || $this->currentObjectOptions->getDelay() == "300000") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        $option = array (
            "value" => "600000",
            "display" => "10 min"
        );
        if (isset ($this->currentObject) && $this->currentObjectOptions->getDelay() == "600000") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        $option = array (
            "value" => "1200000",
            "display" => "20 min"
        );
        if (isset ($this->currentObject) && $this->currentObjectOptions->getDelay() == "1200000") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        $option = array (
            "value" => "1800000",
            "display" => "30 min"
        );
        if (isset ($this->currentObject) && $this->currentObjectOptions->getDelay() == "1800000") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        $option = array (
            "value" => "3600000",
            "display" => "60 min"
        );
        if (isset ($this->currentObject) && $this->currentObjectOptions->getDelay() == "3600000") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        return $options;
    }

    /*
     * Return an array with the different max length options
     * We use this array to generate the xml of the corresponding drop-down list
     */    
    function createOptionMaxLength() {
        $options = array ();
        $option = array (
        "value" => "15",
        "display" => "15 s"
        );
        if (isset ($this->currentObject) && $this->currentObjectOptions->getMaxLength() == "15") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        $option = array (
        "value" => "30",
        "display" => "30 s"
        );
        if (isset ($this->currentObject) && $this->currentObjectOptions->getMaxLength() == "30") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        $option = array (
        "value" => "60",
        "display" => "1 min"
        );
        if (isset ($this->currentObject) && $this->currentObjectOptions->getMaxLength() == "60") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        $option = array (
        "value" => "180",
        "display" => "3 min"
        );
        if (isset ($this->currentObject) && $this->currentObjectOptions->getMaxLength() == "120") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        $option = array (
        "value" => "300",
        "display" => "5 min"
        );
        if (!isset ($this->currentObject) || $this->currentObjectOptions->getMaxLength() == "300") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        $option = array (
        "value" => "600",
        "display" => "10 min"
        );
        if (isset ($this->currentObject) && $this->currentObjectOptions->getMaxLength() == "600") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        $option = array (
        "value" => "1200",
        "display" => "20 min"
        );
        if (isset ($this->currentObject) && $this->currentObjectOptions->getMaxLength() == "1200") {
            $option["selected"] = "true";
        }
        $options[] = $option;
        
        return $options;
    }
    
    /* Return an array with 
     * We use this array to generate the xml of the corresponding drop-down list
     * @param selected : 
     * @param year : 
     */    
    function createSelectYear($selected = false, $year = NULL) {
        $options = array ();
        $option = array (
            "value" => "0",
            "selected" => "true",
            "display" => "--"
        );
        $options[] = $option;
        for ($i = date("Y"); $i <= date("Y") + 10; $i++) {
            $option = array (
                "value" => $i,
                "display" => $i
            );
            
            if (isset ($this->currentObject) && $selected == true && $year == $i) {
                $option['selected'] = "true";
            }
            $options[] = $option;
        }
        return $options;
    }
    
    function createSelectMonth($selected = false, $month = NULL) {
        $options = array ();
        $option = array (
            "value" => "0",
            "selected" => "true",
            "display" => "--"
        );
        $options[] = $option;
        for ($i = 1; $i <= 12; $i++) {
            $option = array (
                "value" => $i,
                "display" => get_string("month" . $i,
                "voicepodcaster"
            ));
            if (isset ($this->currentObject) && $selected == true && $month == $i) {
              $option['selected'] = "true";
            }
            $options[] = $option;
        }
        return $options;
    }
    
    function createSelectDay($selected = false, $day = NULL) {
        $options = array ();
        $option = array (
            "value" => "0",
            "selected" => "true",
            "display" => "--"
        );
        $options[] = $option;
        for ($i = 1; $i <= 31; $i++) {
            $option = array (
                "value" => $i,
                "display" => $i
            );
            if (isset ($this->currentObject) && $selected == true && $day == $i) {
               $option['selected'] = "true";
            }
            $options[] = $option;
        }
        return $options;
    }
    
    function createSelectHour($selected = false, $hr = NULL) {
        $options = array ();
        $option = array (
                    "value" => "0",
                    "selected" => "true",
                    "display" => "--"
        );
        $options[] = $option;
        for ($i = 1; $i < 25; $i++) {
            $option = array (
                "value" => $i,
                "display" => date("h A",mktime($i-1,1,1,1,1,2007))
            );
            if (isset ($this->currentObject) && $selected == true && $hr == $i ) {
              $option['selected'] = "true";
            }
            $options[] = $option;
        }
        return $options;
    }
    
 
    function createSelectMin($selected = false, $min = NULL) {
        $options = array ();
        $option = array (
            "value" => "0",
            "selected" => "true",
            "display" => "--"
        );
        $options[] = $option;
        for ($i = 1; $i < 61; $i = $i +5) {
            $option = array (
                "value" => $i,
                "display" => $i-1
            );
            if (isset ($this->currentObject) && $selected == true && $min == $i) {
                $option['selected'] = "true";
            }
            $options[] = $option;
        }
        return $options;
    }
    
    /*
     *  List all the rooms and their archives associed + the orphaned archives list
     *
     * 
     **/
    function getListLiveClassroom() {
            
        $liveclassroom = array ();
        $archives = array ();
        $rooms = $this->api->getRooms($this->session->getcourseId() . "_T");
        if ($rooms === false) 
        {
            return false;
        }

        foreach ($rooms as $room) 
        {
            $id = $room->getRoomId();
            $name = $room->getLongname();
            $preview = $room->isPreview();
            if ($room->isArchive()) 
            {
                $canDownloadMp3 = $room->isDownloadMP3Enabled();
                $canDownloadMp4 = $room->isDownloadMP4Enabled();
                $archive = new XmlArchive(
                                    $id, 
                                    $name, 
                                    $preview, 
                                    $canDownloadMp3,
                                    $canDownloadMp4,
                                    "manageAction.php", 
                                    $this->session->url_params . "&time=" . time() . "&action=launch");     
                                            
                $archive->setTooltipAvailability(get_string("tooltipLC_".$preview."_student","liveclassroom"));
                $archive->setTooltipDial(get_string("tooltip_dial","liveclassroom"));
                list ($roomId, $other) = split('_', $id);
                $archive->setParent($roomId);
                $archives[$roomId][] = $archive;
            }
            else 
            {
            
                if ($this->session->isInstructor() || !$this->session->isInstructor()  && $preview == 0) 
                {
                    $xmlRoom = new XmlRoom(
                                        $id, 
                                        $name, 
                                        true, 
                                        $preview, 
                                        null, 
                                        "manageAction.php", 
                                        $this->session->url_params . "&time=" . time() . "&action=launch");
                                        
                    $xmlRoom->setTooltipAvailability(get_string("tooltipLC_".$preview."_student","liveclassroom"));
                    $xmlRoom->setTooltipDial(get_string("tooltip_dial","liveclassroom"));
                    $liveclassroom[$id] = $xmlRoom;
                }
            }
        }
        
        foreach ($archives as $key => $value) {
        
            if (array_key_exists($key, $liveclassroom)) 
            {
                if ($this->session->isInstructor()) 
                {
                
                    $liveclassroom[$key]->setArchive($archives[$key]);
                    $listArchives = $archives[$key];
                    for ($i = 0; $i < count($listArchives); $i++) 
                    {
                        if ($listArchives[$i]->getAvailability() == "available" 
                            && $liveclassroom[$key]->getAvailability() == "unavailable") 
                        {
                            $orphaned = new XmlOrphanedArchive($listArchives[$i], "student");
                            $liveclassroom[$orphaned->getId()] = $orphaned;
                        }
                    }
                }
                else 
                {
                    $listArchives = $archives[$key];
                    for ($i = 0; $i < count($listArchives); $i++) 
                    {
                        if ($listArchives[$i]->getAvailability() == "available") 
                        {
                            $liveclassroom[$key]->AddOneArchive($listArchives[$i]);
                        }
                    }
                }
            }
            else 
            {
                $listArchives = $archives[$key];             
                for ($i = 0; $i < count($listArchives); $i++) 
                {
                
                    if ($this->session->isInstructor() || 
                        (!$this->session->isInstructor() && $listArchives[$i]->getAvailability() == "available")) 
                    {
                        $orphaned = new XmlOrphanedArchive($listArchives[$i], "");
                        $liveclassroom[$orphaned->getId()] = $orphaned;
                    }
                }
            }
        }
        return $liveclassroom;
    }
    
     /**
    * 
    * 
    */   
    function getListVoiceTools() {

        $resources = array ();
        $list = voicepodcaster_get_voicetools_list($this->session->hparams["course_id"]);
    
        if( $list != false)
        {
            $vtResources = voicetools_api_get_resources($list["rid"]);
            if ( $vtResources === false ) // problem
            {
                wimba_add_log(WIMBA_ERROR,voicepodcaster_LOGS,"Problem to get the list of resources from the voicepodcaster_LOGS server"); 
                return false;
            }
            else
            {   $ressources = $vtResources->getResources();
        
            }
        }
        else//error to get the database content
        {
            wimba_add_log(WIMBA_ERROR,voicepodcaster_LOGS,"Problem to get the list of resources from the databse");   
            return false;    
        }
         
        for ($i = 0; $i < count($ressources); $i++) 
        {
            $resource = $vtResources->getResource($i);
            $grade = -1;
            
            $rid = $resource->getRid();
            if($list["info"][$rid]->gradeid != -1)
            {
                $grade = $list["info"][$rid]->gradeid;
            }
            if ($list["info"][$rid]->availability == "0") 
            {
                $preview = false;
            }
            elseif ($list["info"][$rid]->start_date == -1 && $list["info"][$rid]->end_date == -1)
            {
                $preview = true;
            }
            elseif ($list["info"][$rid]->start_date == -1 && time() <= $list["info"][$rid]->end_date) 
            {
                $preview = true;
            }
            elseif ($list["info"][$rid]->start_date < time() && $list["info"][$rid]->end_date == -1) 
            {
                $preview = true;
            }
            elseif ($list["info"][$rid]->start_date < time() && time() < $list["info"][$rid]->end_date) 
            {
                $preview = true;
            }
            else 
            {
                $preview = false;
            }
         
            $xmlResource = new XmlResource(
                                    $rid, 
                                    $resource->getTitle(), 
                                    $preview, 
                                    "manageAction.php", 
                                    $this->session->url_params . "&time=" . time() . "&action=launch",
                                    $grade
                                    );
                                    
            $xmlResource->setTooltipAvailability(get_string("tooltipVT_" . $preview . "_student", 'voicepodcaster'));
            if ($this->session->isInstructor() || !$this->session->isInstructor() && $preview) 
            {
                $xmlResource->setType($resource->getType());
                $resources[$resource->getTitle() . $resource->getRid()] = $xmlResource;              
            }
        }

        return $resources;
    }    
    
    function getXmlString()
    {
        return $this->xml->getXml();
    }
    
    function getSession()
    {
        return $this->session;
    }
    
    function getSessionError()
    {
        return $this->session->error;
    }

    function setError($error) {
        $this->xml->setError($error);
    }

}
?>
