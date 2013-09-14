<?php
/**
 * @author matthewschmidt
 */
class Elluminate_WS_SOAP_Helper {

   const SAS_URL = "http://sas.elluminate.com/";
   const WS_EVENT_URL = "webservice.event";
   const WSDL_SUFFIX = "WSDL";
   const FILE_PREFIX = "file://";
   const MAX_RETRIES = 3;
   const RETRY_PAUSE_SECONDS = 1;
   const SAS_CONNECT_ERROR = "Could not connect to host";

   private $logger;

   private $serverurl;
   private $eventurl;
   private $userName;
   private $password;
   private $wsDebug;
   private $soapOptions;


   public function __construct() {
      $this->logger = Elluminate_Logger_Factory::getLogger("Elluminate_WS_SOAPHelper");

      $this->serverurl = Elluminate_Config_Settings::getElluminateSetting('elluminate_server');
      $this->userName = Elluminate_Config_Settings::getElluminateSetting('elluminate_auth_username');
      $this->password = Elluminate_Config_Settings::getElluminateSetting('elluminate_auth_password');
      $this->wsDebug = Elluminate_Config_Settings::getElluminateSetting('elluminate_ws_debug');
      $this->soapOptions = array();
   }

   /**
    * Send a SOAP Command
    * @param  $command
    * @param  $args
    * @param  $connectArgs
    *
    * @param  $overRideClient
    *   overRideClient must be the name of a valid class that extends SoapClient.  This class
    *   can be used to do any modifications to the soap request prior to sending to the server.
    *
    * @throws Elluminate_Exception
    * @return Elluminate_WS_SOAP_Response
    */
   public function send_command($command, $args = null, $connectArgs = null, $overRideClient = null) {
      $this->initializeCommand($connectArgs);
      $wsdl = $this->getWsdlUrl();
      $this->logger->debug("send_command [" . $command . "] wsdl [" . $wsdl . "]");

      try {
         $client = $this->buildSoapClient($wsdl, $overRideClient);
         $header = $this->buildRequestHeader();
         $client->__setSoapHeaders($header);

         $attempts = 0;
         while ($attempts <= self::MAX_RETRIES) {
            try {
               $result = $client->__soapCall(
                  $command,
                  array('parameters' => $args),
                  array("location" => $this->eventurl),
                  $header);
               break;
            } catch (Exception $soapCallException) {
               $attempts++;
               if ($soapCallException->getMessage() == self::SAS_CONNECT_ERROR && $attempts <= self::MAX_RETRIES) {
                  $this->logger->error('Could Not Connect - Retrying Attempt [' . $attempts . "]");
                  sleep(self::RETRY_PAUSE_SECONDS);
                  continue;
               }
               throw $soapCallException;
            }
         }

         $soapResponse = $this->buildResponseObject($result);
         $this->responseLogging($client);
      } catch (Exception $e) {
         $this->responseLogging($client);
         throw new Elluminate_Exception(
            $e->getMessage(),
            $e->getCode(),
            'soap_send_command_error',
            $this->buildErrorDetails($e));
      }

      //Could not parse response
      if ($soapResponse->error) {
         throw new Elluminate_Exception('Invalid Web Service Response', 0, 'user_error_soaperror');
      }

      return $soapResponse;
   }

   private function buildRequestHeader() {
      return new SoapHeader(
         self::SAS_URL,
         "BasicAuth",
         array(
            'Name' => $this->userName,
            'Password' => $this->password),
         true);
   }

   private function buildErrorDetails($soapException) {
      $this->logger->debug("SOAP Error: " . $soapException->getMessage());
      $details = new stdClass;
      $details->soapmessage = $soapException->getMessage();
      return $details;
   }

   private function initializeCommand($connectArgs) {
      $this->checkConnectArgs($connectArgs);
      $this->checkConfig();
      $this->buildEventUrl();

      $this->checkDebugging();
      $this->proxyConfig();
   }

   private function buildSoapClient($wsdl, $overRideClient) {
      if ($overRideClient != null) {
         $this->logger->debug("Override Client Set [" . $overRideClient . "]");
         $client = new $overRideClient($wsdl, $this->soapOptions);
      } else {
         $client = new SoapClient($wsdl, $this->soapOptions);
      }
      $client->xml_encoding = "UTF-8";
      return $client;
   }

   private function getWsdlUrl() {
      global $ELLUMINATE_CONTAINER, $CFG;

      //Config Setting will override pimple setting
      $cfgUrl = Elluminate_Config_Settings::getElluminateSetting('elluminate_wsdl_url');

      if ($cfgUrl != '') {
         return $cfgUrl;
      } else {
         $scheduler = Elluminate_Config_Settings::getElluminateSetting('elluminate_scheduler');
         return self::FILE_PREFIX .
         $CFG->dirroot .
         $ELLUMINATE_CONTAINER[$scheduler . self::WSDL_SUFFIX];
      }
   }

   private function checkConfig() {
      if ($this->configHasDefaultValues()) {
         throw new Elluminate_Exception('The Blackboard Collaborate module has not been configured.  Please contact your administrator.', 0, 'user_error_unconfiguredmodule');
      }
   }

   private function configHasDefaultValues(){
      if (
         $this->serverurl == get_string('default_elluminate_server', 'elluminate') ||
         $this->userName == get_string('default_elluminate_auth_username', 'elluminate') ||
         $this->password == get_string('default_elluminate_auth_password', 'elluminate')){
         return true;
      }else{
         return false;
      }
   }

   private function checkDebugging() {
      if ($this->wsDebug) {
         $this->logger->debug("=========== send_command: elluminate_ws_debug enabled: START SOAP REQUEST/RESPONSE ===========");
         $this->soapOptions = array('trace' => 1);
      }
   }

   /**
    * If the moodle proxy configuration options are setup
    *
    *  Site Administration->Server->HTTP
    *
    * Then configure the SOAP client to use the proxy settings.
    */
   private function proxyConfig() {
      $proxyHost = Elluminate_Config_Settings::getElluminateSetting('proxyhost');
      $proxyPort = Elluminate_Config_Settings::getElluminateSetting('proxyport');
      //Set moodle proxy settings if applicable
      if ($proxyHost != '') {
         $this->logger->debug("SOAP Proxy Configuration Enabled: Host [" . $proxyHost . "] port [" . $proxyPort . "]");
         $this->soapOptions['proxy_host'] = $proxyHost;
         $this->soapOptions['proxy_port'] = $proxyPort;
         $this->soapOptions['proxy_login'] = Elluminate_Config_Settings::getElluminateSetting('proxyuser');
         $this->soapOptions['proxy_password'] = Elluminate_Config_Settings::getElluminateSetting('proxypassword');
      }
   }

   /**
    * Request Complete and response retrieved - log details if debugging is on
    */
   private function responseLogging($client) {
      if ($this->wsDebug) {
         $this->logger->debug("SOAP REQUEST: " . $client->__getLastRequest());
         $this->logger->debug("SOAP RESPONSE: " . $client->__getLastResponse());
         $this->logger->debug("=========== send_command: elluminate_ws_debug enabled: END SOAP REQUEST/RESPONSE ===========");
      }
   }

   /**
    * Process the response from the SOAP call and build a SOAP response
    * object.  The key piece of information this object needs to know is
    * the name of the root element in the XML.
    *
    * Errors:
    *   -Response is not a stdclass (blank response)
    *   -Response is a stdclass but has no fields (only root element returned from server)
    *
    * @param unknown_type $result
    * @return Elluminate_WS_SOAP_Response
    */
   private function buildResponseObject($apiResult) {
      $soapResponse = new Elluminate_WS_SOAP_Response();
      if ($apiResult instanceof stdclass) {
         $soapResponse->apiResponse = $apiResult;
         //Get the key name for the first element
         //then exit the loop
         if ($apiResult instanceof stdclass) {
            $rootName = $this->getResponseRootTypeName($apiResult);
            if ($rootName == '') {
               $soapResponse->empty = true;
            } else {
               $soapResponse->responseType = $rootName;
            }
         }
      } else {
         $soapResponse->error = true;
      }
      return $soapResponse;
   }

   private function buildEventUrl() {
      if (substr($this->serverurl, strlen($this->serverurl) - 1, 1) != '/') {
         $this->eventurl = $this->serverurl . '/' . self::WS_EVENT_URL;
      } else {
         $this->eventurl = $this->serverurl . self::WS_EVENT_URL;
      }
      $this->logger->debug("Event URL [" . $this->eventurl . "]");
   }

   //Get the root element of the response, which we use to identify the response type
   private function getResponseRootTypeName($apiResult) {
      $rootName = '';
      foreach ($apiResult as $key => $value) {
         $rootName = $key;
         break;
      }
      return $rootName;
   }

   private function checkConnectArgs($connectArgs) {
      if (isset($connectArgs['serverurl'])) {
         $this->serverurl = $connectArgs['serverurl'];
      }
      if (isset($connectArgs['username'])) {
         $this->userName = $connectArgs['username'];
      }
      if (isset($connectArgs['password'])) {
         $this->password = $connectArgs['password'];
      }
   }
}

