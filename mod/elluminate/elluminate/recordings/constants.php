<?php
class Elluminate_Recordings_Constants{

   const RECORDING_MANUAL = 1;
   const RECORDING_AUTOMATIC = 2;
   const RECORDING_NONE = 3;
   
   const VCR_FORMAT = 'vcr';
   const MP3_FORMAT = 'mp3';
   const MP4_FORMAT = 'mp4';
   
   const AVAILABLE_STATUS = 'available';
   const NOT_AVAILABLE_STATUS = 'not_available';
   const IN_PROGRESS_STATUS = 'in_progress';
   const NOT_APPLICABLE_STATUS = 'not_applicable';
   
   //this type of error is on the moodle side only, when we can't communicate with the server.
   const COMM_ERROR_STATUS = 'comm_error';
}