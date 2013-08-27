<?php
/**
 * Response handler for GetTelephonyLicenseInfo.  Naming of the response isn't the best because
 * of the response structure.
 * 
 * 
 * @author dwieser
 *
 */
class Elluminate_WS_SAS_Response_TelephonyType implements Elluminate_WS_APIResponseHandler{

   const NO_TELEPHONY = "none";
   
   private $logger;

   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_SAS_Response_TelephonyType");
   }
   
   public function processResponse($apiResponse){
      $this->logger->debug("processResponse start");
      $license = null;
      try{
         $license = $this->loadLicenseObject($apiResponse);
      }catch(Exception $e){
         throw new Elluminate_Exception("processResponse: SAS Response Processing Error",0,'user_error_soaperror');
      }
      return $license;
   }
   
   /**
    * Type returned from API can be:
    *    -integrated
    *    -thirdParty
    *    -none
    *    
    *    If none is returned, this means not licensed, the other two types 
    *    inherently mean licensed for that type.
    * @param unknown_type $apiResponse
    */
   public function loadLicenseObject($apiResponse){
      $license = new Elluminate_License_Entry();
      $license->optionname = Elluminate_License_Constants::TELEPHONY;
      
      $type = $apiResponse->telephonyType;
      
      if ($type != self::NO_TELEPHONY){
         $license->variationname = $type;
         $license->licensed = true;
      }else{
         $license->licensed = false;
      }
      $this->logger->debug("Processed Response " . $license->__toString());
      return $license;
   }
}