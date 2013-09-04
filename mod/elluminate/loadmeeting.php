<?php 
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(__FILE__) . '/lib.php';

//Required Parameter - Session ID
$id = required_param('id', PARAM_INT);

//Load Session
$loadParent = false;
include('web/includes/session.load.php');
//Load View Container
include('web/includes/session.loadview.php');
//basic session permission checks
include('web/includes/session.permissions.php');
require_course_login($cm->course, true);

//Initialize Group Session if needed
$exitOnError = true;
include('web/includes/session.group-init.php');

echo "test";
if (! $permissions->doesUserHaveLoadPermissionsForSession()){
   print_error($permissions->permissionFailureKey,'elluminate');
}

// We always log attendance for the non-moderators who attend the session, 
// regardless of if session is graded.  This information is used in moodle reports.
// Who is a moderator is based on mod/elluminate:moderatemeeting, not the session
// moderator list
if (! $permissions->doesUserHaveModeratePermissionsForSession()){
   $pageSession->logAttendance($USER->id);
   if ($pageSession->gradesession){
      $pageSession->logToGradeBook($USER->id);
   }
}

$pageUrl = 'loadmeeting.php?id=' .$pageSession->id;

// Load the meeting.
$launchSuccess = false;
if($pageSession->meetingid) {
   $user = $pageSession->getSessionLaunchUser($USER->id);

   $launchURL = $pageSession->getLaunchURL($user);
   
   if ($launchURL){
      Elluminate_Audit_Log::log(Elluminate_Audit_Constants::SESSION_LOAD, $pageUrl,$pageSession, $cm);
      header('Location:' . $launchURL);
      $launchSuccess = true;
   }
} 

if (!$launchSuccess){
   print_error(get_string('sessionloaderror','elluminate',$pageSession->id));
}
