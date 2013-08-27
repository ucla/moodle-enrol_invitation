<?php
/**
 * This class is a helper for the SAS SOAP implementation.  It's purpose is to take
 * API responses from the Web Service and translate them into the required business
 * objects for the rest of the system.
 *
 * Above the level of Elluminate_WS_SchedulingManager, there should be no knowledge of API
 * implementation, and the system should only work with the business objects.
 *
 * This handles the response processing for GetOptionsLicenses
 *
 * @author dwieser
 *
 */
class Elluminate_WS_SAS_Response_OptionLicenseResponse implements Elluminate_WS_APIResponseHandler{

   private $logger;

   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_SAS_LicenseLoader");
   }
   
   /**
    * Process the response from getOptionsLicenses and convert into a series of license objects
    *
    * We trap all errors here that may result from the api response not matching exactly
    * what expected.
    *
    * @param unknown_type $apiResponse
    */
   public function processResponse($apiResponse){
      $this->logger->debug("processResponse start");
      $licenseCollection = array();

      try{
         if ($apiResponse != null){
            $licenseCollection = $this->processValidResponse($apiResponse);
         }
      }catch(Exception $e){
         throw new Elluminate_Exception('SAS Response Processing Error',0,'user_error_soaperror');
      }
      $this->logger->debug("processResponse complete, returning [" . sizeof($licenseCollection) . "] licenses");
      return $licenseCollection;
   }

   private function processValidResponse($apiResponse){
      $licenseCollection = array();
      foreach ($apiResponse->OptionLicenseResponse as $apiLicense){
         if (isset($apiLicense->optionVariationNameCollection)){
            $variationList = $this->loadVariationNameLicense($apiLicense);
            $licenseCollection = array_merge($licenseCollection, $variationList);
         }else{
            $licenseCollection[] = $this->loadNonVariationLicense($apiLicense);
         }
      }
      return $licenseCollection;
   }
    
   private function loadNonVariationLicense($apiLicense){
      $license = new Elluminate_License_Entry();
      $license->optionname = $apiLicense->optionName;
      $license->licensed = $apiLicense->licensed;
      return $license;
   }

   /**
    * Certain licenses have nest sub-licensing called
    * variations.  For example, mobile could be licensed for
    * iphone but not android.
    *
    * This will process this type of entry, which is stored in the DB
    * as a unique row for each variation
    */
   private function loadVariationNameLicense($apiLicense){
      $variationList = array();
      $optionName = $apiLicense->optionName;
      $optionLicense = $apiLicense->licensed;
       
      $parentCollection = $apiLicense->optionVariationNameCollection;
      $variationCollection = $parentCollection->optionVariationName;
      foreach($variationCollection as $variationName){
         $license = new Elluminate_License_Entry();
         $license->optionname = $optionName;

         $license->variationname = $variationName;
         $license->licensed = $optionLicense;
         $this->logger->debug("Load " . $license);
         $variationList[] = $license;
      }
      return $variationList;
   }
}