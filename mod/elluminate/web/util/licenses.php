<?php
include ('../includes/moodle.required.php');

require_login();

if (!is_siteadmin()) {
   print_error(get_string('licensepermissions','elluminate'));
}

$PAGE->set_context(get_system_context());
$PAGE->set_url('/mod/elluminate/web/util/licenses.php');
$PAGE->set_heading(get_string('licenses','elluminate'));
$PAGE->set_pagelayout('admin');
echo $OUTPUT->header();
echo $OUTPUT->box_start();

$licenseView = $ELLUMINATE_CONTAINER['licenseView'];
echo $licenseView->getLicenseList();

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
