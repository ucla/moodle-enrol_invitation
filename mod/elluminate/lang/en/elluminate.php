<?php // $Id: elluminatelive.php,v 1.8 2009-06-05 20:12:38 jfilip Exp $

//Module Help
$string['modulename_help'] = 'The Blackboard Collaborate module enables teachers and students to meet in a virtual classroom
by using Blackboard Collaborate web conferencing.  
These online meeting spaces feature:

* two-way audio
* multi-point video
* text chat
* interactive whiteboard, application and desktop sharing
* rich media
* breakout rooms
* polls and quizzes

These sessions can also be recorded for offline viewing and review.
Optionally, students can be automatically assigned a grade for attending the session.';

$string['modulename_link'] = 'http://www.blackboard.com/Platforms/Collaborate/Products/Blackboard-Collaborate/Web-Conferencing.aspx';

//Moodle Role Level Permissions
$string['elluminate:addinstance'] = 'Ability to add a new Blackboard Collaborate Session.';
$string['elluminate:deleteanyrecordings'] = 'Ability to delete any recordings.';
$string['elluminate:deleterecordings'] = 'Ability to delete own recordings.';
$string['elluminate:editallrecordings'] = 'Ability to edit any recording descriptions.';
$string['elluminate:editownrecordings'] = 'Ability to edit own recording descriptions.';
$string['elluminate:enablerecordings'] = 'Ability to hide / show own recordings.';
$string['elluminate:joinmeeting'] = 'Ability to participate in a session.';
$string['elluminate:manage'] = 'Ability to manage the settings.';
$string['elluminate:manageattendance'] = 'Ability to modify attendance records (grades).';
$string['elluminate:manageanyrecordings'] = 'Ability to hide / show any session recordings.';
$string['elluminate:managemoderators'] = 'Ability to add / remove Moderators.';
$string['elluminate:manageparticipants'] = 'Ability to add / remove Participants.';
$string['elluminate:managepreloads'] = 'Ability to add / delete preload files.';
$string['elluminate:managerecordings'] = 'Ability to hide / show group session recordings.';
$string['elluminate:moderatemeeting'] = 'Ability to be a session Moderator.';
$string['elluminate:view'] = 'Ability to access the session.';
$string['elluminate:viewattendance'] = 'Ability to view attendance records.';
$string['elluminate:viewguestlink'] = 'Ability to view guest link.';
$string['elluminate:viewmoderators'] = 'Ability to view Moderators.';
$string['elluminate:viewparticipants'] = 'Ability to view Participants.';
$string['elluminate:viewrecordings'] = 'Ability to view recordings.';
$string['elluminate:convertallrecordings'] = 'Ability to initiate conversion of all recordings to audio (MP3) and video (MP4) formats.';
$string['elluminate:convertownrecordings'] = 'Ability to initiate conversion of own recordings to audio (MP3) and video (MP4) formats.';

// ****** Main Session Create/Update Form ******
$string['boundarytime'] = 'Early session entry';
$string['boundarytime_help'] = 'The period before the start of a session in which users can join the session.  Displayed in minutes.';
$string['boundarytimedisplay'] = 'Display early session entry time';
$string['customdescription'] = 'Prefix description with group name';
$string['customname'] = 'Use Course code and group<br />name for session name';
$string['customname_help'] = 'When creating a group session, an individual session is created for each group that is setup for the Course. <br> This option will cause the group name to become part of the session name.';
$string['customsessionname'] = 'Session name';
$string['customsessionname_help'] = 'Session name is the value passed to the Collaborate Scheduling Server';
$string['description'] = 'Description';
$string['maxtalkers'] = 'Max talkers';
$string['maxtalkers_help'] = 'Maximum number of simultaneous talkers to be configured in the Blackboard Collaborate Session at launch time.';
$string['gradeattendance'] = 'Grade attendance';
$string['gradesession'] = 'Grade this session';
$string['gradesession_help'] = 'When selected, this session will show in the Gradebook and a Grading attendance option must be selected.<br><br><b>No Grade:</b><br><br>Attendance for the session is not tracked, but the session will show in the Gradebook to allow for text feedback to be entered<br><br><b>Scale Grade:</b><br><br>If a Student attends the session, they will be assigned the best grade in the selected scale.<br><br><b>Numerical Grade:</b><br> <br>If a Student attends the session, they will be assigned the full numerical grade selected for this session.';
$string['gradesessiondeletewarn'] = 'Warning: When disabling grading for a session, all existing grades associated with that session will be removed.';
$string['meetingbegins'] = 'Session begins';
$string['meetingends'] = 'Session ends';
$string['recordingmode'] = 'Recording mode';
$string['restrictparticipants'] = 'Restrict session Participants';
$string['restrictparticipants_help'] = 'Checking this option will create a private session that can only be accessed by invited Participants.  Moderators and Participants can be managed from the view session page.  <br><br>This option does not apply to sessions with a Group Mode of visible or separate.  Participation in that type of session can be managed via group membership.';
$string['sessionname'] = 'Custom session name';
$string['title'] = 'Title';
$string['title_help'] = 'The session title is displayed in the Moodle Course Schedule and Calendar.  If no session name is provided, the title is used as the session name as well.';
$string['appendgroupname'] = 'Custom group session name';
$string['recordingmode_help'] = 'The mode of recording in the Blackboard Collaborate Session.  <br><br> 1) Manual - A chairperson must start the recordings <br><br> 2) Automatic - the recording starts automatically when the session starts <br><br> 3) Disabled - Recording is disabled';
$string['groupsettingsdisabled'] = 'Group settings disabled due to Course group mode';
$string['telephony_formvalue'] = 'Enable session teleconferencing';
$string['telephony_formvalue_help'] = 'Allow session participants to dial into sessions via teleconference.  <br>Once this option is enabled, the Telephone number and PIN will be available on the view session page.';
//Recording Modes
$string['disabled'] = 'Disabled';
$string['manual'] = 'Manual';
$string['automatic'] = 'Automatic';
//Custom Group Name Options
$string['customnamegrouponly'] = 'Only group name';
$string['customnameappend'] = 'Append group name to title';
$string['customnamenone'] = 'None';
//Section Titles
$string['group'] = 'Group Settings';
$string['scheduling'] = 'Schedule';
$string['basicsession'] = 'Basic Session Details';
$string['details'] = 'Session Information';
$string['settings'] = 'Session Attributes';
$string['grading'] = 'Session Grading';
$string['groupnamelabel'] = 'Group Name';
$string['groupsessions'] = 'Group Sessions';

//Form Validation
$string['invalidsessiontimes'] = 'The session start time of {$a->timestart} is after the session end time of {$a->timeend}';
$string['starttimebeforenow'] = 'The session start time of {$a->timestart} is before the current time.';
$string['meetinglessthanyear'] = 'Your session cannot be over a year long.';
$string['meetingnamemustbeginwithalphanumeric'] = 'Your session name must begin with an alphanumeric character.  If a session name hasn\'t been entered the title will be used.';
$string['groupname_meetingnamemustbeginwithalphanumeric'] = '<b>Custom group session name error:</b><br> The group name <b>{$a->groupname}</b> cannot be used as a session name because it does not begin with a letter or a number.  <br><br>Either correct the group name or choose a different custom group session name.';
$string['groupname_specialcharacters'] = '<b>Custom group session name error:</b><br> The group name <b>{$a->groupname}</b> cannot be used as a session name because it contains invalid characters. <br><br> Either correct the group name or choose a different custom group session name.';
$string['groupname_meetingnameempty'] = '<b>Custom group session name error:</b><br> The group name <b>{$a->groupname}</b> cannot be used as a session name because it contains invalid characters. <br><br> Either correct the group name or choose a different custom group session name.';
$string['meetingnameempty'] = 'Your session name is empty.  The following characters are stripped out <,>,&,#,%,\' please enter a alphanumeric character. If a session name hasn\'t been entered the title will be used.';
$string['samesessiontimes'] = 'The session start time of {$a->timestart} is the same as session end time of {$a->timeend}';
$string['meetingstartoverayear'] = 'Your session cannot start over a year into the future.';
$string['badgroupname'] = " (Invalid group name)";

// ****** Session View Page ******
$string['guestlink'] = 'Guest link';
$string['guestlinkerror'] = 'Could not retrieve guest link';
$string['guestlinkgrouperror'] = 'The guest link has not been generated. Please join the session and then refresh this page to retrieve the guest link.';
$string['preloadfile'] = 'Preload file';
$string['nopreloadfile'] = 'None';
$string['sessionnamedisplay'] = 'Session name';
$string['joinsession'] = 'Join Session';
$string['supportlinktext'] = 'Verify your system is setup properly ';
$string['telephonydetails'] = 'Teleconference details';
$string['moderatorphone'] = 'Moderator dial in';
$string['participantphone'] = 'Participant dial in';
$string['telephonypin'] = 'PIN: ';
$string['telephonygrouperror'] = 'Dial in information has not been generated. Please join the session and then refresh this page to retrieve the dial in number and PIN.';
$string['telephonysaserror'] = 'Dial in information could not be retrieved at this time.  Please try again later.';

//Invitee Details
$string['nomoderator'] = 'No Moderators are invited to this session';
$string['singlemoderator'] = '1 Moderator is invited to this session';
$string['multimoderator'] = '{$a} Moderators are invited to this session';
$string['editmoderatorsforthissession'] = 'Add/Remove Moderators';

$string['noparticipant'] = 'No Participants are invited to this session';
$string['singleparticipant'] = '1 Participant is invited to this session';
$string['multiparticipant'] = '{$a} Participants are invited to this session';
$string['allparticipant'] = 'All Students enrolled in this Course may attend this session';
$string['editparticipantsforthissession'] = 'Add/Remove Participants';
$string['groupparticipant'] = 'All members of the "{$a}" group may attend this session';

//Attendance Details
$string['sessionattendance'] = "Session Attendance";
$string['noattendance'] = "No Students attended this session";
$string['singleattendance'] = "1 Student attended this session";
$string['multiattendance'] = '{$a} Students attended this session';
$string['editattendance'] = "View attendance details";

// ******* Session View - Recordings *******
$string['deleterecordingconfirm'] = 'Are you sure you want to delete the recording dated {$a} from the Blackboard Collaborate server?';
$string['deleterecordingfailure'] = 'Could not delete the recording from the Blackboard Collaborate server.';
$string['deleterecordingsuccess'] = 'Successfully deleted the recording from the Blackboard Collaborate server.';
$string['deletethisrecording'] = 'Delete recording';
$string['hidethisrecording'] = 'Make this recording invisible';
$string['showthisrecording'] = 'Make this recording visible';
$string['recordingdatetitle'] = 'Date/Time';
$string['recordingdurationtitle'] = 'Duration (H:M:s)';
$string['recordingplaytitle'] = 'Play';
$string['recordingoptionstitle'] = 'Options';
$string['norecordingsavailable'] = 'No recordings available.';
$string['conversionerror'] = 'Error: {$a}';
$string['conversioncommerror'] = 'Error - try again later';
$string['editrecordingdescription'] = 'Edit recording description';
$string['recordingdeleteerror'] = 'Recording could not be deleted.  Error: {$a}';
$string['recordingmanual'] = 'Recording is manually controlled';
$string['recordingnone'] = 'Recording is turned off';
$string['recordingautomatic'] = 'Recording is automatically turned on';
$string['recordings'] = 'Recordings';
$string['recordinggroupvisibleall'] = 'Recording is visible to anyone in this course. Click to change.';
$string['recordinggroupvisiblesingle'] = 'Recording is visible only to members of this group. Click to change.';
$string['recordinglauncherror'] = 'Could not load Blackboard Collaborate recording';
$string['recording'] = 'Title - Date';
$string['viewrecordingdescription'] = 'View recording details and manage conversions';
$string['recordingat'] = 'Collaborate Recording - ';
$string['format'] = 'Format';
$string['status'] = 'Status';
$string['converror'] = "Conversion Error";
$string['lastupdate'] = "Last Updated";
$string['vcr'] = 'Blackboard Collaborate';
$string['mp3'] = 'Audio (MP3)';
$string['mp4'] = 'Video (MP4)';
$string['available'] = "Available";
$string['playlink'] = "Play Recording";
$string['inprogress'] = "In Progress - Please Wait";
$string['notavailable'] = "Not Available";
$string['convertlink'] = "Convert Recording";
$string['error'] = "Conversion Error";
$string['manualstatusupdatelink'] = "Check Conversion Status";
$string['loadrecording'] = "Please wait: recording is now being loaded.";
$string['conversionformaterror'] = 'An error occurred converting format {$a} for this recording.  Please see the logs for more details';
$string['playrecordingnotavailable'] = 'This recording is no longer available.  It may have been automatically removed due to capacity limits.';
$string['backtosession'] = "View Blackboard Collaborate Session";
$string['notapplicable'] = "-";
$string['conversiontitle'] = "Convert";
$string['incorrectversion'] = 'This recording cannot be converted to Audio/Video format.';

//Conversion Status Messages
$string['vcrplayrecording'] = 'Play Blackboard Collaborate Format';
$string['mp3playrecording'] = 'Play Audio (MP3) Format';
$string['mp4playrecording'] = 'Play Video (MP4) Format';
$string['mp3not_available'] = 'Audio (MP3)';
$string['mp4not_available'] = 'Video (MP4)';
$string['mp3in_progress'] = 'Audio (MP3) - In Progress';
$string['mp4in_progress'] = 'Video (MP4) - In Progress';
$string['mp3not_applicable'] = 'Audio (MP3) - Error';
$string['mp4not_applicable'] = 'Video (MP4) - Error';


// ****** Session User Management Form ******
$string['availablemoderators'] = '{$a} available Moderator(s)';
$string['availableparticipant'] = '1 available Participant';
$string['availableparticipants'] = '{$a} available Participants';
$string['existingmoderators'] = '{$a} existing Moderator(s)';
$string['existingparticipant'] = '1 existing Participant';
$string['existingparticipants'] = '{$a} existing Participants';
$string['addmoderators'] = 'Add Moderator(s)';
$string['addparticipants'] = 'Add Participant(s)';
$string['sessioncreator'] = 'Session Creator / Default Moderator:';
$string['editingmoderators'] = 'Editing Moderators';
$string['editingparticipants'] = 'Editing Participants';
$string['couldnotadduserstosession'] = 'Could not add user(s) to session.';
$string['couldnotremoveusersfromsession'] = 'Could not remove user(s) from session.';
$string['removemoderators'] = 'Remove Moderator(s)';
$string['removeparticipants'] = 'Remove Participant(s)';
$string['participanteditbadsessiontype'] = 'Participants cannot be managed for this session type.  The session must be a non-group session with restricted Participants.';
$string['invalidformdata'] = 'Invalid data submitted.';
$string['user_edit_invalid_selection'] = 'Invalid user selection.  <br>To add a user to the session, select a user from the "available" list and click add.<br>To remove a user from the session, select a user from the "existing" list and click remove.' ;

// ****** Preload Page ******
$string['addpreload'] = 'Add a preload file';
$string['deletepreloadfile'] = 'Delete preload file';
$string['deletewhiteboardpreload'] = 'Delete a whiteboard preload file';
$string['preloadchoosewhiteboardfile'] = 'Choose a preload file (*.wbd, *.wbp, *.elp, *.elpx) to upload, with a maximum size of {$a->uploadmaxfilesize}.';
$string['preloadcouldnotaddpreloadtomeeting'] = 'Could not add preload to session.  Error: {$a}';
$string['preloadcouldnotcreatepreload'] = 'Could not create preload.  Error: {$a}';
$string['preloadcouldnotreadfilecontents'] = 'Could not read uploaded file contents';
$string['preloadcouldnotstreamepreload'] = 'Could not send file contents to ELM server';
$string['preloaddeleteerror'] = 'Could not delete the preload.  Error: {$a}';
$string['preloaddeletemeetingerror'] = 'Could not delete preload from session';
$string['preloaddeletesuccess'] = 'Successfully deleted the preload file';
$string['preloademptyfile'] = 'The uploaded file was empty';

//Preload Validation Errors
$string['preloadinvalidfileempty'] = 'The uploaded file is empty.';
$string['preloadinvalidnotxml'] = 'The uploaded file is not in XML format.';
$string['preloadinvalidfileextension'] = 'The uploaded file uses an invalid extension';
$string['preloadnofileextension'] = 'The uploaded file has no file extension';
$string['preloadfiletoolarge'] = 'The preload you attempted to upload was too large.';
$string['preloadfileinvalidname'] = 'No file chosen.  Please choose a valid file for upload.';
$string['preloaduploadsuccess'] = 'Successfully uploaded preload file: {$a}';

// ****** Attendance Page *******
$string['attendancescalenotice'] = 'Because you are using a scale for the attendance grade value, you can set individual user values below.';
$string['attended'] = 'Attended';
$string['updateattendance'] = 'Update attendance';
$string['meetingattendance'] = 'Session attendance';
$string['attendance'] = 'Attendance';
$string['attendancefor'] = 'Attendance for {$a}';
$string['attendancenousers'] = 'No Participants are enrolled in this session for attendance.';
$string['sessionnotgraded'] = 'Attendance can only be managed for sessions where grading has been enabled and a numerical or scaled grade has been selected.';

// ********* Module Configuration Settings  ************ 
$string['configauthusername'] = 'Web Service Account for Blackboard Collaborate Scheduling Server.';
$string['configauthpassword'] = 'Web Service Password for Blackboard Collaborate Scheduling Server.';
$string['configboundarydefault'] = 'Allow teachers to choose a boundary time (in minutes) for their sessions or force a default value here.';
$string['configopenchair'] = 'All users will join the session as a Moderator in the Blackboard Collaborate session.';
$string['configmustbesupervised'] = 'Permits Moderators to view all private chat messages in the Blackboard Collaborate session.';
$string['configmaxtalkers'] = 'Maximum number of simultaneous talkers to be configured in the Blackboard Collaborate session at session launch time.';
$string['configprepopulatemoderators'] = 'Yes: All Course Users with moderator privileges are added as session Moderators.<br>No:   Only the User that creates the session is added as session Moderator.';
$string['configpermissionson'] = 'All users who join the session as Participants are granted full permissions to session resources such as audio, whiteboard, etc.';
$string['configseatreservation'] = 'Allow teachers to reserve seats on the Blackboard Collaborate server for their sessions.';
$string['configscheduler'] = 'Type of scheduler being used (SAS/ELM)';
$string['configserver'] = 'Default server to use when creating a new Blackboard Collaborate session';
$string['configserverblank'] = 'You must enter a server URL';
$string['configraisehand'] = 'When users join the Blackboard Collaborate session, they will automatically raise their hand (this may be accompanied by an audible notification).';
$string['configuserduration'] = 'The number of days a Student\'s account will exist on the Blackboard Collaborate server before being automatically deleted';
$string['configwsdebug'] = 'Turn on Web Services debugging: useful when you are receiving <b>Fault</b> errors using this module but prints out a <b>lot</b> of extra information.';
$string['configloglevel'] = 'Set the level of logging output to the Blackboard Collaborate Module Logs.';
$string['downloadlogs'] = 'View and download available log files';
$string['configenabletelephony'] = 'Allow teleconferencing to be enabled/disabled for individual sessions (defaults to Yes), or choose a default value for all sessions that cannot be modified.';
$string['connectiontestfailure'] = 'Connection test failed!  Check your settings.';
$string['connectiontestsuccessful'] = 'Connection test succeeded!';
$string['default_elluminate_server'] = 'http://localhost:8080';
$string['default_elluminate_scheduler'] = 'SAS';
$string['default_elluminate_auth_username'] = 'default_user';
$string['default_elluminate_auth_password'] = 'default_pass';
$string['elluminate_auth_username'] = 'Username';
$string['elluminate_auth_password'] = 'Password';
$string['elluminate_boundary_default'] = 'Default Boundary Time';
$string['elluminate_must_be_supervised'] = 'Must Be Supervised';
$string['elluminate_all_moderators'] = 'All Moderators';
$string['elluminate_permissions_on'] = 'Permissions On';
$string['elluminate_pre_populate_moderators'] = 'Pre-Populate Moderators';
$string['elluminate_raise_hand'] = 'Raise Hand on Entry';
$string['elluminate_scheduler'] = 'Scheduler';
$string['elluminate_seat_reservation'] = 'Seat Reservation';
$string['elluminate_server'] = 'Server URL';
$string['elluminate_user_duration'] = 'User Duration';
$string['elluminate_telephony'] = 'Enable Session Teleconference';
$string['elluminate_ws_debug'] = 'Web Services Debugging';
$string['elluminate_log_level'] = 'Log Level';
$string['elluminate_max_talkers'] = 'Max Talkers';
$string['testconnection'] = 'Test connection';
$string['testconnectionnotice'] = 'Test the values in the form right now to verify they successfully connect to your server.  <b>Opens in a new window</b>';
$string['elluminateconnectiontest'] = 'Blackboard Collaborate connection test';
$string['serverurl'] = 'Server URL';
$string['serverlogs'] = 'Blackboard Collaborate Logs';
$string['backtosettings'] = 'Back to Blackboard Collaborate Settings';
$string['logname'] = 'File Name';
$string['logdate'] = 'Log Date';
$string['logsize'] = 'File Size';
$string['logsizeunits'] = 'KB';
$string['viewlogs'] = 'View Logs';
$string['licenseoption'] = 'License';
$string['licensevariation'] = 'Variation Name';
$string['licensed'] = 'licensed';
$string['licenses'] = 'Blackboard Collaborate License Information';

// ************ General Error Messages *********
$string['cmidincorrect'] = 'Course Module ID is incorrect';
$string['couldnotloadsessionmeetingid'] = 'Could not load session with meetingID: ';
$string['courseidincorrect'] = 'Course ID is incorrect';
$string['deletesessionloaderror'] = 'Delete Session, Load Error: ';
$string['fromdatabase'] = ' from database. ';
$string['here'] = 'here';
$string['invalidsessiontype'] = 'Invalid Session Type: ';
$string['invalidrecordingid'] = 'Invalid Recording ID';
$string['nomeetings'] = 'There are no Blackboard Collaborate meetings ';
$string['viewrecordingloaderror'] = 'Recording Load Error: {$a}';
$string['viewsessionloaderror'] = 'Session Load Error: {$a}';
$string['viewsessiongenericerror'] = 'Could not load session with ID [{$a}].  Please contact your administrator.';
$string['toplevelnotfound'] = 'Could not find a top-level course!';
$string['user_error_unconfiguredmodule'] = 'The Blackboard Collaborate module has not been configured.  Please contact your administrator.';
$string['user_error_soaperror'] = 'An error occurred communicating with the SAS server.  Please contact your administrator.';
$string['soap_send_command_error'] = 'An error occurred communicating with the SAS server.  Please contact your administrator. <br>Error Details: {$a->soapmessage}';
$string['user_error_processing'] = 'An internal processing error has occurred.  Please contact your administrator.';
$string['user_error_database'] = 'An internal database error has occurred.  Please contact your administrator.';
$string['sessioncreationerror'] = 'Error: Session could not be created : {$a}';
$string['sessionupdateerror'] = 'Error: Session could not be updated: ${a}';
$string['sessionloaderror'] = 'Error: Blackboard Collaborate Session with ID of {$a} could not be launched.';
$string['responseerror'] = 'Invalid Web Service Response Type: [{$a}]';
$string['groupiderror'] = 'Invalid session group id [{$a}].';
$string['groupiniterror'] = 'Group session could not be initialized.  Session ID [{$a->id}] Group ID [{$a->group}].  <br><br>Scheduling Server Error: {$a->errormsg}';
$string['upgrade30error_api'] = 'In order to use version 3.0 of the Moodle for SAS module, your API account must have access the version 3 of the API enabled. <br> The upgrade cannot complete until this is enabled.  <br>Please contact Collaborate Support. <br> Once the v3 API is enabled, you can re-attempt this upgrade by loading <a href="{$a->retry}">{$a->retry}</a>';
$string['upgrade30error'] = 'An error occured during the process of converting your session data for version 3.0 of the Moodle for SAS module. <br> You can re-attempt this upgrade by loading <a href="{$a->retry}">/mod/elluminate/web/util/30_upgrade_retry.php</a> <br>You can find more details about the error in the <a href="{$a->logs}">logs</a> </a><br>If you continue to have issues, please contact Collaborate Support.';
$string['groupdoesnotexist'] = 'Invalid Group ID of [{$a}].  A Group with this ID does not exist for this course.';
$string['recordingidinvalid'] = 'Delete recording failed.  Recording with ID of [{$a}] does not exist.';

// ******* Force Group Mode Handling ***********
$string['convertedgroupsession1'] = 'This session has been updated from group mode to no groups due to a Course level setting.';
$string['convertedgroupsession2'] = 'Click below to access to the existing scheduling group sessions and any associated recordings.';
$string['convertedgroupsessionreturn'] = 'Return to main session';

// ******** Permission Errors **************
$string['recording_generalpermissionserror'] = 'You do not have permissions to view this recording.';
$string['recording_hiddenpermissionserror'] = 'You do not have permissions to view this recording.';
$string['recording_meetinggeneralpermissionserror'] = 'You do not have permissions to view this recording.';
$string['recording_meetingprivatepermissionserror'] = 'You do not have permissions to view this recording.';
$string['recording_meetinggrouppermissionserror'] = 'You do not have permissions to view this recording.';
$string['recordingconvertpermissionserror'] = 'You do not have permissions to request a conversion for this recording.';
$string['meetinggeneralpermissionserror'] = 'You do not have permissions to join this meeting.';
$string['meetingprivatepermissionserror'] = 'You do not have permissions to join this meeting.';
$string['meetingattendancepermissionserror'] = 'You do not have permissions to manage attendance for this meeting.';
$string['meetingeditpermissionserror'] = 'You do not have permissions to edit this meeting.';
$string['viewattendanceepermissionserror'] = 'You do not have permissions to view attendance for this Blackboard Collaborate Session.';
$string['privatesessionnotinvited'] = 'You cannot join this meeting without an invite.';
$string['recordingdeletepermissionserror'] = 'You do not have permissions to delete this recording.';
$string['recordingeditpermissionserror'] = 'You do not have permissions to edit this recording.';
$string['recordingconvertpermissionserror'] = 'You do not have permissions to request conversion of this recording.';
$string['recordingmanagepermissionserror'] = 'You do not have permissions to change the visibility of this recording.';
$string['groupsessionnotinvited'] = 'You cannot join this meeting without being a member of the correct group.';
$string['recordingmp4notlicensed'] = 'You are not licensed for MP3/MP4 recording conversion and playback';
$string['logpermissions'] = 'You do not have permissions to access Blackboard Collaborate Logs.';
$string['licensepermissions'] = 'You do not have permissions to access Blackboard Collaborate License Details.';

// ******** Moodle Integration/Features ************
$string['modulename'] = 'Blackboard Collaborate';
$string['modulenameinstance'] = 'Blackboard Collaborate Session';
$string['modulenameplural'] = 'Blackboard Collaborate Sessions';
$string['sessionsviewed'] = 'Blackboard Collaborate Sessions:';
$string['calendarname'] = '[Blackboard Collaborate Session] : {$a}';
$string['pluginname'] = 'Blackboard Collaborate';
$string['pluginadministration'] = 'Blackboard Collaborate Administration';
//Grading Report
$string['notattendedyet'] = 'Not attended yet';
