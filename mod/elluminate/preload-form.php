<?php 
include('web/includes/moodle.required.php');

$id = optional_param('id', '', PARAM_INT);
$delete = optional_param('delete', 0, PARAM_ALPHANUM);

$loadParent = false;
include('web/includes/session.load.php');
//Session View Container
include('web/includes/session.loadview.php');
//basic session permission checks
include('web/includes/session.permissions.php');
include('web/includes/session.permissioncheck.php');

//Initialize Group Session if needed
$exitOnError = true;
include('web/includes/session.group-init.php');

//Permission Checks For Preloadd
require_capability('mod/elluminate:managepreloads', $context);

//Create View Helper Object
$pageView = $ELLUMINATE_CONTAINER['preloadView'];
$pageView->preloadSession = $pageSession;
$pageView->courseModule = $cm;
$pageView->wwwroot = $CFG->wwwroot;

$pageView->sesskey = sesskey();
//Form Action Handling
// *** ADD ACTION ***
if (($data = data_submitted($CFG->wwwroot . '/mod/elluminate/preload-form.php')) &&
      confirm_sesskey()) {
   //this will either direct to an error page or success page
   $pageView->processAddAction($USER->id);
}

// *** DELETE ACTION ***
if (!empty($delete)) {
   $pageView->processDeleteAction($delete);
}

//Page Detail Setup
$pageUrl = '/mod/elluminate/preload-form.php?id=' . $id;
$pageTitle = get_string('addpreload','elluminate');
$pageHeading = $pageTitle;
include('web/includes/moodle.header.php');

echo $pageView->getPreloadFormHTML();

include('web/includes/moodle.footer.php');