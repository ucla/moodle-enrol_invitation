<?php

define('CLI_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/ucla_browseby/block_ucla_browseby.php');

$k = new block_ucla_browseby();
$k->termslist = array('11W');
$k->cron();
