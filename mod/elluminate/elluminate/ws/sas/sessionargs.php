<?php
class Elluminate_WS_SAS_SessionArgs{
   
   public static function getAPIArgumentsFromSession($Session,$type='create') {
      $args = array ();
      
      //Certain Arguments are only passed on create
      if ($type == 'create'){
         $args['creatorId'] = $Session->creator;
         //These 2 properties are only updated via updateUsers
         $args['nonChairList'] = $Session->nonchairlist;
         $args['groupingList'] = $Session->course;
      }
      
      //In case of update, we already have a session Id to load
      if ($type == 'update'){
         $args['sessionId'] = $Session->meetingid;
      }

      //chairlist can be set on update via the pre-populate moderators configuration value
      $args['chairList'] = $Session->chairlist;

      //everything else is passed on create AND update
      $args['startTime'] = Elluminate_WS_Utils::convertPHPDateToSASDate($Session->timestart);
      $args['endTime'] = Elluminate_WS_Utils::convertPHPDateToSASDate($Session->timeend);
      $args['sessionName'] = $Session->sessionname;
      $args['boundaryTime'] = $Session->boundarytime;
      $args['maxTalkers'] = $Session->maxtalkers;
      $args['recordingModeType'] = $Session->recordingmode;
   
      //These 4 values are not editable on the session create
      //moodle form, but rather are general module configuration
      //values.  They're updated on both a create and update because
      //a sys admin might change the values between the creation
      //of a session and it's update.
      $args['mustBeSupervised'] = $Session->mustbesupervised;
      $args['raiseHandOnEnter'] = $Session->raisehandonenter;
      $args['permissionsOn'] = $Session->permissionson;
      $args['openChair'] = $Session->allmoderators;
           
      return $args;
   }
   
   //Available attributes in the SetSession call not implemented yet in the
   //Moodle bridge.
   //$args['accessType'] = $Session->accessType;
   //$args['chairNotes'] = $Session->chairnotes;
   //$args['maxCameras'] = $Session->maxCameras;
   //
   //$args['nonChairNotes'] = $Session->nonChairNotes;
   //$args['recurrenceCount'] = $Session->recurrenceCount;
   //$args['recurrenceDays'] = $Session->recurrenceDays;
   //$args['reserveSeats'] = $Session->reserveSeats;
   //$args['secureSignOn'] = $Session->secureSignOn;
   //$args['versionId'] = $Session->versionId;
   //$args['allowInSessionInvites'] = $Session->allowInSessionInvites;
   //$args['hideParticipantNames'] = $Session->hideParticipantNames;
}