<?php
include ('../includes/moodle.required.php');

require_login();

//Make sure wea re an admin
if (!is_siteadmin()) {
   print_error(get_string('logpermissions','elluminate'));
}

$paramlogname = required_param('logname', PARAM_NOTAGS);

//Always strip off path if passed - we only get files from log dir
$logname = basename($paramlogname);

$logger = Elluminate_Logger_Factory::getLogger("logDownload");
$logDir = $logger->getLogDir();

$fullName = $logDir . $logname . ".log";
if (file_exists($fullName)){
   header("Content-type: application/octet-stream" );
   header("Content-Disposition: attachment; filename=".$fullName);
   readfile ($fullName);
   Elluminate_Audit_Log::log(Elluminate_Audit_Constants::DOWNLOAD_LOG,'download-log.php?logname=' . $paramlogname);
}else{
   echo "Invalid Log File: " . $logname;
}