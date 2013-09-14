<?php

require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once dirname(__FILE__) . '/lib.php';
require_login();

if (has_capability('moodle/site:config', context_system::instance())) {
	
	$url = "/mod/elluminate/forcecron.php";
	Elluminate_Audit_Log::log(Elluminate_Audit_Constants::CRON_START, $url);
	
	echo('Forcing Blackboard Collaborate cron job to run.  See Log for details.<br/>');
	elluminate_cron();
	
}