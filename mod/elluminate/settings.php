<?php  //$Id: settings.php,v 1.1 2009-04-01 21:04:34 jfilip Exp $
GLOBAL $PAGE;

require_once $CFG->dirroot . '/mod/elluminate/lib.php';
require_once ($CFG->libdir . '/formslib.php');
$PAGE->requires->js('/mod/elluminate/web/js/elltestconnection.js');


if ($ADMIN->fulltree) {

	$settings->add(new admin_setting_configtext('elluminate_server', get_string('elluminate_server', 'elluminate'),
	                   get_string('configserver', 'elluminate'), get_string('default_elluminate_server', 'elluminate'), PARAM_URL));
	
	$settings->add(new admin_setting_configtext('elluminate_auth_username', get_string('elluminate_auth_username', 'elluminate'),
	                   get_string('configauthusername', 'elluminate'), get_string('default_elluminate_auth_username', 'elluminate'), PARAM_TEXT));
	
	$settings->add(new admin_setting_configpasswordunmask('elluminate_auth_password', get_string('elluminate_auth_password', 'elluminate'),
	                   get_string('configauthpassword', 'elluminate'), get_string('default_elluminate_auth_password', 'elluminate')));                   
	
	$duration    = array();
	$duration[0] = get_string('disabled', 'elluminate');
	
	for ($i = 1; $i <= 365; $i++) {
	    $duration[$i] = $i;
	}           
	
	$boundary_times = array(
	    -1  => get_string('choose'),
	    0  => '0',
	    15 => '15',
	    30 => '30',
	    45 => '45',
	    60 => '60'
	);

   $max_talkers = array(
		-1 => get_string('choose'),
		1 => '1',
		2 => '2',
		3 => '3',
		4 => '4',
		5 => '5',
		6 => '6'
	);
	
	$telephony_options = array(
      -1  => get_string('choose'),
      1  => get_string('yes'),
      2 => get_string('no')
	);
	
	$settings->add(new admin_setting_configselect('elluminate_boundary_default', get_string('elluminate_boundary_default', 'elluminate'),
	                   get_string('configboundarydefault', 'elluminate'), Elluminate_Session::BOUNDARY_TIME_DEFAULT, $boundary_times));
	
	$settings->add(new admin_setting_configselect('elluminate_pre_populate_moderators', get_string('elluminate_pre_populate_moderators', 'elluminate'),
						get_string('configprepopulatemoderators', 'elluminate'), 0, array(0 => get_string('no'), 1 => get_string('yes')))); 
	
	$settings->add(new admin_setting_configselect('elluminate_permissions_on', get_string('elluminate_permissions_on', 'elluminate'),
						get_string('configpermissionson', 'elluminate'), 0, array(0 => get_string('no'), 1 => get_string('yes'))));
	                   
	$settings->add(new admin_setting_configselect('elluminate_raise_hand', get_string('elluminate_raise_hand', 'elluminate'),
	   					get_string('configraisehand', 'elluminate'), 0, array(0 => get_string('no'), 1 => get_string('yes'))));
	   
	$settings->add(new admin_setting_configselect('elluminate_all_moderators', get_string('elluminate_all_moderators', 'elluminate'),
	   					get_string('configopenchair', 'elluminate'), 0, array(0 => get_string('no'), 1 => get_string('yes'))));
	   
	$settings->add(new admin_setting_configselect('elluminate_must_be_supervised', get_string('elluminate_must_be_supervised', 'elluminate'),
	   					get_string('configmustbesupervised', 'elluminate'), 0, array(0 => get_string('no'), 1 => get_string('yes'))));
	   					
	$settings->add(new admin_setting_configselect('elluminate_max_talkers', get_string('elluminate_max_talkers', 'elluminate'),
	                   get_string('configmaxtalkers', 'elluminate'), Elluminate_Session::MAX_TALKERS_DEFAULT, $max_talkers));
	
	//Telephony
	global $ELLUMINATE_CONTAINER;
	$licenseManager = $ELLUMINATE_CONTAINER['licenseManager'];
	
	if ($licenseManager->isTelephonyLicensed()){
	   $settings->add(new admin_setting_configselect('elluminate_telephony', get_string('elluminate_telephony', 'elluminate'),
	                   get_string('configenabletelephony', 'elluminate'), -1, $telephony_options));
	}
	
	$settings->add(new admin_setting_configselect('elluminate_ws_debug', get_string('elluminate_ws_debug', 'elluminate'),
	                   get_string('configwsdebug', 'elluminate'), 0, array(0 => get_string('no'), 1 => get_string('yes'))));     
	
	//Log Level Setting, Link to access logs
	$logchoices = array('1' => 'DEBUG', '2' => 'INFO', '3' => 'WARN', '4' => 'ERROR');
	$logtext = get_string('configloglevel','elluminate');
	$logURL = new moodle_url('/mod/elluminate/web/util/logs.php');
	$logtext = $logtext .= '<br><a href="' .  $logURL . '">' . get_string('downloadlogs','elluminate') . "</a>";
	$settings->add(new admin_setting_configselect('elluminate_log_level', get_string('elluminate_log_level', 'elluminate'),
			$logtext, Elluminate_Logger_Factory::DEFAULT_LOG_LEVEL, $logchoices));
	
	//echo "<input type='hidden' name='wwwvalue' value='" . M.cfg.wwwroot . "' id='wwwvalue'></input>";

}                           
