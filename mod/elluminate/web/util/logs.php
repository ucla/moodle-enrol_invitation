<?php 
include ('../includes/moodle.required.php');

require_login();

if (!is_siteadmin()) {
   print_error(get_string('logpermissions','elluminate'));
}

$PAGE->set_context(get_system_context());
$PAGE->set_url('/mod/elluminate/web/util/logs.php');
$PAGE->set_heading(get_string('serverlogs','elluminate'));
$PAGE->set_pagelayout('admin');
echo $OUTPUT->header();
echo $OUTPUT->box_start();

echo "<a href='javascript:history.back()'><span>" . get_string('backtosettings' , 'elluminate') . "</span></a>";

$logView = $ELLUMINATE_CONTAINER['logView'];
echo $logView->getLogList();

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
