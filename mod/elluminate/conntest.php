<?php 
require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(__FILE__) . '/lib.php';

require_login(SITEID, false);

$context = get_context_instance(CONTEXT_SYSTEM);
require_capability('mod/elluminate:manage',$context);

if (!$site = get_site()) {
   redirect($CFG->wwwroot);
}

$serverurl = required_param('serverURL', PARAM_NOTAGS);
$username  = required_param('authUsername', PARAM_NOTAGS);
$password  = required_param('authPassword', PARAM_NOTAGS);

$PAGE->set_url('/mod/elluminate/conntest.php', array('serverURL'=>$serverurl,
    		'authUsername'=>$username,
    		'authPassword'=>$password));

$strtitle = get_string('elluminateconnectiontest', 'elluminate');
print_header_simple(format_string($strtitle));
echo $OUTPUT->box_start('generalbox', 'notice');

$connectSuccess = false;
try {
	Elluminate_Config_Settings::testConnection($serverurl, $username, $password);
	echo $OUTPUT->notification(get_string('connectiontestsuccessful', 'elluminate'), 'notifysuccess');
	$connectSuccess = true;
} catch (Elluminate_Exception $e) {
	echo $OUTPUT->notification(get_string($e->getUserMessage(), 'elluminate', $e->getDetails()));
} catch (Exception $e) {
    echo $OUTPUT->notification(get_string('connectiontestfailure', 'elluminate'));
}

echo '<center><input type="button" onclick="self.close();" value="' . get_string('closewindow') . '" /></center>';

if ($connectSuccess){
   //MOOD-428 clear cache on settings save/conn test
   $cacheManager = $ELLUMINATE_CONTAINER['cacheManager'];
   $cacheManager->clearCacheContent();
   
   //MOOD-501 force license check on next cron run
   $moodleDAO = $ELLUMINATE_CONTAINER['moodleDAO'];
   $moodleDAO->deleteConfigRecord('elluminate_last_license_check');
}

echo $OUTPUT->box_end();
echo $OUTPUT->footer();
