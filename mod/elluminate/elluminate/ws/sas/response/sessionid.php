<?php
/**
 * Handle Response processing for the SetTelephony/GetTelephony API calls
 *
 * The naming (_SessionId) of this response object is strange due to the format of the API
 * response (See example below).  The response returns a collection, but the first two elements
 * in the collection are information related to all items in the collection.
 *
 * The way the php soap libraries parses the response is that it ignores the root element and returns a
 * stdclass based on the inner elements.  In this case, the first element here is "sessionId"
 *
 * This is quite fragile and ideally the response format should be cleaned up on the SAS End.
 *
 *   <SetTelephonyResponse xmlns="http://sas.elluminate.com/">
 *        <sessionId xsi:type="xs:long">2855</sessionId>
 *        <telephonyType xsi:type="xs:string">integrated</telephonyType>
 *        <TelephonyResponseItem>
 *           <itemType xsi:type="xs:string">moderator</itemType>
 *           <uri xsi:type="xs:string">1-587-887-1860</uri>
 *           <pin xsi:type="xs:long">126174840593</pin>
 *        </TelephonyResponseItem>
 *
 * @author dwieser
 *
 */
class Elluminate_WS_SAS_Response_SessionId implements Elluminate_WS_APIResponseHandler{

   private $logger;

   const PIN_SEGMENT_LENGTH = 3;
   const PIN_SEPARATOR = ' ';

   public function __construct(){
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_SAS_Response_SessionId");
   }

   public function processResponse($apiResponse){
      $this->logger->debug("processResponse start");
      $telephonyInstances = null;
      try{
         $telephonyInstances = $this->loadTelephonyTypes($apiResponse);
      }catch(Exception $e){
         throw new Elluminate_Exception("processResponse: SAS Response Processing Error",0,'user_error_soaperror');
      }

      $this->logger->debug("processResponse, returning [" . sizeof($telephonyInstances) . "] telephony entries");
      return $telephonyInstances;
   }

   private function loadTelephonyTypes($apiResponse){
      $telephonyInstances = array();
      $sessionId = $apiResponse->sessionId;

      //When we turn off telephony, the response has no TelephonyResponseItem elements
      if (isset($apiResponse->TelephonyResponseItem)){
         $telephonyResponseItem = $apiResponse->TelephonyResponseItem;
         if ($telephonyResponseItem instanceof stdclass){
            array_push($telephonyInstances, $this->getTelephonyInstanceFromAPI($sessionId,$telephonyResponseItem));
         } else {
            foreach ($telephonyResponseItem as $telephonyType) {
               array_push($telephonyInstances, $this->getTelephonyInstanceFromAPI($sessionId,$telephonyType));
            }
         }
      }
      return $telephonyInstances;
   }


   private function getTelephonyInstanceFromAPI($sessionId, $telephonyType){
      $telephonyInstance = new Elluminate_Telephony_Instance();
      $telephonyInstance->itemtype = $telephonyType->itemType;
      $telephonyInstance->uri = $telephonyType->uri;
      $telephonyInstance->pin = $this->formatPIN($telephonyType->pin);
      $telephonyInstance->meetingid = $sessionId;
      $this->logger->debug("getTelephonyInstanceFromAPI: " . $telephonyInstance);
      return $telephonyInstance;
   }

   /**
    * PIN returned from SAS is a number >11 digit number and should be split into
    * segments of 3 starting from the right side of the number.
    * 
    * i.e. 12345678901 becomes 12 345 678 901
    */
   private function formatPIN($rawpin){
      $reverseFormattedPin = '';
      if (strlen($rawpin) > 0){
         $revString = strrev($rawpin);
         for($pinCnt = 0; $pinCnt < strlen($revString); $pinCnt++){
            if ($pinCnt > 0 && $pinCnt % self::PIN_SEGMENT_LENGTH == 0){
               $reverseFormattedPin .= self::PIN_SEPARATOR;
            }     
            $reverseFormattedPin .= $revString[$pinCnt];
         }
      }
      return strrev($reverseFormattedPin);
   }
}