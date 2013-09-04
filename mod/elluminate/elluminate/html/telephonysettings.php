<?php

/**
 * This is a helper class for handling the configuration options for telephony on the session create/update form.
 *
 * @author dwieser
 *
 */
class Elluminate_HTML_TelephonySettings{
   //The ID of the telephony value on the form is setup to be different that the session attribute
   //because if it's the same, moodle will automatically populate the value.
   //We don't want that in this case, since the global setting may override the session setting.
   const TELEPHONY_FORM_ID = "telephony_formvalue";
   const TELEPHONY_GLOBAL_SETTING = "telephony_global_setting";
   const TELEPHONY_DEFAULT_CONFIG = "elluminate_telephony";
   const TELEPHONY_CHOOSE = -1;
   const TELEPHONY_FORCE_ON = 1;
   const TELEPHONY_FORCE_OFF = 2;
   
   const TELEPHONY_ENABLED = '1';
   const TELEPHONY_DISABLED = '0';
   
   private $licenseManager;
   private $logger;
   
   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_HTML_TelephonySettings");
   }
   
   public function __set($property, $value){
      if (property_exists($this, $property)) {
         $this->$property = $value;
      }
      return $this;
   }
   
   public function addTelephonyConfigurationOption($form, $existingSession = null){
      //Do nothing if telephony is not licensed.
      if (!$this->isTelephonyLicensed()){
         return;
      }
      
      $globalSetting = $this->processTelephonyGlobalDefault($form);
      $defaultValue = $this->getTelephonyDefaultSetting($globalSetting,$existingSession);
      $this->telephonyCheckbox($form, $defaultValue);
      
      //Setup disable of field
      $form->disabledIf(self::TELEPHONY_FORM_ID, self::TELEPHONY_GLOBAL_SETTING, 'neq',self::TELEPHONY_CHOOSE);
   }

   private function isTelephonyLicensed(){
      $licensed = false;
      
      if ($this->licenseManager->isTelephonyLicensed()){
         $licensed = true;
      }
      return $licensed;
   }
   
   private function telephonyCheckbox($form, $defaultValue){
      $form->addElement('advcheckbox', self::TELEPHONY_FORM_ID, get_string(self::TELEPHONY_FORM_ID, 'elluminate'));
      $form->addHelpButton(self::TELEPHONY_FORM_ID, self::TELEPHONY_FORM_ID, 'elluminate');
      $form->setDefault(self::TELEPHONY_FORM_ID,$defaultValue);
   }
   
   /*
    * Possible outputs here are:
    * 
    * Module Setting: CHOOSE 
    *    -new session: default to enabled
    *    -existing session: based on previously saved value
    *    
    * Module Setting: YES
    *    -force setting to YES
    *    
    * Module Setting: NO
    *    -force setting to NO
    * 
    */
   private function getTelephonyDefaultSetting($globalSetting, $existingSession){
      $defaultValue = self::TELEPHONY_ENABLED;
      if ($globalSetting == self::TELEPHONY_CHOOSE){
         if (isset($existingSession)){
            if ($existingSession->telephony){
               $defaultValue = self::TELEPHONY_ENABLED;
            }else{
               $defaultValue = self::TELEPHONY_DISABLED;
            }
         }
      }
      
      if ($globalSetting == self::TELEPHONY_FORCE_ON){
         $defaultValue = self::TELEPHONY_ENABLED;
      }
      
      if ($globalSetting == self::TELEPHONY_FORCE_OFF){
         $defaultValue = self::TELEPHONY_DISABLED;
      }
      
      return $defaultValue;
   }
   
   private function processTelephonyGlobalDefault($form){
      $globalDefault = Elluminate_Config_Settings::getElluminateSetting(self::TELEPHONY_DEFAULT_CONFIG);
      $form->addElement('hidden', self::TELEPHONY_GLOBAL_SETTING, $globalDefault);
      $form->setType(self::TELEPHONY_GLOBAL_SETTING, PARAM_INT);
      return $globalDefault;
   }
}