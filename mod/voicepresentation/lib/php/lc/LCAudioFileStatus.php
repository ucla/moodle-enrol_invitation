<?php

define("VALUE_STATUS_EXISTS", "exists");
define("VALUE_STATUS_GENERATING", "generating");
define("VALUE_STATUS_DOES_NOT_EXIST", "does_not_exist");
define("VALUE_STATUS_ERROR", "error");
define("VALUE_STATUS_GENERATING_PREVIOUS_ERROR", "generating_previous_error");
/*define("statusList", array(
          VALUE_STATUS_EXISTS,
          VALUE_STATUS_GENERATING,
          VALUE_STATUS_DOES_NOT_EXIST,
          VALUE_STATUS_ERROR,
          VALUE_STATUS_GENERATING_PREVIOUS_ERROR));*/

define("ATTRIB_AUDIO_FILE_STATUS", "status");
define("ATTRIB_AUDIO_FILE_DOWNLOAD_URI", "download_uri");
  
class LCAudioFileStatus{
  
  
  var $status = "";
  var  $uri = "";
    
  function LCAudioFileStatus($currentRecord, $authToken)
  {
    $this->setStatus( $this->getKeyValue($currentRecord,ATTRIB_AUDIO_FILE_STATUS));
    if ($this->getStatus() == VALUE_STATUS_EXISTS) {
      $audioUrl = $this->getKeyValue($currentRecord,ATTRIB_AUDIO_FILE_DOWNLOAD_URI);
      if ($authToken != null && strlen($authToken)>0 && indexOf(strtolower($audioUrl),"hza=") == -1) {
        if (indexOf($audioUrl,"?") == -1) {
          $audioUrl .= "?";
        } else if (!endsWith($audioUrl,"&")) {
          $audioUrl .= "&";
        }
        $audioUrl .= "hzA=" . urlencode(utf8_encode($authToken));
        
      }
      $this->setUri($audioUrl);
    }
  }
  
  function getStatus()
  {
    return $this->status;
  }
  
  function  setStatus($status)
  {
    /*if (!statusList.contains(status)) {
      throw new IllegalArgumentException("The only values allowed for the status are " + VALUE_STATUS_EXISTS + ", "
              + VALUE_STATUS_GENERATING + ", "
              + VALUE_STATUS_DOES_NOT_EXIST + " and "
              + VALUE_STATUS_ERROR);
    }*/
    $this->status = $status;
  }
  
  function getUri()
  {
    return $this->uri;
  }
  
  function setUri($uri)
  {
    $this->uri = $uri;
  }
  
  function getKeyValue($tab,$key){
    if(array_key_exists($key,$tab)){
        return $tab[$key];
    }
    return "";
  }
}
?>
