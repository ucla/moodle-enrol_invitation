<?php
include('web/includes/moodle.required.php');

$id = required_param('id', PARAM_INT);

//Page Detail Setup
$pageUrl = '/mod/elluminate/attend-form.php?id=' . $id;
$pageTitle = get_string('updateattendance','elluminate');

$loadParent = true;
include('web/includes/session.load.php');
include('web/includes/session.loadview.php');

include('web/includes/session.permissions.php');
include('web/includes/session.permissioncheck.php');

//Permission Checks
if (!$permissions->doesUserHaveViewAttendancePermissionsForSession()){
   print_error(get_string($permissions->permissionFailureKey,'elluminate'));
}

//View Helper
try {
   $attendViewHelper = $ELLUMINATE_CONTAINER['attendView'];
   $attendViewHelper->init($pageSession, $CFG->wwwroot, 
            $permissions->doesUserHaveManageAttendancePermissionsForSession(), $context);
} catch (Elluminate_Exception $e) {
	print_error(get_string($e->getUserMessage(),'elluminate'));
} catch (Exception $e) {
	print_error(get_string('user_error_processing','elluminate'));
}

//FORM HANDLING
$data = data_submitted($CFG->wwwroot . '/mod/elluminate/attend-form.php');
if ($data && confirm_sesskey()) {
   $attendViewHelper->processUpdateAction($data);
   Elluminate_Audit_Log::log(Elluminate_Audit_Constants::SESSION_ATTENDANCE_EDIT, $pageUrl,$pageSession, $cm);
}

//HTML OUTPUT
$pageHeading = get_string('attendancefor', 'elluminate', $pageSession->name);
include('web/includes/moodle.header.php');

$sesskey = sesskey();

$table = $attendViewHelper->setupMoodleTable();
$userData =$attendViewHelper->getUserTableData($sesskey);

if ($userData == null) {
   $table->data[] = array(get_string('attendancenousers','elluminate'));
   echo html_writer::table($table);
} else {
   echo $attendViewHelper->getFormStartHTML($sesskey); 
   if ($pageSession->isSessionGradeScaled() && $permissions->doesUserHaveManageAttendancePermissionsForSession()) {
      echo $OUTPUT->heading(get_string('attendancescalenotice', 'elluminate'));
   }
   
   $table->data = $userData;
   $table->head = $attendViewHelper->getHeaderRow();
   echo html_writer::table($table);
   echo $attendViewHelper->getFormEndHTML($sesskey);
}

include ('web/includes/moodle.footer.php');
