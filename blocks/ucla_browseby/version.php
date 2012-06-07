<?php

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2012041700;
$plugin->component = 'block_ucla_browseby';
$plugin->cron = 86400;  // (60 * 60 * 24), once a day
$plugin->dependencies = array('local_ucla' => 2012020100);
