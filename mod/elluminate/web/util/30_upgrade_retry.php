<?php
include ('../includes/moodle.required.php');

require_login();

require_capability('moodle/site:config', context_system::instance());

echo "Re-attempting 3.0 upgrade";
$retryMode = true;
include_once '../../db/elluminate_upgrade_30_rerun.php';