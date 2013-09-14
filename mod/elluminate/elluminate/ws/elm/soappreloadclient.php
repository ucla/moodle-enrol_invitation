<?php
/**
 * The UploadRepositoryPresentation API call for ELM has 2 special
 * conditions for the request.
 * 
 * 1.) An additional namespace needs to be defined for the XML document:
 *     xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance
 *     
 * 2.) The Content Element needs the content type explicitly defined:
 * 
 *    <ns1:content xsi:type="xs:base64Binary"> ...
 *    
 * Without these two conditions, the ELM server throws errors when processing
 * the request.
 * 
 * This class will override the default SoapClient and add a custom
 * doRequest method that will alter the $request XML document created by
 * the SoapClient, adding these 2 values before sending the request back
 * to the default doRequest method.
 *    
 * @author dwieser
 *
 */
class Elluminate_WS_ELM_SOAPPreloadClient extends SoapClient {
   
   function __doRequest($request, $location, $action, $version, $one_way = NULL) {
      $dom = new DomDocument('1.0', 'UTF-8');
      $dom->preserveWhiteSpace = false;
      $dom->loadXML($request);
      $hdr = $dom->createAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:xsi');

      $contentElements = $dom->getElementsByTagName ('content');
      foreach ($contentElements as $content){
         $content->setAttribute( 'xsi:type' , 'xs:base64Binary' );
      }
      $request = $dom->saveXML();

      return parent::__doRequest($request, $location, $action, $version);
   }
}