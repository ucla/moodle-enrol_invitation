<?php
class Elluminate_Recordings_Factory{
   
   public function getRecording(){
      global $ELLUMINATE_CONTAINER;
      return $ELLUMINATE_CONTAINER['recording'];
   }
   
   public function getRecordingFile(){
      global $ELLUMINATE_CONTAINER;
      return $ELLUMINATE_CONTAINER['recordingFile'];
   }  
}