<?php
class Elluminate_Recordings_Utils{
   
   public static function getRecordingModeString($recordingMode){
      $recordingstring = '';
      switch ($recordingMode) {
         case Elluminate_Recordings_Constants::RECORDING_MANUAL:
            $recordingstring = get_string('recordingmanual', 'elluminate');
            break;
         case Elluminate_Recordings_Constants::RECORDING_AUTOMATIC:
            $recordingstring = get_string('recordingautomatic', 'elluminate');
            break;
         case Elluminate_Recordings_Constants::RECORDING_NONE:
            $recordingstring = get_string('recordingnone', 'elluminate');
            break;
      }
      return $recordingstring;
   }
}