<?php

define('CLI_SCRIPT', true);

require('../../config.php');
require_once('lib.php');

// run ucla cron for every term in ucla_request_classes
$terms = $DB->get_records_menu('ucla_request_classes', null, '', 'id, term');
$terms = array_unique((array) $terms);

local_ucla_cron($terms);
